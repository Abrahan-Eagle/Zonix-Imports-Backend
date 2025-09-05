<?php

use App\Http\Controllers\Admin\CommerceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
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
use App\Http\Controllers\Buyer\RestaurantController;
use App\Http\Controllers\Buyer\CartController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\Commerce\DashboardController;
use App\Http\Controllers\Commerce\DeliveryRequestController;
use App\Http\Controllers\WebSocket\WebSocketController;
use App\Http\Controllers\Buyer\BuyerProfileController;
use App\Http\Controllers\BroadcastingController;
use App\Http\Controllers\Delivery\DeliveryController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Location\LocationController;
use App\Http\Controllers\Chat\ChatController;

// Broadcasting auth route (for Laravel Broadcasting) - requiere autenticación
Route::post('/broadcasting/auth', [BroadcastingController::class, 'authenticate'])->middleware('auth:sanctum');

// Rutas públicas para órdenes (sin autenticación para tests)
Route::get('/orders', [BuyerOrderController::class, 'index']);
Route::post('/orders', [BuyerOrderController::class, 'store']);
Route::get('/buyer/orders/{id}', [\App\Http\Controllers\Buyer\OrderController::class, 'show']);

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


// Buyer routes
Route::prefix('buyer')->middleware(['auth:sanctum', 'role:users'])->group(function () {
    Route::get('/profiles/{profile}', [BuyerProfileController::class, 'show']);
    Route::put('/profiles/{profile}', [BuyerProfileController::class, 'update']);
});

// Rutas para usuarios/buyers
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Rutas existentes...
    
    // Sistema de Pagos Avanzado
    Route::prefix('buyer/payments')->group(function () {
        Route::get('/methods', [App\Http\Controllers\Buyer\PaymentController::class, 'getPaymentMethods']);
        Route::post('/card', [App\Http\Controllers\Buyer\PaymentController::class, 'processCardPayment']);
        Route::post('/mobile', [App\Http\Controllers\Buyer\PaymentController::class, 'processMobilePayment']);
        Route::post('/paypal', [App\Http\Controllers\Buyer\PaymentController::class, 'processPayPalPayment']);
        Route::post('/mercadopago', [App\Http\Controllers\Buyer\PaymentController::class, 'processMercadoPagoPayment']);
        Route::post('/cash', [App\Http\Controllers\Buyer\PaymentController::class, 'confirmCashPayment']);
        Route::post('/refund', [App\Http\Controllers\Buyer\PaymentController::class, 'requestRefund']);
        Route::get('/receipt/{orderId}', [App\Http\Controllers\Buyer\PaymentController::class, 'getPaymentReceipt']);
        Route::get('/history', [App\Http\Controllers\Buyer\PaymentController::class, 'getPaymentHistory']);
        Route::get('/statistics', [App\Http\Controllers\Buyer\PaymentController::class, 'getPaymentStatistics']);
    });

    // Tracking de Pedidos
    Route::prefix('buyer/tracking')->group(function () {
        Route::get('/order/{orderId}', [App\Http\Controllers\Buyer\OrderTrackingController::class, 'getOrderStatus']);
        Route::get('/delivery-agent/{orderId}', [App\Http\Controllers\Buyer\OrderTrackingController::class, 'getDeliveryAgentLocation']);
        Route::put('/order/{orderId}/status', [App\Http\Controllers\Buyer\OrderTrackingController::class, 'updateOrderStatus']);
    });

    // Sistema de Calificaciones
    Route::prefix('buyer/reviews')->group(function () {
        Route::post('/restaurant', [App\Http\Controllers\Buyer\ReviewController::class, 'rateRestaurant']);
        Route::post('/delivery-agent', [App\Http\Controllers\Buyer\ReviewController::class, 'rateDeliveryAgent']);
        Route::get('/restaurant/{commerceId}', [App\Http\Controllers\Buyer\ReviewController::class, 'getRestaurantReviews']);
        Route::get('/delivery-agent/{agentId}', [App\Http\Controllers\Buyer\ReviewController::class, 'getDeliveryAgentReviews']);
    });

    // Sistema de Chat
    Route::prefix('buyer/chat')->group(function () {
        Route::get('/messages/{orderId}', [App\Http\Controllers\Buyer\ChatController::class, 'getChatMessages']);
        Route::post('/send', [App\Http\Controllers\Buyer\ChatController::class, 'sendMessage']);
        Route::post('/mark-read', [App\Http\Controllers\Buyer\ChatController::class, 'markAsRead']);
        Route::get('/unread/{orderId}', [App\Http\Controllers\Buyer\ChatController::class, 'getUnreadMessages']);
    });

    // Búsqueda y Filtros
    Route::prefix('buyer/search')->group(function () {
        Route::get('/restaurants', [App\Http\Controllers\Buyer\SearchController::class, 'searchRestaurants']);
        Route::get('/products', [App\Http\Controllers\Buyer\SearchController::class, 'searchProducts']);
        Route::get('/categories', [App\Http\Controllers\Buyer\SearchController::class, 'getCategories']);
        Route::get('/suggestions', [App\Http\Controllers\Buyer\SearchController::class, 'getSearchSuggestions']);
    });

    // Sistema de Promociones
    Route::prefix('buyer/promotions')->group(function () {
        Route::get('/active', [App\Http\Controllers\Buyer\PromotionController::class, 'getActivePromotions']);
        Route::get('/coupons', [App\Http\Controllers\Buyer\PromotionController::class, 'getAvailableCoupons']);
        Route::post('/validate-coupon', [App\Http\Controllers\Buyer\PromotionController::class, 'validateCoupon']);
        Route::post('/apply-coupon', [App\Http\Controllers\Buyer\PromotionController::class, 'applyCouponToOrder']);
        Route::get('/coupon-history', [App\Http\Controllers\Buyer\PromotionController::class, 'getCouponHistory']);
    });

    // Gestión de Direcciones
    Route::prefix('buyer/addresses')->group(function () {
        Route::get('/', [App\Http\Controllers\Buyer\AddressController::class, 'getUserAddresses']);
        Route::post('/', [App\Http\Controllers\Buyer\AddressController::class, 'createAddress']);
        Route::put('/{addressId}', [App\Http\Controllers\Buyer\AddressController::class, 'updateAddress']);
        Route::delete('/{addressId}', [App\Http\Controllers\Buyer\AddressController::class, 'deleteAddress']);
        Route::post('/{addressId}/default', [App\Http\Controllers\Buyer\AddressController::class, 'setDefaultAddress']);
        Route::get('/default', [App\Http\Controllers\Buyer\AddressController::class, 'getDefaultAddress']);
    });

    Route::prefix('phones')->group(function () {
        Route::get('/', [PhoneController::class, 'index']);
        Route::get('/operator-codes', [PhoneController::class, 'getOperatorCodes']);
        Route::post('/', [PhoneController::class, 'store']);
        Route::get('/{id}', [PhoneController::class, 'show']);
        Route::put('/{id}', [PhoneController::class, 'update']);
        Route::delete('/{id}', [PhoneController::class, 'destroy']);
    });



    // Sistema de Gamificación
    Route::prefix('buyer/gamification')->group(function () {
        Route::get('/points', [App\Http\Controllers\Buyer\GamificationController::class, 'getUserPoints']);
        Route::get('/rewards', [App\Http\Controllers\Buyer\GamificationController::class, 'getAvailableRewards']);
        Route::post('/redeem', [App\Http\Controllers\Buyer\GamificationController::class, 'redeemReward']);
        Route::get('/badges', [App\Http\Controllers\Buyer\GamificationController::class, 'getUserBadges']);
        Route::get('/leaderboard', [App\Http\Controllers\Buyer\GamificationController::class, 'getLeaderboard']);
        Route::get('/stats', [App\Http\Controllers\Buyer\GamificationController::class, 'getGamificationStats']);
    });

    // Sistema de Fidelización
    Route::prefix('buyer/loyalty')->group(function () {
        Route::get('/info', [App\Http\Controllers\Buyer\LoyaltyController::class, 'getLoyaltyInfo']);
        Route::get('/volume-discounts', [App\Http\Controllers\Buyer\LoyaltyController::class, 'getVolumeDiscounts']);
        Route::get('/referral-code', [App\Http\Controllers\Buyer\LoyaltyController::class, 'generateReferralCode']);
        Route::post('/apply-referral', [App\Http\Controllers\Buyer\LoyaltyController::class, 'applyReferralCode']);
        Route::get('/benefits-history', [App\Http\Controllers\Buyer\LoyaltyController::class, 'getBenefitsHistory']);
        Route::get('/stats', [App\Http\Controllers\Buyer\LoyaltyController::class, 'getLoyaltyStats']);
        Route::get('/upcoming-benefits', [App\Http\Controllers\Buyer\LoyaltyController::class, 'getUpcomingBenefits']);
    });







    // Funcionalidades Avanzadas de Usuario
    Route::prefix('user')->group(function () {
        // Historial de Actividad
        Route::get('/activity-history', [App\Http\Controllers\Buyer\ActivityController::class, 'getUserActivityHistory']);
        Route::get('/activity-stats', [App\Http\Controllers\Buyer\ActivityController::class, 'getActivityStats']);
        
        // Exportación de Datos
        Route::post('/export-data', [App\Http\Controllers\Buyer\ExportController::class, 'requestDataExport']);
        Route::get('/export-status/{exportId}', [App\Http\Controllers\Buyer\ExportController::class, 'getExportStatus']);
        Route::get('/download-export/{exportId}', [App\Http\Controllers\Buyer\ExportController::class, 'downloadExport']);
        Route::get('/export-history', [App\Http\Controllers\Buyer\ExportController::class, 'getExportHistory']);
        
        // Configuración de Privacidad
        Route::get('/privacy-settings', [App\Http\Controllers\Buyer\PrivacyController::class, 'getPrivacySettings']);
        Route::put('/privacy-settings', [App\Http\Controllers\Buyer\PrivacyController::class, 'updatePrivacySettings']);
        Route::get('/privacy-policy', [App\Http\Controllers\Buyer\PrivacyController::class, 'getPrivacyPolicy']);
        Route::get('/terms-of-service', [App\Http\Controllers\Buyer\PrivacyController::class, 'getTermsOfService']);
        
        // Eliminación de Cuenta
        Route::post('/request-deletion', [App\Http\Controllers\Buyer\AccountDeletionController::class, 'requestAccountDeletion']);
        Route::post('/confirm-deletion', [App\Http\Controllers\Buyer\AccountDeletionController::class, 'confirmAccountDeletion']);
        Route::delete('/cancel-deletion', [App\Http\Controllers\Buyer\AccountDeletionController::class, 'cancelDeletionRequest']);
        Route::get('/deletion-status', [App\Http\Controllers\Buyer\AccountDeletionController::class, 'getDeletionStatus']);
    });
});

// Métodos de pago unificados
Route::middleware(['auth:sanctum'])->prefix('payment-methods')->group(function () {
    Route::get('/', [\App\Http\Controllers\PaymentMethodController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\PaymentMethodController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\PaymentMethodController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\PaymentMethodController::class, 'destroy']);
    Route::patch('/{id}/default', [\App\Http\Controllers\PaymentMethodController::class, 'setDefault']);
});

// Métodos de pago disponibles
Route::get('/available-payment-methods', [\App\Http\Controllers\PaymentMethodController::class, 'getAvailableMethods']);

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
        Route::post('/delivery-agent', [ProfileController::class, 'createDeliveryAgent']);
        Route::post('/commerce', [ProfileController::class, 'createCommerce']);
        Route::post('/delivery-company', [ProfileController::class, 'createDeliveryCompany']);
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
    Route::middleware('role:users')->prefix('buyer')->group(function () {
        Route::get('/restaurants', [RestaurantController::class, 'index']);
        Route::get('/restaurants/{id}', [RestaurantController::class, 'show']);
        Route::post('/cart/add', [CartController::class, 'add']);
        Route::get('/cart', [CartController::class, 'show']);
        Route::put('/cart/update-quantity', [CartController::class, 'updateQuantity']);
        Route::delete('/cart/{productId}', [CartController::class, 'remove']);
        Route::post('/cart/notes', [CartController::class, 'addNotes']);
        Route::post('/orders', [BuyerOrderController::class, 'store']);
        Route::get('/orders', [BuyerOrderController::class, 'index']);
        Route::get('/products/{id}', [\App\Http\Controllers\Buyer\ProductController::class, 'show']);
        Route::get('/products', [\App\Http\Controllers\Buyer\ProductController::class, 'index']);
        Route::post('buyer/orders/{id}/comprobante', [\App\Http\Controllers\Buyer\OrderController::class, 'uploadComprobante']);
        
        // Rutas de órdenes
        Route::post('/orders/{id}/payment-proof', [BuyerOrderController::class, 'uploadPaymentProof']);
        Route::post('/orders/{id}/cancel', [BuyerOrderController::class, 'cancelOrder']);
        
        // Nuevas rutas para búsqueda y favoritos
        Route::get('/posts', [\App\Http\Controllers\Buyer\PostController::class, 'index']);
        Route::get('/posts/{id}', [\App\Http\Controllers\Buyer\PostController::class, 'show']);
        Route::post('/posts/{id}/favorite', [\App\Http\Controllers\Buyer\PostController::class, 'toggleFavorite']);
        Route::get('/favorites', [\App\Http\Controllers\Buyer\PostController::class, 'favorites']);
        
        // Rutas de tracking
        Route::get('/orders/{orderId}/tracking', [\App\Http\Controllers\Buyer\TrackingController::class, 'getOrderTracking']);
        Route::post('/orders/{orderId}/tracking/location', [\App\Http\Controllers\Buyer\TrackingController::class, 'updateDeliveryLocation']);
        
        // Rutas de chat
        Route::get('/orders/{orderId}/messages', [\App\Http\Controllers\ChatController::class, 'getMessages']);
        Route::post('/orders/{orderId}/messages', [\App\Http\Controllers\ChatController::class, 'sendMessage']);
        
        // Rutas de calificaciones
        Route::post('/reviews', [\App\Http\Controllers\ReviewController::class, 'store']);
        Route::get('/reviews/{reviewableId}/{reviewableType}', [\App\Http\Controllers\ReviewController::class, 'index']);
        Route::put('/reviews/{reviewId}', [\App\Http\Controllers\ReviewController::class, 'update']);
        Route::delete('/reviews/{reviewId}', [\App\Http\Controllers\ReviewController::class, 'destroy']);
        Route::get('/reviews/{reviewableId}/{reviewableType}/can-review', [\App\Http\Controllers\ReviewController::class, 'canReview']);
    });

    // Commerce
    Route::middleware('role:commerce')->prefix('commerce')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::resource('/products', ProductController::class);
        Route::get('/orders', [CommerceOrderController::class, 'index']);
        Route::put('/orders/{id}/status', [CommerceOrderController::class, 'updateStatus']);
        Route::post('/orders/{id}/validate-payment', [CommerceOrderController::class, 'validatePayment']);
        Route::post('/delivery/request', [DeliveryRequestController::class, 'store']);
        Route::post('commerce/orders/{id}/validar-comprobante', [\App\Http\Controllers\Commerce\OrderController::class, 'validarComprobante']);
    });

    // Delivery
    Route::middleware('role:delivery')->prefix('delivery')->group(function () {
        Route::get('/orders', [\App\Http\Controllers\Delivery\OrderController::class, 'index']);
        Route::put('/orders/{id}/accept', [\App\Http\Controllers\Delivery\OrderController::class, 'accept']);
        Route::patch('/orders/{id}/status', [\App\Http\Controllers\Delivery\OrderController::class, 'updateStatus']);
        
        // New delivery endpoints
        Route::get('/available-orders', [DeliveryController::class, 'getAvailableOrders']);
        Route::get('/assigned-orders/{deliveryAgentId}', [DeliveryController::class, 'getAssignedOrders']);
        Route::post('/orders/{orderId}/accept', [DeliveryController::class, 'acceptOrder']);
        Route::post('/location/update', [DeliveryController::class, 'updateLocation']);
        Route::get('/statistics/{deliveryAgentId}', [DeliveryController::class, 'getStatistics']);
        Route::post('/orders/{orderId}/report-issue', [DeliveryController::class, 'reportIssue']);
    });

    // Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::put('/users/{id}/role', [AdminUserController::class, 'updateRole']);
        Route::get('/reports', [AdminReportController::class, 'index']);
        // NUEVAS RUTAS PARA TESTS DE ADMIN
        Route::get('/commerces', [\App\Http\Controllers\Admin\AdminOrderController::class, 'commerces']);
        Route::get('/orders', [\App\Http\Controllers\Admin\AdminOrderController::class, 'index']);
        Route::patch('/orders/{id}/status', [\App\Http\Controllers\Admin\AdminOrderController::class, 'updateStatus']);
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::get('/methods', [PaymentController::class, 'getPaymentMethods']);
        Route::post('/methods', [PaymentController::class, 'addPaymentMethod']);
        Route::post('/process', [PaymentController::class, 'processPayment']);
        Route::get('/history', [PaymentController::class, 'getTransactionHistory']);
        Route::post('/{transactionId}/refund', [PaymentController::class, 'refundPayment']);
        Route::get('/statistics', [PaymentController::class, 'getPaymentStatistics']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'getNotifications']);
        Route::post('/{notificationId}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::delete('/{notificationId}', [NotificationController::class, 'delete']);
    });

    // Location routes
    Route::prefix('location')->group(function () {
        Route::post('/update', [LocationController::class, 'updateLocation']);
        Route::get('/nearby-places', [LocationController::class, 'getNearbyPlaces']);
        Route::get('/delivery-routes', [LocationController::class, 'getDeliveryRoutes']);
        Route::post('/calculate-route', [LocationController::class, 'calculateRoute']);
        Route::post('/geocode', [LocationController::class, 'getCoordinatesFromAddress']);
        Route::get('/delivery-zones', [LocationController::class, 'getDeliveryZones']);
    });

    // Chat routes
    Route::prefix('chat')->group(function () {
        Route::get('/conversations', [ChatController::class, 'getConversations']);
        Route::get('/conversations/{conversationId}/messages', [ChatController::class, 'getMessages']);
        Route::post('/conversations/{conversationId}/messages', [ChatController::class, 'sendMessage']);
        Route::post('/conversations/{conversationId}/read', [ChatController::class, 'markMessagesAsRead']);
        Route::post('/conversations', [ChatController::class, 'createConversation']);
        Route::delete('/conversations/{conversationId}', [ChatController::class, 'deleteConversation']);
        Route::get('/search', [ChatController::class, 'searchMessages']);
        Route::post('/block', [ChatController::class, 'blockUser']);
        Route::delete('/block/{userId}', [ChatController::class, 'unblockUser']);
        Route::get('/blocked-users', [ChatController::class, 'getBlockedUsers']);
    });
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

// Ruta pública para pruebas
Route::get('/ping', fn() => response()->json(['message' => 'API funcionando']));

// Ruta de prueba para productos sin autenticación
Route::get('/test/products', function() {
    $products = \App\Models\Product::where('disponible', true)->get();
    return response()->json($products);
});

// Ruta de prueba para verificar autenticación y rol
Route::get('/test/auth', function() {
    if (!Auth::check()) {
        return response()->json(['error' => 'No autenticado'], 401);
    }
    
    $user = Auth::user();
    return response()->json([
        'authenticated' => true,
        'user_id' => $user->id,
        'user_role' => $user->role,
        'user_email' => $user->email,
        'token_valid' => true
    ]);
})->middleware('auth:sanctum');
