# 🚀 Zonix Imports - Backend (Laravel 10)

API REST completa para e-commerce multi-modal en Venezuela. Sistema de marketplace descentralizado con múltiples métodos de pago, chat en tiempo real, notificaciones push y programa de referidos.

## 📋 Tabla de Contenidos

- [Características Principales](#características-principales)
- [Tecnologías](#tecnologías)
- [Instalación](#instalación)
- [Arquitectura](#arquitectura)
- [Endpoints API](#endpoints-api)
- [Modelo de Datos](#modelo-de-datos)
- [Testing](#testing)
- [Seguridad](#seguridad)

## ✨ Características Principales

### 🛍️ E-commerce Multi-Modal
- **Detal**: Venta unitaria con stock disponible
- **Mayor**: Cantidad mínima, precio mayorista
- **Pre-order**: Con sistema de abonos avanzado
- **Referidos**: Programa completo multinivel
- **Dropshipping**: Automatizado con liquidación

### 💳 Pagos Descentralizados
- **APIs**: Stripe, PayPal, Binance Pay
- **Manuales**: Pago Móvil, Zelle (con OCR)
- **Webhooks**: Idempotentes con firma
- **Multi-moneda**: USD, VES, USDT

### 💬 Comunicación
- **Chat en tiempo real**: Pusher (comprador ↔ vendedor)
- **Notificaciones**: Pusher Beams + In-App
- **Email**: Eventos críticos

### ⭐ Social & Reviews
- **Sistema de Calificaciones**: Intermedio
  - Rating + comentarios
  - Subir fotos
  - Respuesta del vendedor
  - Reportar reviews

### 🎁 Programa de Referidos
- Links únicos por producto/tienda
- Sistema multinivel
- Dashboard de ganancias
- Códigos de descuento personalizados
- Ranking y bonos

### 🔍 Búsqueda Avanzada
- Autocompletado
- Filtros combinados
- Búsqueda semántica
- Historial personalizado

### 🔒 Seguridad
- KYC para vendedores
- Verificación de documentos
- Anti-fraude (pagos duplicados)
- Blacklist de usuarios
- Rate limiting

## 🛠️ Tecnologías

- **Framework**: Laravel 10
- **PHP**: 8.2+
- **Base de datos**: MySQL 8
- **Cache**: Redis
- **Queue**: Redis
- **Websockets**: Pusher
- **Notificaciones**: Pusher Beams
- **Pagos**: Stripe SDK, PayPal SDK, Binance API
- **OCR**: Google Cloud Vision / Tesseract
- **Storage**: S3 / Local
- **Testing**: PHPUnit, Pest

## 📦 Instalación

### Requisitos
- PHP 8.2+
- Composer 2.x
- MySQL 8.x
- Redis
- Node.js 18+ (para Laravel Echo Server)

### Pasos

```bash
# 1. Clonar repositorio
git clone https://github.com/Abrahan-Eagle/Zonix-Imports-Backend.git
cd Zonix-Imports-Backend

# 2. Instalar dependencias
composer install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar base de datos en .env
DB_DATABASE=zonix
DB_USERNAME=root
DB_PASSWORD=

# 5. Configurar Pusher en .env
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=

# 6. Configurar servicios de pago
STRIPE_SECRET=
PAYPAL_CLIENT_ID=
PAYPAL_SECRET=
BINANCE_PAY_KEY=
BINANCE_PAY_SECRET=

# 7. Ejecutar migraciones
php artisan migrate --seed

# 8. Enlazar storage
php artisan storage:link

# 9. Iniciar servidor
php artisan serve

# 10. Iniciar queue worker
php artisan queue:work

# 11. Iniciar websockets (opcional)
php artisan websockets:serve
```

## 🏗️ Arquitectura

### Estructura de Capas

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/              # Endpoints públicos
│   │   ├── Buyer/            # Endpoints comprador
│   │   ├── Commerce/         # Endpoints vendedor
│   │   ├── Admin/            # Endpoints admin (post-MVP)
│   │   └── Authenticator/    # Auth
│   ├── Middleware/
│   └── Requests/             # Form Requests
├── Models/                   # Eloquent Models
├── Services/                 # Lógica de negocio
├── Events/                   # Eventos del sistema
├── Listeners/                # Listeners de eventos
├── Jobs/                     # Jobs de cola
└── Notifications/            # Notificaciones
```

### Patrones Implementados

- **Repository Pattern**: Abstracción de datos
- **Service Layer**: Lógica de negocio
- **Event-Driven**: Eventos y listeners
- **Queue Jobs**: Tareas asíncronas
- **Observer Pattern**: Modelos observables

## 🔌 Endpoints API

### Autenticación
```
POST   /api/auth/google          - Login con Google
POST   /api/auth/register         - Registro
POST   /api/auth/login            - Login email/password
POST   /api/auth/logout           - Cerrar sesión
GET    /api/auth/user             - Usuario actual
```

### Productos
```
GET    /api/buyer/products        - Listar productos
GET    /api/buyer/products/{id}   - Detalle producto
GET    /api/buyer/products/search - Búsqueda
POST   /api/commerce/products     - Crear producto (vendedor)
PUT    /api/commerce/products/{id} - Actualizar
DELETE /api/commerce/products/{id} - Eliminar
```

### Tiendas
```
GET    /api/commerces             - Listar tiendas
GET    /api/commerces/{id}        - Detalle tienda
GET    /api/commerces/{id}/products - Productos de tienda
GET    /api/my-commerce           - Mi tienda
PUT    /api/my-commerce/toggle    - Abrir/cerrar tienda
```

### Carrito
```
POST   /api/buyer/cart/add        - Agregar al carrito
GET    /api/buyer/cart            - Ver carrito
PUT    /api/cart/{item}           - Actualizar cantidad
DELETE /api/cart/{item}           - Eliminar item
```

### Checkout & Pagos
```
POST   /api/checkout              - Crear orden
GET    /api/payments/methods      - Métodos disponibles
POST   /api/payments/stripe       - Pago con Stripe
POST   /api/payments/paypal       - Pago con PayPal
POST   /api/payments/binance      - Pago con Binance
POST   /api/payments/comprobante  - Pago manual
POST   /api/webhooks/{provider}   - Webhooks
```

### Pedidos
```
GET    /api/buyer/orders          - Mis pedidos
GET    /api/buyer/orders/{id}     - Detalle pedido
GET    /api/buyer/orders/{id}/tracking - Tracking
GET    /api/commerce/orders       - Pedidos de mi tienda
PUT    /api/commerce/orders/{id}/status - Actualizar estado
```

### Reviews
```
POST   /api/products/{id}/reviews - Crear review
GET    /api/products/{id}/reviews - Listar reviews
PUT    /api/reviews/{id}          - Actualizar review
DELETE /api/reviews/{id}          - Eliminar review
POST   /api/reviews/{id}/report   - Reportar review
POST   /api/reviews/{id}/response - Responder (vendedor)
```

### Chat (Pusher)
```
POST   /api/chats                 - Iniciar chat
GET    /api/chats                 - Mis chats
GET    /api/chats/{id}/messages   - Mensajes
POST   /api/chats/{id}/messages   - Enviar mensaje
PUT    /api/chats/{id}/read       - Marcar leído
```

### Notificaciones
```
GET    /api/notifications         - Listar notificaciones
PUT    /api/notifications/{id}/read - Marcar leída
DELETE /api/notifications/{id}    - Eliminar
```

### Referidos
```
POST   /api/referrals             - Crear referido
GET    /api/referrals             - Mis referidos
GET    /api/referrals/stats       - Estadísticas
GET    /api/referrals/earnings    - Ganancias
POST   /api/referrals/withdraw    - Retirar comisiones
```

### Wishlist
```
POST   /api/wishlist              - Agregar a wishlist
GET    /api/wishlist              - Mi wishlist
DELETE /api/wishlist/{id}         - Eliminar
```

## 📊 Modelo de Datos

### Tablas Principales

#### Usuarios y Autenticación
- `users` - Usuarios del sistema
- `profiles` - Perfiles extendidos (1:1)
- `personal_access_tokens` - Tokens Sanctum

#### E-commerce
- `commerces` - Tiendas/Vendedores
- `categories` - Categorías
- `products` - Productos
- `product_images` - Imágenes de productos
- `cart_items` - Carrito
- `orders` - Pedidos
- `order_items` - Items de pedido

#### Pagos
- `payments` - Pagos
- `payment_methods` - Métodos de pago
- `processed_webhook_events` - Webhooks procesados
- `banks` - Bancos (Pago Móvil)
- `operator_codes` - Códigos de operadora

#### Social
- `reviews` - Calificaciones
- `review_images` - Imágenes en reviews
- `review_reports` - Reportes
- `wishlists` - Lista de deseos

#### Comunicación
- `chats` - Conversaciones
- `messages` - Mensajes
- `notifications` - Notificaciones

#### Referidos
- `referrals` - Programa de referidos
- `referral_earnings` - Ganancias
- `referral_withdrawals` - Retiros

#### Inventario
- `inventory_movements` - Movimientos de stock

#### Seguridad
- `documents` - Documentos KYC
- `blacklist` - Usuarios bloqueados
- `fraud_detections` - Detección de fraude

## 🧪 Testing

### Ejecutar Tests

```bash
# Todos los tests
php artisan test

# Con coverage
php artisan test --coverage

# Solo unit tests
php artisan test --testsuite=Unit

# Solo feature tests
php artisan test --testsuite=Feature

# Test específico
php artisan test --filter=ProductTest
```

### Estructura de Tests

```
tests/
├── Unit/
│   ├── Models/
│   ├── Services/
│   └── Helpers/
├── Feature/
│   ├── Auth/
│   ├── Products/
│   ├── Cart/
│   ├── Checkout/
│   ├── Payments/
│   ├── Orders/
│   ├── Reviews/
│   ├── Chat/
│   └── Referrals/
└── Integration/
    └── E2E/
```

## 🔒 Seguridad

### Implementado
- ✅ HTTPS obligatorio (TLS 1.2+)
- ✅ CORS configurado
- ✅ Rate limiting
- ✅ Sanctum para autenticación
- ✅ Validación de inputs
- ✅ SQL Injection prevention (Eloquent)
- ✅ XSS protection
- ✅ CSRF tokens
- ✅ Secrets en .env
- ✅ Webhook signature verification
- ✅ KYC para vendedores
- ✅ Anti-fraude básico

### Mejores Prácticas
- No exponer trazas en producción
- Logs sin datos sensibles
- Passwords hasheados (bcrypt)
- Tokens con expiración
- 2FA (opcional, post-MVP)

## 📈 Rendimiento

### Optimizaciones
- Paginación por defecto
- Eager loading (evitar N+1)
- Cache con Redis
- Índices en DB
- Queue jobs para tareas pesadas
- CDN para assets (producción)

### KPIs
- API pequeñas: ≤2s
- API grandes: ≤4s
- Paginación: 20 items por defecto
- Cache TTL: 1 hora (configurable)

## 🚀 Deployment

### Producción

```bash
# 1. Optimizar
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 2. Migraciones
php artisan migrate --force

# 3. Storage
php artisan storage:link

# 4. Supervisor para queues
supervisorctl start zonix-worker:*
```

## 📝 Contribución

### Workflow
1. Fork del repositorio
2. Crear branch: `git checkout -b feat/nueva-funcionalidad`
3. Commits convencionales: `feat(products): agregar filtro por precio`
4. Tests: `php artisan test`
5. Push: `git push origin feat/nueva-funcionalidad`
6. Pull Request con descripción clara

### Commits Convencionales
- `feat:` Nueva funcionalidad
- `fix:` Corrección de bug
- `refactor:` Refactorización
- `test:` Agregar/actualizar tests
- `docs:` Documentación
- `chore:` Tareas de mantenimiento

## 📄 Licencia

Propietario - Zonix Imports © 2025

## 🔗 Enlaces

- [Frontend Flutter](https://github.com/Abrahan-Eagle/Zonix-Imports-Frontend)
- [Documentación API](https://api.zonix.com/docs) (próximamente)
- [Postman Collection](./postman/) (próximamente)

---

**Desarrollado con ❤️ en Venezuela 🇻🇪**
