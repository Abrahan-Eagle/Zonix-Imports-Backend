<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Ensure seller and commerce
$user = App\Models\User::firstOrCreate(['email' => 'seller@test.com'], ['name' => 'Test Seller']);
if (!$user->profile) {
    App\Models\Profile::firstOrCreate(['user_id' => $user->id], ['firstName' => 'Test', 'lastName' => 'Seller']);
}
$profile = $user->profile ?: App\Models\Profile::where('user_id', $user->id)->first();
$commerce = App\Models\Commerce::firstOrCreate(
    ['profile_id' => $profile->id],
    [
        'business_name' => 'Demo Commerce',
        'business_type' => 'retail',
        'rif' => 'J-' . strtoupper(bin2hex(random_bytes(4))),
        'bank_account' => null,
        'is_verified' => true,
        'payment_methods' => json_encode(['zelle' => true, 'pago_movil' => true]),
        'open' => true,
        'schedule' => null,
    ]
);

// Ensure at least one category
$category = App\Models\Category::firstOrCreate(['name' => 'General'], ['description' => 'Categoría demo']);

// Ensure product under this commerce
$product = App\Models\Product::firstOrCreate(
    ['commerce_id' => $commerce->id, 'name' => 'Producto Seller Demo'],
    [
        'category_id' => $category->id,
        'sku' => 'DEMO-' . strtoupper(bin2hex(random_bytes(3))),
        'description' => 'Producto de prueba para demo seller',
        'modality' => 'retail',
        'base_price' => 10.00,
        'stock' => 100,
        'available' => true,
    ]
);

echo $product->id, "\n";


