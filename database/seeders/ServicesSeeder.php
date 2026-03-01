<?php

namespace Database\Seeders;

use App\Models\Service\Service;
use App\Models\Service\ServiceAddon;
use App\Models\Service\ServiceCategory;
use Illuminate\Database\Seeder;

class ServicesSeeder extends Seeder
{
    private $serviceData = [

        'service_categories' => [

            [
                'key' => 'haircuts',
                'name' => 'Haircuts',
                'description' => 'Professional haircut services for men and boys',
            ],

            [
                'key' => 'beard_services',
                'name' => 'Beard Services',
                'description' => 'Beard trimming, shaping and grooming',
            ],

            [
                'key' => 'combo_deals',
                'name' => 'Combo Deals',
                'description' => 'Hair & beard combination packages',
            ],

            [
                'key' => 'kids_services',
                'name' => 'Kids Services',
                'description' => 'Haircuts for children under 12',
            ],
        ],

        'services' => [

            // HAIRCUTS
            [
                'key' => 'classic_cut',
                'category_key' => 'haircuts',
                'name' => 'Classic Cut',
                'description' => 'A timeless scissor and clipper cut tailored to your head shape and style preference. Includes consultation, precise cutting, and clean finishing.',
                'price' => 250.00,
                'duration_minutes' => 45,
            ],
            [
                'key' => 'skin_fade',
                'category_key' => 'haircuts',
                'name' => 'Skin Fade',
                'description' => 'A sharp, modern fade blended seamlessly down to the skin. Finished with detailed edging and styling for a crisp, clean look.',
                'price' => 300.00,
                'duration_minutes' => 60,
            ],
            [
                'key' => 'buzz_cut',
                'category_key' => 'haircuts',
                'name' => 'Buzz Cut',
                'description' => 'A clean, even all-over clipper cut for a low-maintenance and sharp appearance. Simple, neat, and efficient.',
                'price' => 180.00,
                'duration_minutes' => 30,
            ],

            // BEARD SERVICES
            [
                'key' => 'beard_trim',
                'category_key' => 'beard_services',
                'name' => 'Beard Trim',
                'description' => 'Precision beard trimming to maintain length and shape. Includes neckline and cheek line clean-up for a well-groomed finish.',
                'price' => 150.00,
                'duration_minutes' => 30,
            ],
            [
                'key' => 'beard_shape',
                'category_key' => 'beard_services',
                'name' => 'Beard Shape & Line Up',
                'description' => 'Detailed beard sculpting and sharp line-up using clippers and razor for defined edges and a polished look.',
                'price' => 200.00,
                'duration_minutes' => 35,
            ],

            // COMBO DEALS
            [
                'key' => 'cut_and_beard',
                'category_key' => 'combo_deals',
                'name' => 'Classic Cut + Beard Trim',
                'description' => 'Our classic haircut paired with a professional beard trim. The perfect combination for a fresh, complete grooming experience.',
                'price' => 380.00,
                'duration_minutes' => 75,
            ],
            [
                'key' => 'fade_and_beard',
                'category_key' => 'combo_deals',
                'name' => 'Skin Fade + Beard Shape',
                'description' => 'A sharp skin fade combined with expert beard shaping and line-up for a bold, modern, and well-defined look.',
                'price' => 450.00,
                'duration_minutes' => 90,
            ],

            // KIDS
            [
                'key' => 'kids_cut',
                'category_key' => 'kids_services',
                'name' => 'Kids Cut (Under 12)',
                'description' => 'A neat and stylish haircut tailored for kids under 12. Gentle service with attention to comfort and style.',
                'price' => 180.00,
                'duration_minutes' => 30,
            ],
        ],

        'service_addons' => [

            [
                'service_key' => 'beard_trim',
                'name' => 'Hot Towel',
                'price' => 25.00,
                'duration_minutes' => 5,
            ],
            [
                'service_key' => 'beard_trim',
                'name' => 'Hair Wash',
                'price' => 40.00,
                'duration_minutes' => 10,
            ],
            [
                'service_key' => 'fade_and_beard',
                'name' => 'Black Mask',
                'price' => 60.00,
                'duration_minutes' => 15,
            ],
            [
                'service_key' => 'fade_and_beard',
                'name' => 'Detailed Shape',
                'price' => 30.00,
                'duration_minutes' => 10,
            ],
        ],
    ];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->serviceData['service_categories'] as $category) {
            $createdCategory = ServiceCategory::create([
                'name' => $category['name'],
                'description' => $category['description'],
            ]);

            $categoryMap[$category['key']] = $createdCategory->id;
        }

        foreach ($this->serviceData['services'] as $service) {
            $createdService = Service::create([
                'service_category_id' => $categoryMap[$service['category_key']],
                'name' => $service['name'],
                'description' => $service['description'],
                'price' => $service['price'],
                'duration_minutes' => $service['duration_minutes'],
            ]);

            $serviceMap[$service['key']] = $createdService->id;
        }

        foreach ($this->serviceData['service_addons'] as $addon) {
            ServiceAddon::create([
                'service_id' => $serviceMap[$addon['service_key']],
                'name' => $addon['name'],
                'price' => $addon['price'],
                'duration_minutes' => $addon['duration_minutes'],
            ]);
        }
    }
}
