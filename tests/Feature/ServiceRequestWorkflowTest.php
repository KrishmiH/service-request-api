<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_submit_their_own_draft_request(): void
    {
        $manager = User::factory()->manager()->create();

        $employee = User::factory()
            ->employeeOf($manager)
            ->create();

        $serviceRequest = ServiceRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson("/api/requests/{$serviceRequest->id}/submit");

        $response->assertOk();

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'submitted',
        ]);
    }

    public function test_employee_cannot_approve_a_request(): void
    {
        $manager = User::factory()->manager()->create();

        $employee = User::factory()
            ->employeeOf($manager)
            ->create();

        $serviceRequest = ServiceRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson("/api/requests/{$serviceRequest->id}/approve");

        $response->assertForbidden();

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'submitted',
        ]);
    }

    public function test_employee_cannot_submit_an_already_submitted_request(): void
    {
        $manager = User::factory()->manager()->create();

        $employee = User::factory()
            ->employeeOf($manager)
            ->create();

        $serviceRequest = ServiceRequest::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson("/api/requests/{$serviceRequest->id}/submit");

        $response->assertStatus(422);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'submitted',
        ]);
    }
}
