# Qué probar con Docker (E4 y V4) — checklist exacta

En la máquina de desarrollo **no hay Docker** y GitHub Actions está bloqueado por facturación (08/11/2026), así que la imagen **nunca se construyó**. El 09/11/2026 se reprodujo a mano **todo lo que la imagen hace con PHP**; queda por probar solo lo propio de Docker.

## Ya verificado (sin Docker, 09/11/2026)
Copia limpia del repo (`git archive`, sin `vendor` ni `.env`, como `COPY . .` con `.dockerignore`) + `public/build` de `npm run build`:
1. `composer install --no-dev --no-scripts --optimize-autoloader` → ok (115 paquetes; `laravel/pail` y `phpunit` **no** quedan instalados).
2. `composer dump-autoload --optimize --no-dev` (incluye `package:discover`) → ok, 8.331 clases.
3. Con las variables de producción y un Mongo local con replica set: `php artisan mongo:indices` (71 índices) → `sistema:instalar` (crea el administrador) → `config:cache` → `route:cache` → `view:cache` → servidor: **`/up` 200 y `/login` 200 con formulario**. Ningún comando necesitó un paquete de desarrollo.
4. `render.yaml`, `render.demo.yaml` y los workflows parsean como YAML; `render.yaml` tiene 1 servicio, sin bloque `databases` y `healthCheckPath: /up`. `sh -n` sobre `docker/entrypoint.sh` y `scripts/*.sh`: sintaxis ok.
5. `.dockerignore` ahora excluye `instancias/`, `backups/` y `docs/` (antes una carpeta con las variables de un cliente habría entrado en la imagen).
6. Revisión del `Dockerfile` línea por línea: etapa `assets` (node 22, `npm ci --ignore-scripts`, `npm run build`), etapa `app` (FrankenPHP + `install-php-extensions mongodb gd zip bcmath intl opcache`), capa de `vendor` cacheada por manifiestos, `setcap -r` para que corra sin capabilities en Render, `ENTRYPOINT entrypoint`. No se encontraron errores. **No hay hadolint ni shellcheck instalados**: no hubo lint automático.

## Falta probar con Docker (cualquier máquina con Docker, ~15 min)
```bash
docker build -t crud-erp .
docker image ls crud-erp
./scripts/nueva-instancia.sh prueba tu@mail.com --local
```
`--local` hace `docker compose up -d --build` (app + Mongo 7 con replica set) y espera hasta 3 min a que `/up` dé 200. Después, a mano:
- [ ] `docker compose -p erp-prueba logs app` muestra «Verificando MongoDB», «71 índices», «Preparando la instalación», «Administrador … creado», «Iniciando servidor».
- [ ] `curl -i http://localhost:8080/up` → 200; `curl -i http://localhost:8080/login` → 200 con `X-Frame-Options` y `Content-Security-Policy`.
- [ ] Entrar con el correo y la contraseña de `instancias/prueba/.env` (el compose corre `APP_ENV=local`: no pide 2FA ni cookie segura).
- [ ] `docker compose -p erp-prueba exec app php artisan backup:crear` crea un respaldo.
- [ ] `docker compose -p erp-prueba restart app`: **no** vuelve a sembrar ni pisa el administrador.
- [ ] Limpiar: `docker compose -p erp-prueba down -v` y borrar `instancias/prueba`.

Anotar en `PLAN-VENTA-10K.md` (E4 y V4): fecha, versión de Docker, tamaño de la imagen y tiempo total desde cero.

Si GitHub Actions se destraba, el job **«Imagen Docker»** de `.github/workflows/ci.yml` hace el `docker build`, levanta Mongo con replica set y espera `/up` 200 en cada push: con ese job en verde, E4 queda probado sin hacer nada a mano (V4 `--local` sigue pidiendo la corrida de arriba).

## Riesgos conocidos al construir
- `composer install` dentro de la imagen resuelve contra el PHP de FrankenPHP: si falla por una extensión, agregarla a `install-php-extensions`.
- `setcap -r` está con `|| true`: si la imagen base cambia la ubicación del binario, el contenedor puede fallar en Render con `Operation not permitted` (exit 126); ver el comentario del Dockerfile.
- El plan Free de Render no tiene *Shell*: el rescate `usuario:quitar-doble-factor` exige un plan de pago.
