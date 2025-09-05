<?php

namespace Database\Seeders;

use App\Models\OperatorCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OperatorCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $operatorCodes = [
            ['id' => 1,'name' => '0412', 'code' => 412],
            ['id' => 2,'name' => '0414', 'code' => 414],
            ['id' => 3,'name' => '0424', 'code' => 424],
            ['id' => 4,'name' => '0416', 'code' => 416],
            ['id' => 5,'name' => '0426', 'code' => 426],
          ];

        // Insertar datos en la tabla country Code
        foreach ($operatorCodes as $operatorCode) {
            $varOperatorCode = OperatorCode::create($operatorCode);
            echo $varOperatorCode . "OperatorCode:";
        }

    }
}
