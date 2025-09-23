<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Ensure seller exists and has commerce
$user = App\Models\User::firstOrCreate(['email' => 'seller@test.com'], ['name' => 'Test Seller']);
$profile = $user->profile ?: App\Models\Profile::firstOrCreate(['user_id' => $user->id], ['firstName' => 'Test', 'lastName' => 'Seller']);
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

echo $commerce->id, "\n";

