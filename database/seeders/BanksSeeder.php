<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bank;

class BanksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            ['name' => 'Banco de Venezuela', 'code' => '0102', 'type' => 'público'],
            ['name' => 'Banco del Tesoro', 'code' => '0163', 'type' => 'público'],
            ['name' => 'Banco Bicentenario', 'code' => '0175', 'type' => 'público'],
            ['name' => 'Banco Central de Venezuela', 'code' => '0001', 'type' => 'público'],
            ['name' => 'Banesco Banco Universal', 'code' => '0134', 'type' => 'privado'],
            ['name' => 'Banco Mercantil', 'code' => '0105', 'type' => 'privado'],
            ['name' => 'Banco Provincial', 'code' => '0108', 'type' => 'privado'],
            ['name' => 'Banco Exterior', 'code' => '0113', 'type' => 'privado'],
            ['name' => 'Banco Occidental de Descuento (BOD)', 'code' => '0116', 'type' => 'privado'],
            ['name' => 'Banco Caroní', 'code' => '0128', 'type' => 'privado'],
            ['name' => 'Banco Sofitasa', 'code' => '0137', 'type' => 'privado'],
            ['name' => 'Banco Plaza', 'code' => '0138', 'type' => 'privado'],
            ['name' => 'Banco Fondo Común', 'code' => '0151', 'type' => 'privado'],
            ['name' => '100% Banco', 'code' => '0156', 'type' => 'privado'],
            ['name' => 'Banco del Sur', 'code' => '0157', 'type' => 'privado'],
            ['name' => 'Banco Activo', 'code' => '0166', 'type' => 'privado'],
            ['name' => 'Bancaribe', 'code' => '0114', 'type' => 'privado'],
            ['name' => 'Banco Agrícola de Venezuela', 'code' => '0168', 'type' => 'público'],
            ['name' => 'Banco Venezolano de Crédito', 'code' => '0104', 'type' => 'privado'],
            ['name' => 'Banco Nacional de Crédito (BNC)', 'code' => '0191', 'type' => 'privado'],
            ['name' => 'Citibank', 'code' => '0190', 'type' => 'internacional'],
            ['name' => 'Banco Mi Casa', 'code' => '0169', 'type' => 'privado'],
            ['name' => 'Banco de la Fuerza Armada Nacional Bolivariana (BANFANB)', 'code' => '0177', 'type' => 'público'],
            ['name' => 'Banco Bancrecer', 'code' => '0164', 'type' => 'privado'],
            ['name' => 'Banco Venezolano de Crédito', 'code' => '0104', 'type' => 'privado'],
            ['name' => 'Banco Industrial de Venezuela', 'code' => '0106', 'type' => 'público'],
            ['name' => 'Banco Provincial', 'code' => '0108', 'type' => 'privado'],
            ['name' => 'Banco Guayana', 'code' => '0132', 'type' => 'privado'],
            ['name' => 'Banco Federal', 'code' => '0121', 'type' => 'privado'],
            ['name' => 'Banco Confederado', 'code' => '0129', 'type' => 'privado'],
        ];

        foreach ($banks as $bank) {
            \App\Models\Bank::updateOrCreate(['code' => $bank['code']], $bank);
        }
    }
} 