<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Authenticator\AuthController;
use App\Http\Controllers\Commerce\OrderController as CommerceOrderController;
use App\Http\Controllers\Commerce\ProductController;
use App\Http\Controllers\Profiles\ProfileController;
use App\Http\Controllers\Profiles\DocumentController;
use App\Http\Controllers\Profiles\AddressController;
use App\Http\Controllers\Profiles\PhoneController;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\WebSocket\WebSocketController;
use App\Http\Controllers\BroadcastingController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\PaymentGatewayController;

// Endpoint público de salud
Route::get('/ping', fn() => response()->json(['message' => 'API funcionando']));

// Broadcasting auth route (for Laravel Broadcasting) - requiere autenticación
Route::post('/broadcasting/auth', [BroadcastingController::class, 'authenticate'])->middleware('auth:sanctum');

// Rutas públicas para órdenes (sin autenticación para tests)
// (Desactivado) Estas rutas deben ser protegidas, los tests ya validan buyer con token
// Route::get('/orders', [BuyerOrderController::class, 'index']);
// Route::post('/orders', [BuyerOrderController::class, 'store']);
// Route::get('/buyer/orders/{id}', [\App\Http\Controllers\Buyer\OrderController::class, 'show']);

Route::prefix('auth')->group(function () {
    Route::post('/google', [AuthController::class, 'googleUser']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'getUser']);
        Route::put('/user', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
        Route::post('/refresh', [AuthController::class, 'refreshToken']);
    });
});

// WebSocket routes
Route::prefix('websocket')->group(function () {
    Route::post('/connect', [WebSocketController::class, 'connect']);
    Route::post('/disconnect', [WebSocketController::class, 'disconnect']);
    Route::post('/subscribe', [WebSocketController::class, 'subscribe']);
    Route::post('/unsubscribe', [WebSocketController::class, 'unsubscribe']);
    Route::post('/auth', [WebSocketController::class, 'authenticate']);
});


// Buyer routes (no-MVP eliminadas)
// MVP: Endpoints mínimos adicionales
Route::post('/products/{id}/images', [ProductImageController::class, 'store'])->middleware('auth:sanctum');
Route::post('/webhooks/{provider}', [WebhookController::class, 'handle']);
Route::prefix('referrals')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [ReferralController::class, 'store']);
    Route::get('/', [ReferralController::class, 'index']);
    Route::get('/stats', [ReferralController::class, 'stats']);
});

// Checkout y Pagos (MVP)
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('auth:sanctum');
Route::post('/payments/{provider}', [PaymentGatewayController::class, 'apiPayment'])->middleware('auth:sanctum');
Route::post('/payments/comprobante', [PaymentGatewayController::class, 'comprobante'])->middleware('auth:sanctum');

// Rutas mínimas para buyers (requeridas por tests)
Route::middleware(['auth:sanctum'])->prefix('buyer')->group(function () {
    // Cart
    Route::post('/cart/add', [\App\Http\Controllers\Buyer\CartController::class, 'add']);
    Route::get('/cart', [\App\Http\Controllers\Buyer\CartController::class, 'show']);

    // Orders
    Route::get('/orders', [BuyerOrderController::class, 'index']);
    Route::post('/orders', [BuyerOrderController::class, 'store']);

    // Products
    Route::get('/products', [\App\Http\Controllers\Buyer\ProductController::class, 'index']);
    Route::get('/products/{id}', [\App\Http\Controllers\Buyer\ProductController::class, 'show']);
});

// Métodos de pago unificados (no-MVP eliminados)

// Commerce routes
Route::prefix('commerce')->middleware(['auth:sanctum', 'role:commerce'])->group(function () {
    Route::get('/orders', [CommerceOrderController::class, 'index']);
    Route::get('/orders/{order}', [CommerceOrderController::class, 'show']);
    Route::put('/orders/{order}/status', [CommerceOrderController::class, 'updateStatus']);
});

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('onboarding')->group(function () {
        Route::put('/{id}', [AuthController::class, 'update']);
    });

    // Perfil
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

     Route::prefix('profiles')->group(function () {
        Route::get('/', [ProfileController::class, 'index']);
        Route::post('/', [ProfileController::class, 'store']);
        // Rutas no-MVP removidas: delivery-agent, commerce (legacy), delivery-company
        Route::get('/{id}', [ProfileController::class, 'show']);
        Route::post('/{id}', [ProfileController::class, 'update']);
        Route::delete('/{id}', [ProfileController::class, 'destroy']);
    });


    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/', [DocumentController::class, 'store']);
        Route::get('/{id}', [DocumentController::class, 'show']);
        Route::put('/{id}', [DocumentController::class, 'update']);
        Route::delete('/{id}', [DocumentController::class, 'destroy']);
    });


    Route::prefix('addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index']);
        Route::post('/', [AddressController::class, 'store']);
        Route::get('/{id}', [AddressController::class, 'show']);
        Route::put('/{id}', [AddressController::class, 'update']);
        Route::delete('/{id}', [AddressController::class, 'destroy']);
        Route::post('/getCountries', [AddressController::class, 'getCountries']);
        Route::post('/get-states-by-country', [AddressController::class, 'getState']);
        Route::post('/get-cities-by-state', [AddressController::class, 'getCity']);
    });


    // Users (antes Buyer)
    // Buyer group (no-MVP eliminado)

    // Commerce (MVP)
    Route::prefix('commerce')->group(function () {
        Route::resource('/products', ProductController::class);
        Route::get('/orders', [CommerceOrderController::class, 'index']);
        Route::put('/orders/{id}/status', [CommerceOrderController::class, 'updateStatus']);
    });

    // Delivery (no-MVP eliminado)

    // Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::put('/users/{id}/role', [AdminUserController::class, 'updateRole']);
        Route::get('/commerces', [\App\Http\Controllers\Admin\AdminOrderController::class, 'commerces']);
        Route::get('/orders', [\App\Http\Controllers\Admin\AdminOrderController::class, 'index']);
        Route::patch('/orders/{id}/status', [\App\Http\Controllers\Admin\AdminOrderController::class, 'updateStatus']);
    });

    // Payment routes (no-MVP eliminadas)

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'getNotifications']);
        Route::post('/{notificationId}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::delete('/{notificationId}', [NotificationController::class, 'delete']);
    });

    // Location routes (no-MVP eliminadas)

    // Chat routes (no-MVP eliminadas)
});

// Endpoint público para listar bancos activos
Route::get('/banks', [\App\Http\Controllers\BankController::class, 'index']);


// // Ruta pública para pruebas
// Route::get('/ping', fn() => response()->json(['message' => 'API funcionando']));



// Route::get('/env-test', function () {
//     dd(env('APP_NAME'), env('DB_DATABASE'), env('APP_DEBUG'));
// });


// Route::get('/migrate-refresh', function () {
//     Artisan::call('migrate:refresh', ['--seed' => true]);
//     return 'Database migration refreshed and seeded successfully!';
// });



// Route::prefix('auth')->group(function () {
//     Route::post('/google', [AuthController::class, 'googleUser']);
//     Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
//     Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'getUser']);
// });

// Route::post('/orders', [OrderController::class, 'store'])->middleware(['auth:sanctum', 'role:comprador', 'commerce.open']);

// Route::middleware('auth:sanctum')->group(function () {

//     Route::prefix('onboarding')->group(function () {
//         Route::put('/{id}', [AuthController::class, 'update']);
//     });

//     Route::prefix('profiles')->group(function () {
//         Route::get('/', [ProfileController::class, 'index']);
//         Route::post('/', [ProfileController::class, 'store']);
//         Route::get('/{id}', [ProfileController::class, 'show']);
//         Route::post('/{id}', [ProfileController::class, 'update']);
//         Route::delete('/{id}', [ProfileController::class, 'destroy']);
//     });


//     // En routes/api.php
// Route::prefix('commerce')->group(function () {

//     // Productos del comercio
//     Route::get('/products', [ProductController::class, 'index']);
//     Route::post('/products', [ProductController::class, 'store']);
//     Route::get('/products/{id}', [ProductController::class, 'show']);
//     Route::put('/products/{id}', [ProductController::class, 'update']);
//     Route::delete('/products/{id}', [ProductController::class, 'destroy']);

//     // Nuevas funcionalidades
//     Route::put('/products/{id}/toggle-disponible', [ProductController::class, 'toggleDisponible']);
//     Route::get('/products-stats', [ProductController::class, 'estadisticas']);

// });



//      /**
//      * Buyer
//      */
//     Route::prefix('buyer')->group(function () {
//         Route::get('/orders', [OrderController::class, 'orders']);
//         Route::post('/orders', [OrderController::class, 'placeOrder']);
//     });

//     /**
//      * Commerce (Dueño del restaurante)
//      */
//     Route::prefix('commerce')->group(function () {
//         Route::get('/products', [ProductController::class, 'products']);
//         Route::post('/products', [ProductController::class, 'storeProduct']);
//         Route::get('/orders', [CommerceOrderController::class, 'orders']);
//         Route::post('/orders/{id}/status', [CommerceOrderController::class, 'updateOrderStatus']);
//     });

//     /**
//      * Delivery
//      */
//     Route::prefix('delivery')->group(function () {
//         Route::get('/available-orders', [OrderController::class, 'availableOrders']);
//         Route::post('/orders/{id}/accept', [OrderController::class, 'acceptOrder']);
//         Route::post('/orders/{id}/deliver', [OrderController::class, 'deliverOrder']);
//     });

//     /**
//      * Admin
//      */
//     Route::prefix('admin')->group(function () {
//         // Usuarios
//         Route::get('/users', [UserController::class, 'index']);
//         Route::get('/users/{id}', [UserController::class, 'show']);
//         Route::put('/users/{id}/role', [UserController::class, 'updateRole']);
//         Route::delete('/users/{id}', [UserController::class, 'destroy']);

//         // Comercios
//         Route::get('/commerces', [CommerceController::class, 'index']);
//         Route::put('/commerces/{id}/status', [CommerceController::class, 'updateStatus']);
//     });


// });

// Rutas de prueba (eliminadas para MVP)
