<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Electrónicos', 'description' => 'Dispositivos electrónicos, smartphones, computadoras y accesorios.'],
            ['name' => 'Ropa y Accesorios', 'description' => 'Ropa para hombre, mujer y niños, zapatos, bolsos y accesorios.'],
            ['name' => 'Hogar y Jardín', 'description' => 'Muebles, decoración, electrodomésticos y artículos para el hogar.'],
            ['name' => 'Deportes y Fitness', 'description' => 'Equipos deportivos, ropa deportiva, suplementos y accesorios.'],
            ['name' => 'Salud y Belleza', 'description' => 'Productos de cuidado personal, cosméticos, suplementos y salud.'],
            ['name' => 'Libros y Educación', 'description' => 'Libros, material educativo, cursos y recursos de aprendizaje.'],
            ['name' => 'Juguetes y Juegos', 'description' => 'Juguetes para niños, juegos de mesa, videojuegos y entretenimiento.'],
            ['name' => 'Automotriz', 'description' => 'Repuestos, accesorios, herramientas y productos para vehículos.'],
            ['name' => 'Alimentos y Bebidas', 'description' => 'Productos alimenticios, bebidas, snacks y productos gourmet.'],
            ['name' => 'Mascotas', 'description' => 'Alimentos, juguetes, accesorios y productos para mascotas.'],
            ['name' => 'Arte y Manualidades', 'description' => 'Materiales artísticos, manualidades, pinturas y herramientas creativas.'],
            ['name' => 'Herramientas', 'description' => 'Herramientas manuales, eléctricas, equipos de construcción y bricolaje.'],
            ['name' => 'Viajes y Turismo', 'description' => 'Equipaje, accesorios de viaje, guías y productos turísticos.'],
            ['name' => 'Oficina y Negocios', 'description' => 'Suministros de oficina, equipos de trabajo y productos empresariales.'],
            ['name' => 'Bebés y Niños', 'description' => 'Productos para bebés, pañales, ropa infantil y artículos de cuidado.'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }
    }
}
