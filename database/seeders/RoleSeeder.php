<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'description' => 'Administrador del sistema',
                'permissions' => [
                    'manage_users',
                    'manage_orders',
                    'manage_commerces',
                    'manage_delivery',
                    'view_reports',
                    'manage_system'
                ]
            ],
            [
                'name' => 'users',
                'description' => 'Usuario cliente',
                'permissions' => [
                    'place_orders',
                    'view_orders',
                    'manage_profile',
                    'write_reviews'
                ]
            ],
            [
                'name' => 'commerce',
                'description' => 'Comercio/Restaurante',
                'permissions' => [
                    'manage_products',
                    'manage_orders',
                    'view_reports',
                    'manage_profile'
                ]
            ],
            [
                'name' => 'delivery_company',
                'description' => 'Empresa de delivery',
                'permissions' => [
                    'manage_agents',
                    'view_delivery_orders',
                    'manage_profile'
                ]
            ],
            [
                'name' => 'delivery_agent',
                'description' => 'Repartidor',
                'permissions' => [
                    'accept_deliveries',
                    'update_delivery_status',
                    'view_assigned_orders'
                ]
            ],
            [
                'name' => 'delivery',
                'description' => 'Repartidor independiente',
                'permissions' => [
                    'accept_deliveries',
                    'update_delivery_status',
                    'view_assigned_orders'
                ]
            ]
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
} 