<?php

namespace Database\Factories;

use App\Models\Commerce;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $foodImages = [
            'https://www.themealdb.com/images/media/meals/wxywrq1468235067.jpg', // Apple Frangipan Tart
            'https://www.themealdb.com/images/media/meals/xvsurr1511719182.jpg', // Apple & Blackberry Crumble
            'https://www.themealdb.com/images/media/meals/adxcbq1619787919.jpg', // Apam balik
            'https://www.themealdb.com/images/media/meals/20z181619788503.jpg', // Ayam Percik
            'https://www.themealdb.com/images/media/meals/1550441275.jpg', // Baked salmon with fennel & tomatoes
            'https://www.themealdb.com/images/media/meals/1520084413.jpg', // Beef and Mustard Pie
            'https://www.themealdb.com/images/media/meals/1529446352.jpg', // Beef and Oyster pie
            'https://www.themealdb.com/images/media/meals/1550441883.jpg', // Beef Dumpling Stew
            'https://www.themealdb.com/images/media/meals/1529444830.jpg', // Beef Sunday Roast
            'https://www.themealdb.com/images/media/meals/1550441275.jpg', // Big Mac
            'https://www.themealdb.com/images/media/meals/1520084413.jpg', // Chicken & mushroom Hotpot
            'https://www.themealdb.com/images/media/meals/1529446352.jpg', // Chicken Alfredo Primavera
            'https://www.themealdb.com/images/media/meals/1550441883.jpg', // Chicken Couscous
            'https://www.themealdb.com/images/media/meals/1529444830.jpg', // Chicken Fajita Mac and Cheese
            'https://www.themealdb.com/images/media/meals/1550441275.jpg', // Chicken Ham and Leek Pie
            'https://www.themealdb.com/images/media/meals/1520084413.jpg', // Chicken Quinoa Greek Bowl
            'https://www.themealdb.com/images/media/meals/1529446352.jpg', // Chocolate Raspberry Tarts
            'https://www.themealdb.com/images/media/meals/1550441883.jpg', // Classic Christmas pudding
            'https://www.themealdb.com/images/media/meals/1529444830.jpg', // Creamy Tomato Soup
            'https://www.themealdb.com/images/media/meals/1550441275.jpg', // Dal fry
            'https://www.themealdb.com/images/media/meals/1520084413.jpg', // Duck Confit
            'https://www.themealdb.com/images/media/meals/1529446352.jpg', // English Breakfast
            'https://www.themealdb.com/images/media/meals/1550441883.jpg', // French Onion Chicken with Roasted Carrots & Mashed Potatoes
            'https://www.themealdb.com/images/media/meals/1529444830.jpg', // French Onion Soup
            'https://www.themealdb.com/images/media/meals/1550441275.jpg', // Full English Breakfast
            'https://www.themealdb.com/images/media/meals/1520084413.jpg', // Hot Chocolate Fudge
            'https://www.themealdb.com/images/media/meals/1529446352.jpg', // Katsu Chicken curry
            'https://www.themealdb.com/images/media/meals/1550441883.jpg', // Lamb and Potato pie
            'https://www.themealdb.com/images/media/meals/1529444830.jpg', // Lasagna Sandwiches
            'https://www.themealdb.com/images/media/meals/1550441275.jpg', // Massaman Beef curry
            'https://www.themealdb.com/images/media/meals/1520084413.jpg', // Minced Beef Pie
            'https://www.themealdb.com/images/media/meals/1529446352.jpg', // Mushroom soup with buckwheat
            'https://www.themealdb.com/images/media/meals/1550441883.jpg', // New York cheesecake
            'https://www.themealdb.com/images/media/meals/1529444830.jpg', // Pad See Ew
            'https://www.themealdb.com/images/media/meals/1550441275.jpg', // Pancakes
            'https://www.themealdb.com/images/media/meals/1520084413.jpg', // Pasta and Beans
            'https://www.themealdb.com/images/media/meals/1529446352.jpg', // Peanut Butter Cheesecake
            'https://www.themealdb.com/images/media/meals/1550441883.jpg', // Pizza Express Margherita
            'https://www.themealdb.com/images/media/meals/1529444830.jpg', // Pork Cassoulet
            'https://www.themealdb.com/images/media/meals/1550441275.jpg', // Pork Dumplings
            'https://www.themealdb.com/images/media/meals/1520084413.jpg', // Portuguese prego with green piri-piri
            'https://www.themealdb.com/images/media/meals/1529446352.jpg', // Potato Gratin with Chicken
            'https://www.themealdb.com/images/media/meals/1550441883.jpg', // Pumpkin Pie
            'https://www.themealdb.com/images/media/meals/1529444830.jpg', // Red Peas Soup
            'https://www.themealdb.com/images/media/meals/1550441275.jpg', // Roast fennel and aubergine paella
            'https://www.themealdb.com/images/media/meals/1520084413.jpg', // Salmon Avocado Salad
            'https://www.themealdb.com/images/media/meals/1529446352.jpg', // Salmon Prawn Risotto
            'https://www.themealdb.com/images/media/meals/1550441883.jpg', // Salted Caramel Cheescake
            'https://www.themealdb.com/images/media/meals/1529444830.jpg', // Seafood fideuà
            'https://www.themealdb.com/images/media/meals/1550441275.jpg', // Smoky Lentil Chili with Squash
            'https://www.themealdb.com/images/media/meals/1520084413.jpg', // Spanish Tortilla
            'https://www.themealdb.com/images/media/meals/1529446352.jpg', // Spicy Arrabiata Penne
            'https://www.themealdb.com/images/media/meals/1550441883.jpg', // Spicy North African Potato Salad
            'https://www.themealdb.com/images/media/meals/1529444830.jpg', // Sticky Toffee Pudding
            'https://www.themealdb.com/images/media/meals/1550441275.jpg', // Summer Pistou
            'https://www.themealdb.com/images/media/meals/1520084413.jpg', // Tandoori chicken pizza
            'https://www.themealdb.com/images/media/meals/1529446352.jpg', // Teriyaki Chicken Casserole
            'https://www.themealdb.com/images/media/meals/1550441883.jpg', // Three Fish Pie
            'https://www.themealdb.com/images/media/meals/1529444830.jpg', // Toad In The Hole
            'https://www.themealdb.com/images/media/meals/1550441275.jpg', // Treacle Tart
            'https://www.themealdb.com/images/media/meals/1520084413.jpg', // Tuna and Egg Briks
            'https://www.themealdb.com/images/media/meals/1529446352.jpg', // White chocolate creme brulee
        ];

        return [
            'commerce_id' => Commerce::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 5, 50),
            'image' => $this->faker->randomElement($foodImages),
            'available' => $this->faker->boolean(80),
        ];
    }

    /**
     * Indicate that the product should be created with a commerce.
     */
    public function withCommerce()
    {
        return $this->afterCreating(function (Product $product) {
            $commerce = Commerce::factory()->create();
            $product->update(['commerce_id' => $commerce->id]);
        });
    }
}
