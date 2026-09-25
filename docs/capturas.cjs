/**
 * Genera las capturas del README contra una instancia local del sistema.
 *
 *   php artisan migrate:fresh --seed
 *   php artisan db:seed --class=DemoSeeder
 *   php artisan serve
 *
 *   npm install playwright && npx playwright install chromium
 *   node docs/capturas.cjs
 *
 * Variables opcionales: BASE_URL, LOGIN_EMAIL, LOGIN_PASSWORD, OUT_DIR.
 */
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE = process.env.BASE_URL || 'http://localhost:8000';
const EMAIL = process.env.LOGIN_EMAIL || 'admin@admin.com';
const PASSWORD = process.env.LOGIN_PASSWORD || 'password';
const OUT = process.env.OUT_DIR || path.join(__dirname, 'screenshots');

const PANTALLAS = [
  { archivo: 'dashboard.png',          url: '/dashboard' },
  { archivo: 'venta-nueva.png',        url: '/ventas/crear' },
  { archivo: 'ventas.png',             url: '/ventas' },
  { archivo: 'productos.png',          url: '/productos' },
  { archivo: 'stock-depositos.png',    url: '/depositos' },
  { archivo: 'caja.png',               url: '/caja' },
  { archivo: 'cuenta-corriente.png',   url: '/cuentas/clientes' },
  { archivo: 'reporte-ganancias.png',  url: '/reportes/ganancias' },
  { archivo: 'reporte-periodo.png',    url: '/reportes/ventas-por-periodo?periodo=mensual' },
  { archivo: 'roles.png',              url: '/roles' },
];

(async () => {
  fs.mkdirSync(OUT, { recursive: true });

  const navegador = await chromium.launch();
  const contexto = await navegador.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 2,
    locale: 'es-AR',
  });
  const pagina = await contexto.newPage();

  await pagina.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
  await pagina.fill('input[type=email]', EMAIL);
  await pagina.fill('input[type=password]', PASSWORD);
  await Promise.all([
    pagina.waitForURL(/dashboard/, { timeout: 30000 }),
    pagina.click('button[type=submit]'),
  ]);

  for (const { archivo, url } of PANTALLAS) {
    const destino = path.join(OUT, archivo);
    try {
      const respuesta = await pagina.goto(BASE + url, { waitUntil: 'networkidle', timeout: 30000 });
      if (respuesta && respuesta.status() >= 400) {
        console.warn(`  !! ${url} devolvio ${respuesta.status()} — se omite ${archivo}`);
        continue;
      }
      // Las animaciones de entrada (GSAP) dejan los bloques a media opacidad.
      await pagina.waitForTimeout(1500);
      await pagina.screenshot({ path: destino });
      console.log(`  ok ${archivo}`);
    } catch (e) {
      console.warn(`  !! fallo ${archivo}: ${e.message}`);
    }
  }

  await navegador.close();
  console.log(`\nCapturas en ${OUT}`);
})();
