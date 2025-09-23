<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;

// Ensure seller with commerce
$seller = User::firstOrCreate(['email' => 'seller@test.com'], ['name' => 'Test Seller']);
$sellerProfile = $seller->profile ?: Profile::firstOrCreate(['user_id' => $seller->id], ['firstName' => 'Test', 'lastName' => 'Seller']);
$commerce = Commerce::firstOrCreate(
    ['profile_id' => $sellerProfile->id],
    [
        'business_name' => 'Demo Commerce',
        'business_type' => 'retail',
        'rif' => 'J-' . strtoupper(bin2hex(random_bytes(4))),
        'is_verified' => true,
        'payment_methods' => ['zelle' => true],
        'open' => true,
    ]
);

// Ensure product under seller commerce
$product = Product::firstOrCreate(
    ['commerce_id' => $commerce->id, 'name' => 'Demo Product'],
    [
        'category_id' => null,
        'sku' => Str::upper(Str::random(8)),
        'description' => 'Producto demo para pedido',
        'modality' => 'retail',
        'base_price' => 10.00,
        'stock' => 100,
        'available' => true,
        'image' => null,
    ]
);

// Ensure buyer
$buyer = User::firstOrCreate(['email' => 'buyer@test.com'], ['name' => 'Test Buyer']);
$buyerProfile = $buyer->profile ?: Profile::firstOrCreate(['user_id' => $buyer->id], ['firstName' => 'Test', 'lastName' => 'Buyer']);

// Create order belonging to seller commerce
$order = Order::create([
    'profile_id' => $buyerProfile->id,
    'commerce_id' => $commerce->id,
    'modality' => 'retail',
    'delivery_type' => 'pickup',
    'status' => 'pending_payment',
    'subtotal' => 20.00,
    'discount_total' => 0.00,
    'shipping_total' => 0.00,
    'total' => 20.00,
]);

OrderItem::create([
    'order_id' => $order->id,
    'product_id' => $product->id,
    'quantity' => 2,
    'unit_price' => 10.00,
    'subtotal' => 20.00,
]);

echo $order->id, "\n";

