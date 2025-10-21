<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Wireless Bluetooth Headphones',
                'description' => 'Premium noise-cancelling wireless headphones with 30-hour battery life',
                'price' => 129.99,
                'stock' => 50,
                'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e',
            ],
            [
                'name' => 'Smart Watch Series 5',
                'description' => 'Fitness tracker with heart rate monitor and GPS',
                'price' => 299.99,
                'stock' => 35,
                'image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30',
            ],
            [
                'name' => 'Portable Bluetooth Speaker',
                'description' => 'Waterproof portable speaker with 360-degree sound',
                'price' => 79.99,
                'stock' => 100,
                'image_url' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1',
            ],
            [
                'name' => 'USB-C Fast Charger',
                'description' => '65W fast charging adapter for laptops and phones',
                'price' => 49.99,
                'stock' => 200,
                'image_url' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0',
            ],
            [
                'name' => 'Mechanical Keyboard RGB',
                'description' => 'Gaming mechanical keyboard with customizable RGB lighting',
                'price' => 149.99,
                'stock' => 45,
                'image_url' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3',
            ],
            [
                'name' => 'Wireless Mouse',
                'description' => 'Ergonomic wireless mouse with precision tracking',
                'price' => 39.99,
                'stock' => 150,
                'image_url' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46',
            ],
            [
                'name' => '4K Webcam',
                'description' => 'Professional 4K webcam with auto-focus and noise reduction',
                'price' => 199.99,
                'stock' => 25,
                'image_url' => 'https://images.unsplash.com/photo-1587826080692-f439cd0b70da',
            ],
            [
                'name' => 'External SSD 1TB',
                'description' => 'Ultra-fast portable SSD with USB 3.2 Gen 2',
                'price' => 159.99,
                'stock' => 60,
                'image_url' => 'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b',
            ],
            [
                'name' => 'Laptop Stand Aluminum',
                'description' => 'Adjustable ergonomic laptop stand for better posture',
                'price' => 54.99,
                'stock' => 80,
                'image_url' => 'https://images.unsplash.com/photo-1625225233840-695456021cde',
            ],
            [
                'name' => 'Phone Case Premium Leather',
                'description' => 'Genuine leather phone case with card holder',
                'price' => 29.99,
                'stock' => 120,
                'image_url' => 'https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb',
            ],
            [
                'name' => 'Wireless Charging Pad',
                'description' => 'Fast wireless charger compatible with all Qi devices',
                'price' => 34.99,
                'stock' => 90,
                'image_url' => 'https://images.unsplash.com/photo-1591290619762-0af5c6c6c96a',
            ],
            [
                'name' => 'Screen Protector Tempered Glass',
                'description' => '9H hardness tempered glass screen protector',
                'price' => 14.99,
                'stock' => 250,
                'image_url' => 'https://images.unsplash.com/photo-1601784551446-20c9e07cdbdb',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
