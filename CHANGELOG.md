## v0.1.0-mvp (2025-09-23)

### Resumen
- MVP backend listo (Laravel 10 + MySQL 8) con esquema alineado a `.cursorrules` y `README.md`.
- 80/80 tests pasando y 2 pruebas E2E completas (basadas en factories y en seeders).

### Destacados
- Autenticación: Sanctum + Google OAuth2.
- Productos y catálogo con modalidades: retail, wholesale, preorder (abonos), referral, dropshipping.
- Carrito persistente (`cart_items`) y checkout.
- Pagos: Stripe, PayPal, Binance (API) y Pago Móvil/Zelle (manual) con idempotencia de webhooks.
- Inventario: `inventory_movements` con trazabilidad.
- Direcciones, Teléfonos, Documentos, Notificaciones.
- Seeders base: categorías, bancos, operadoras, países/estados/ciudades.

### Cambios técnicos
- Migraciones actualizadas y consistentes con el MVP.
- Modelos y relaciones ajustadas (1:1 `users`↔`profiles`, 1:1 `profiles`↔`commerces`).
- Factories y Seeders creados/actualizados para modelos clave (Products, Orders, Payments, etc.).
- Tests de Feature y E2E añadidos:
  - `Tests/Feature/E2E/MvpEndToEndTest.php` (factories).
  - `Tests/Feature/E2E/MvpSeededEndToEndTest.php` (seeders + factories, múltiples modalidades y métodos de pago).
- `PaymentFactory` ajustada a estados válidos (`pending`, `succeeded`, `failed`, `refunded`, `cancelled`) y `processed_at` coherente.

### Comandos útiles
- Refrescar DB y seed: `php artisan migrate:fresh --seed`
- Ejecutar pruebas: `php artisan test`


