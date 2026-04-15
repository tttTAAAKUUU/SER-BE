<?php

namespace Database\Factories\Store;

use App\Models\Business\Store\StoreService;
use App\Models\Business\Store\Employee;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'store_service_id' => StoreService::factory(),
            'employee_id' => Employee::factory(),
            'time_category' => fake()->randomElement(['morning', 'afternoon', 'evening']),
            'time' => fake()->time(),
            'service_location' => fake()->randomElement(['shop', 'office', 'home']),
        ];
    }
}