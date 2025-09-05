<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Arepas', 'description' => 'Tradicionales arepas venezolanas rellenas de diversos ingredientes.'],
            ['name' => 'Empanadas', 'description' => 'Empanadas de maíz fritas, típicas en desayunos y meriendas.'],
            ['name' => 'Pizzas', 'description' => 'Pizzas artesanales y comerciales, con variedad de ingredientes.'],
            ['name' => 'Hamburguesas', 'description' => 'Hamburguesas al estilo venezolano, con salsas y papas.'],
            ['name' => 'Perros Calientes', 'description' => 'Hot dogs con toppings criollos: papitas, salsas y más.'],
            ['name' => 'Pollo Frito', 'description' => 'Pollo frito crujiente, pieza clave de la comida rápida local.'],
            ['name' => 'Comida China', 'description' => 'Arroz frito, lumpias y platos chinos adaptados al gusto venezolano.'],
            ['name' => 'Comida Criolla', 'description' => 'Pabellón, asado negro, caraotas y platos típicos venezolanos.'],
            ['name' => 'Parrillas', 'description' => 'Carnes a la parrilla, chorizos, morcillas y guarniciones.'],
            ['name' => 'Sushi', 'description' => 'Sushi y comida japonesa, con opciones tradicionales y fusión.'],
            ['name' => 'Cachapas', 'description' => 'Cachapas de maíz tierno con queso de mano y rellenos variados.'],
            ['name' => 'Pastas', 'description' => 'Pastas italianas y pastichos, muy populares en Venezuela.'],
            ['name' => 'Ensaladas', 'description' => 'Ensaladas frescas, César, de gallina y más.'],
            ['name' => 'Jugos Naturales', 'description' => 'Jugos de frutas tropicales: parchita, guanábana, mango, etc.'],
            ['name' => 'Bebidas Gaseosas', 'description' => 'Refrescos y sodas populares como Frescolita, Pepsi, Coca-Cola.'],
            ['name' => 'Cervezas', 'description' => 'Cervezas nacionales e importadas: Polar, Zulia, Regional, etc.'],
            ['name' => 'Helados', 'description' => 'Helados artesanales e industriales, paletas y barquillas.'],
            ['name' => 'Postres', 'description' => 'Dulces criollos, tortas, quesillos y golosinas.'],
            ['name' => 'Cafés', 'description' => 'Café venezolano, espresso, marrón, guayoyo y más.'],
            ['name' => 'Té y Aromáticas', 'description' => 'Tés calientes y fríos, infusiones naturales y aromáticas.'],
            ['name' => 'Sandwiches', 'description' => 'Sandwiches variados, club house, pepitos y más.'],
            ['name' => 'Panadería', 'description' => 'Pan canilla, campesino, dulces y productos de panadería.'],
            ['name' => 'Charcutería', 'description' => 'Quesos, jamones, embutidos y productos de charcutería.'],
            ['name' => 'Comida Mexicana', 'description' => 'Tacos, burritos, nachos y platos mexicanos populares.'],
            ['name' => 'Comida Italiana', 'description' => 'Pastas, pizzas, lasañas y gastronomía italiana.'],
            ['name' => 'Comida Árabe', 'description' => 'Shawarma, falafel, kibbe y platos árabes.'],
            ['name' => 'Comida Vegetariana', 'description' => 'Opciones vegetarianas y saludables.'],
            ['name' => 'Comida Vegana', 'description' => 'Platos 100% vegetales, sin ingredientes de origen animal.'],
            ['name' => 'Marisquería', 'description' => 'Pescados y mariscos frescos, ceviches y cocteles.'],
            ['name' => 'Pescados', 'description' => 'Pescados preparados al gusto local.'],
            ['name' => 'Churros', 'description' => 'Churros rellenos y tradicionales, dulces populares.'],
            ['name' => 'Chocolatería', 'description' => 'Bombones, tabletas y postres de chocolate venezolano.'],
            ['name' => 'Bebidas Energéticas', 'description' => 'Bebidas energizantes y rehidratantes.'],
            ['name' => 'Bebidas Alcohólicas', 'description' => 'Licores, ron, whisky y otras bebidas alcohólicas.'],
            ['name' => 'Bodegón', 'description' => 'Productos importados, snacks, golosinas y bebidas.'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }
    }
}
