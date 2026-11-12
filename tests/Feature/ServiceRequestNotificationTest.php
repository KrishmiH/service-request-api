<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ServiceRequestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_job_is_dispatched_when_employee_submits_request(): void
    {
        Queue::fake();

        // Create a manager.
        $manager = User::create([
            'name' => 'John Manager',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'role' => 'manager',
        ]);

        // Create an employee reporting to that manager.
        $employee = User::create([
            'name' => 'Alice Employee',
            'email' => 'employee@example.com',
            'password' => 'password123',
            'role' => 'employee',
            'manager_id' => $manager->id,
        ]);

        // Create a draft request.
        $serviceRequest = ServiceRequest::create([
            'employee_id' => $employee->id,
            'title' => 'Laptop Repair',
            'description' => 'Laptop is not starting.',
            'category' => 'IT Support',
            'priority' => 'medium',
            'status' => 'draft',
        ]);

        // Submit the request as the employee.
        $response = $this->actingAs($employee, 'sanctum')
            ->postJson("/api/requests/{$serviceRequest->id}/submit");

        $response->assertOk();

        // Confirm that the request status changed.
        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'submitted',
        ]);

        // Confirm that Laravel dispatched a queued notification job.
        Queue::assertPushed(SendQueuedNotifications::class);
    }
}
