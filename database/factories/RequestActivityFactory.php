<?php

namespace Database\Factories;

use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RequestActivity>
 */
class RequestActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'user_id' => User::factory(),
            'action' => 'created',
            'from_status' => null,
            'to_status' => 'draft',
            'comment' => null,
            'metadata' => null,
        ];
    }
}
