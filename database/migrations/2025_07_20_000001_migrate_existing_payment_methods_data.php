<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrar datos de user_payment_methods
        $userPaymentMethods = DB::table('user_payment_methods')->get();
        foreach ($userPaymentMethods as $method) {
            DB::table('payment_methods')->insert([
                'payable_type' => 'App\\Models\\User',
                'payable_id' => $method->user_id,
                'bank_id' => $method->bank_id,
                'type' => $method->type,
                'brand' => $method->brand,
                'account_number' => $method->account_number,
                'phone' => $method->phone,
                'owner_name' => $method->owner_name,
                'owner_id' => $method->owner_id,
                'is_default' => $method->is_default,
                'is_active' => $method->is_active,
                'created_at' => $method->created_at,
                'updated_at' => $method->updated_at,
            ]);
        }

        // Migrar datos de delivery_payment_methods
        $deliveryPaymentMethods = DB::table('delivery_payment_methods')->get();
        foreach ($deliveryPaymentMethods as $method) {
            DB::table('payment_methods')->insert([
                'payable_type' => 'App\\Models\\DeliveryAgent',
                'payable_id' => $method->delivery_agent_id,
                'bank_id' => $method->bank_id,
                'type' => $method->type,
                'brand' => $method->brand,
                'account_number' => $method->account_number,
                'phone' => $method->phone,
                'owner_name' => $method->owner_name,
                'owner_id' => $method->owner_id,
                'is_default' => $method->is_default,
                'is_active' => $method->is_active,
                'created_at' => $method->created_at,
                'updated_at' => $method->updated_at,
            ]);
        }


    }

    public function down(): void
    {
        // Eliminar todos los datos migrados
        DB::table('payment_methods')->truncate();
    }
}; 