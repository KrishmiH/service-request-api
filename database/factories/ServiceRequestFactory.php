<?php

namespace Database\Factories;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => User::factory(),
            'assigned_to' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement([
                'IT Support',
                'Hardware',
                'Software',
                'Maintenance',
            ]),
            'priority' => fake()->randomElement([
                'low',
                'medium',
                'high',
            ]),
            'status' => 'draft',
        ];
    }
}
