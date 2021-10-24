<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $product = Product::inRandomOrder()->first();
        $quantity = $this->faker->numberBetween(1, 5);
        return [
            'product_title' => $product->title,
            'price' => $product->price,
            'quantity' => $quantity,
            'admin_revenue' => $product->price * $quantity * 0.9,
            'ambassador_revenue' => $product->price * $quantity * 0.1,
        ];
    }
}
