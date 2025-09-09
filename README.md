## Zonix Imports — Backend (Laravel 10)

API REST para el MVP de e‑commerce multi‑modal en Venezuela (detal, mayor, pre‑order con abonos, referidos, dropshipping). Pagos descentralizados por vendedor (Stripe, PayPal, Pago Móvil, Zelle, Binance Pay/USDT). Basado en Laravel 10 + MySQL 8.

### 0) Modelo de negocio (reglas en API)
- Detal: requiere stock; `precio_unitario`.
- Mayor: campos `min_mayor` y `precio_mayor`; validar cantidad mínima en carrito/checkout.
- Pre‑order: `preorden_entrega` (ETA); registrar abonos (pagos parciales) y saldo; entregar solo con 100% pago.
- Referidos: `referidos` con `porcentaje` y `link`; atribución por link; registrar comisiones.
- Dropshipping interno: referencia a `producto_origen`; validar stock del origen en compra; liquidación entre vendedores (reglas mínimas en MVP).

### 1) Requisitos
- PHP 8.2+
- Composer 2.x
- MySQL 8.x
- Extensiones: OpenSSL, PDO, Mbstring, Tokenizer, JSON, cURL, Fileinfo

### 2) Configuración e instalación
1. Copiar variables de entorno:
   - `cp .env.example .env`
2. Configurar `.env` (DB, APP_URL, CORS, SANCTUM, MAIL, STORAGE):
   - `APP_NAME=ZonixImports`
   - `APP_ENV=local`
   - `APP_KEY=` (se genera luego)
   - `APP_URL=http://localhost`
   - `DB_CONNECTION=mysql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=3306`
   - `DB_DATABASE=zonix`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=`
   - `SANCTUM_STATEFUL_DOMAINS=localhost`
   - `FRONTEND_URL=http://localhost:5173` (o la URL móvil si aplica)
   - `MAIL_MAILER=smtp` (configurar credenciales)
   - Proveedores de pago (según habilitados):
     - `STRIPE_SECRET=...`
     - `PAYPAL_CLIENT_ID=...`, `PAYPAL_SECRET=...`
     - `BINANCE_PAY_KEY=...`, `BINANCE_PAY_SECRET=...`
   - Webhooks (recomendado):
     - `STRIPE_WEBHOOK_SECRET=...`
     - `PAYPAL_WEBHOOK_ID=...`
     - `BINANCE_WEBHOOK_SECRET=...`
3. Instalar dependencias:
   - `composer install`
4. Generar clave app:
   - `php artisan key:generate`
5. Ejecutar migraciones y seeders:
   - `php artisan migrate --seed`
6. Storage link (archivos y comprobantes):
   - `php artisan storage:link`
7. Ejecutar servidor:
   - `php artisan serve`

### 3) Arquitectura
- Capas: `Controllers` delgados, `Services` para negocio, `FormRequest` para validación, `Policies/Middleware` para permisos.
- Autenticación: Laravel Sanctum (tokens) + OAuth2 Google (login) [controlador/servicio dedicado].
- Eventos/Listeners para: pedidos, pagos, notificaciones; webhooks idempotentes.
- Logging sin datos sensibles; manejo de errores con códigos HTTP claros.
 - Estándar de respuesta de error: `{ message, errors?, code }`

### 4) Modelo de datos (tablas clave)
- `usuarios`
- `productos`, `imagenes_producto`
- `pedidos`, `items_pedido`
- `pagos`
- `inventario_movimientos`
- `referidos`
- `notificaciones`

### 5) Endpoints API (mínimos)
Prefijo `/api`.
- POST `/auth/google` → login
- GET `/me` → perfil
- PUT `/me/rol` → cambio a vendedor (RIF/banco/dirección requeridos)
- CRUD `/productos` y GET `/productos?filtros...`
- POST `/carrito`, DELETE `/carrito/{item}`
- POST `/checkout`
- POST `/pagos/stripe|paypal|binance` → intentos de pago
- POST `/webhooks/{proveedor}` → confirmaciones (idempotentes)
- POST `/pagos/comprobante` → flujo manual Pago Móvil/Zelle
- GET `/pedidos` (del comprador)
- GET `/vendedor/pedidos`
- PUT `/vendedor/pedidos/{id}/estado`
- GET `/notificaciones`

### 6) Políticas y seguridad
- HTTPS (TLS 1.2+), CORS configurado
- Roles: comprador, vendedor, admin (Policies/Middleware)
- Validación con `FormRequest`; sanitización de entradas
- Rate limiting en auth y webhooks
- Secretos solo en `.env`
 - Validar firma de webhooks y prevenir reintentos (idempotencia por `event_id`)

### 7) Rendimiento
- Paginación obligatoria en listados grandes
- Eager loading para evitar N+1
- Cache básico en datos estáticos (categorías)
- Tiempos objetivo: ≤2s (pequeñas), ≤4s (grandes)
 - Seleccionar columnas necesarias; índices adecuados

### 8) Pagos (descentralizado)
- Métodos por vendedor; checkout muestra solo habilitados
- API: Stripe, PayPal, Binance Pay (webhooks marcan `pagado`)
- Manual: Pago Móvil, Zelle (subir comprobante → validación del vendedor → auditoría admin)
 - Guardar comprobantes en `storage/app/public/payments/` (nunca datos de tarjetas)

### 9) Notificaciones
- Internas y por correo en eventos clave (creación de pedido, cambio de estado, validación de pago, recordatorio de abonos)

### 10) Desarrollo y contribución
- Commits convencionales: `tipo(scope): resumen`
- PRs pequeñas con pruebas locales: `php artisan test`
- Sin secretos en el repo; enlazar evidencias de pruebas manuales en PRs
 - Estándar de ramas: `feat/*`, `fix/*`, `chore/*`, `docs/*`

### 11) Scripts útiles
- Ejecutar pruebas: `php artisan test`
- Ejecutar seeders: `php artisan db:seed`
- Refrescar DB: `php artisan migrate:fresh --seed`
- Limpiar caches: `php artisan optimize:clear`
 - Jobs/queues (si se usan): `php artisan queue:work`

### 12) Roadmap (MVP)
- S1: Infra + Auth
- S2: Productos, catálogo, carrito, checkout, pedidos (comprador)
- S3: Publicación, inventario, pedidos vendedor, pre‑order abonos
- S4: Admin mínimo, QA, deploy

### 13) Observaciones
- Webhooks deben ser idempotentes y validados con firma
- Logs sin PII ni datos de tarjetas; evitar almacenar comprobantes con información sensible
 - Respetar prefijo `/api` y paginación por defecto

### 14) Integración con Frontend (Flutter)
- `FRONTEND_URL` en `.env` para CORS.
- Autenticación: emitir tokens Sanctum; el cliente envía `Authorization: Bearer <token>`.
- Estructura de error: `{ message, errors?, code }`; `errors` es diccionario campo→mensajes.
- Paginación: devolver `{ data, meta: { current_page, per_page, total } }`.
- Fechas: ISO 8601 UTC; documentar TZ si difiere.
- Moneda: retornar decimales (no enteros escalados); frontend formatea.


