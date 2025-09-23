<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/** @var \App\Models\User $user */
$user = App\Models\User::firstOrCreate(['email' => 'seller@test.com'], ['name' => 'Test Seller', 'role' => 'seller']);
$user->role = 'seller';
$user->save();
if (!$user->profile) {
    App\Models\Profile::firstOrCreate(['user_id' => $user->id], ['firstName' => 'Test', 'lastName' => 'Seller']);
}

// Asegurar comercio 1:1 para el seller
$profile = $user->profile ?: App\Models\Profile::where('user_id', $user->id)->first();
if ($profile) {
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
}

echo $user->createToken('cli')->plainTextToken;
echo "\n";

