<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/** @var \App\Models\User $user */
$user = App\Models\User::firstOrCreate(['email' => 'buyer@test.com'], ['name' => 'Test Buyer']);
if (!$user->profile) {
    App\Models\Profile::firstOrCreate(['user_id' => $user->id], ['firstName' => 'Test', 'lastName' => 'Buyer']);
}

echo $user->createToken('cli')->plainTextToken;
echo "\n";

