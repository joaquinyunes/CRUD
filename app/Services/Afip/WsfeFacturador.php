<?php

namespace App\Services\Afip;

use App\Contracts\FacturadorElectronico;
use App\Models\ComprobanteAfip;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use RuntimeException;
use SoapClient;

/**
 * Cliente WSFEv1 de AFIP/ARCA. Requiere ext-soap + ext-openssl y un
 * certificado emitido en ARCA (config/afip.php).
 *
 * Flujo: WSAA (login CMS -> token/sign, cacheado en TA.xml) -> WSFE
 * (FECompUltimoAutorizado + FECAESolicitar).
 */
class WsfeFacturador implements FacturadorElectronico
{
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('afip');
    }

    public function disponible(): bool
    {
        return extension_loaded('soap')
            && extension_loaded('openssl')
            && $this->cfg['cuit']
            && is_readable($this->cfg['cert'])
            && is_readable($this->cfg['key']);
    }

    public function ultimoNumero(int $puntoVenta, int $tipoComprobante): int
    {
        $this->guard();
        $client = $this->wsfeClient();
        $auth = $this->auth();

        $res = $client->FECompUltimoAutorizado([
            'Auth' => $auth,
            'PtoVta' => $puntoVenta,
            'CbteTipo' => $tipoComprobante,
        ]);

        return (int) ($res->FECompUltimoAutorizadoResult->CbteNro ?? 0);
    }

    public function autorizar(Venta $venta, int $tipoComprobante, int $docTipo, string $docNro): ComprobanteAfip
    {
        $this->guard();

        $pv = (int) $this->cfg['punto_venta'];
        $numero = $this->ultimoNumero($pv, $tipoComprobante) + 1;

        $total = round((float) $venta->total_final, 2);
        $iva = round((float) $venta->impuesto, 2);
        $neto = round($total - $iva, 2);
        $hoy = now()->format('Ymd');

        $req = [
            'Auth' => $this->auth(),
            'FeCAEReq' => [
                'FeCabReq' => ['CantReg' => 1, 'PtoVta' => $pv, 'CbteTipo' => $tipoComprobante],
                'FeDetReq' => ['FECAEDetRequest' => [
                    'Concepto' => 1,
                    'DocTipo' => $docTipo,
                    'DocNro' => $docNro,
                    'CbteDesde' => $numero,
                    'CbteHasta' => $numero,
                    'CbteFch' => $hoy,
                    'ImpTotal' => $total,
                    'ImpTotConc' => 0,
                    'ImpNeto' => $neto,
                    'ImpOpEx' => 0,
                    'ImpIVA' => $iva,
                    'ImpTrib' => 0,
                    'MonId' => 'PES',
                    'MonCotiz' => 1,
                    'Iva' => ['AlicIva' => [
                        'Id' => (int) $this->cfg['iva_alicuota_id'],
                        'BaseImp' => $neto,
                        'Importe' => $iva,
                    ]],
                ]],
            ],
        ];

        $res = $this->wsfeClient()->FECAESolicitar($req);
        $result = $res->FECAESolicitarResult;
        $det = $result->FeDetResp->FECAEDetResponse;

        $resultado = $det->Resultado ?? 'R';
        $obs = '';
        if (isset($result->Errors)) {
            $obs = json_encode($result->Errors);
        } elseif (isset($det->Observaciones)) {
            $obs = json_encode($det->Observaciones);
        }

        return ComprobanteAfip::create([
            'venta_id' => $venta->id,
            'tipo_comprobante' => $tipoComprobante,
            'punto_venta' => $pv,
            'numero' => $numero,
            'cae' => $resultado === 'A' ? ($det->CAE ?? null) : null,
            'cae_vencimiento' => $resultado === 'A' && ! empty($det->CAEFchVto)
                ? Carbon::createFromFormat('Ymd', $det->CAEFchVto)->toDateString()
                : null,
            'importe_total' => $total,
            'importe_neto' => $neto,
            'importe_iva' => $iva,
            'doc_tipo' => $docTipo,
            'doc_nro' => $docNro,
            'resultado' => $resultado === 'A' ? 'A' : 'R',
            'observaciones' => $obs ?: null,
        ]);
    }

    // ---- WSAA ----

    private function auth(): array
    {
        $ta = $this->ticketAcceso();

        return ['Token' => $ta['token'], 'Sign' => $ta['sign'], 'Cuit' => $this->cfg['cuit']];
    }

    private function ticketAcceso(): array
    {
        $file = rtrim($this->cfg['ta_path'], '/\\').'/TA.xml';

        if (is_readable($file)) {
            $xml = simplexml_load_file($file);
            if ($xml && strtotime((string) $xml->header->expirationTime) > time() + 600) {
                return ['token' => (string) $xml->credentials->token, 'sign' => (string) $xml->credentials->sign];
            }
        }

        return $this->loginCms($file);
    }

    private function loginCms(string $cacheFile): array
    {
        $now = time();
        $tra = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?><loginTicketRequest version="1.0">'.
            '<header><uniqueId>%d</uniqueId><generationTime>%s</generationTime><expirationTime>%s</expirationTime></header>'.
            '<service>wsfe</service></loginTicketRequest>',
            $now, date('c', $now - 60), date('c', $now + 3600)
        );

        $traFile = tempnam(sys_get_temp_dir(), 'tra');
        $cmsFile = tempnam(sys_get_temp_dir(), 'cms');
        file_put_contents($traFile, $tra);

        $ok = openssl_pkcs7_sign(
            $traFile, $cmsFile, 'file://'.$this->cfg['cert'],
            ['file://'.$this->cfg['key'], ''], [], ! PKCS7_DETACHED
        );
        if (! $ok) {
            throw new RuntimeException('No se pudo firmar el TRA para WSAA: '.openssl_error_string());
        }

        // Extrae el CMS en base64 (quita cabeceras MIME).
        $cms = file_get_contents($cmsFile);
        [, $cms] = explode("\n\n", $cms, 2);
        $cms = preg_replace('/^-----.*$/m', '', $cms);
        @unlink($traFile);
        @unlink($cmsFile);

        $client = new SoapClient($this->endpoint('wsaa'), ['trace' => 1, 'exceptions' => true, 'soap_version' => SOAP_1_2]);
        $resp = $client->loginCms(['in0' => trim($cms)]);
        $xml = simplexml_load_string($resp->loginCmsReturn);

        @file_put_contents($cacheFile, $resp->loginCmsReturn);

        return ['token' => (string) $xml->credentials->token, 'sign' => (string) $xml->credentials->sign];
    }

    private function wsfeClient(): SoapClient
    {
        return new SoapClient($this->endpoint('wsfe'), [
            'soap_version' => SOAP_1_2, 'trace' => 1, 'exceptions' => true,
            'location' => str_replace('?WSDL', '', $this->endpoint('wsfe')),
        ]);
    }

    private function endpoint(string $servicio): string
    {
        return $this->cfg['endpoints'][$this->cfg['ambiente']][$servicio];
    }

    private function guard(): void
    {
        if (! $this->disponible()) {
            throw new RuntimeException('AFIP WSFE no está configurado (revisá config/afip.php: cuit, cert, key y ext-soap).');
        }
    }
}
