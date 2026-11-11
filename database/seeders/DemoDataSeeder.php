<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create the manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'John Manager',
                'password' => 'password123',
                'role' => UserRole::MANAGER->value,
                'manager_id' => null,
            ]
        );

        // Create employees reporting to this manager
        $employee1 = User::updateOrCreate(
            ['email' => 'employee1@example.com'],
            [
                'name' => 'Alice Employee',
                'password' => 'password123',
                'role' => UserRole::EMPLOYEE->value,
                'manager_id' => $manager->id,
            ]
        );

        $employee2 = User::updateOrCreate(
            ['email' => 'employee2@example.com'],
            [
                'name' => 'Bob Employee',
                'password' => 'password123',
                'role' => UserRole::EMPLOYEE->value,
                'manager_id' => $manager->id,
            ]
        );

        // Create an Operations user
        $operations = User::updateOrCreate(
            ['email' => 'operations@example.com'],
            [
                'name' => 'David Operations',
                'password' => 'password123',
                'role' => UserRole::OPERATIONS->value,
                'manager_id' => null,
            ]
        );

        // Sample service requests with different statuses
        $requests = [
            [
                'employee' => $employee1,
                'title' => 'Laptop Repair',
                'description' => 'My laptop is not starting.',
                'category' => 'IT Support',
                'priority' => 'high',
                'status' => RequestStatus::DRAFT->value,
                'assigned_to' => null,
            ],
            [
                'employee' => $employee1,
                'title' => 'Software Installation',
                'description' => 'Need development software installed.',
                'category' => 'Software',
                'priority' => 'medium',
                'status' => RequestStatus::SUBMITTED->value,
                'assigned_to' => null,
            ],
            [
                'employee' => $employee1,
                'title' => 'Internet Access',
                'description' => 'Requesting access to an internal system.',
                'category' => 'Access',
                'priority' => 'high',
                'status' => RequestStatus::APPROVED->value,
                'assigned_to' => null,
            ],
            [
                'employee' => $employee1,
                'title' => 'Printer Issue',
                'description' => 'Office printer is not working.',
                'category' => 'Hardware',
                'priority' => 'low',
                'status' => RequestStatus::REJECTED->value,
                'assigned_to' => null,
            ],
            [
                'employee' => $employee1,
                'title' => 'New Monitor',
                'description' => 'Requesting a second monitor.',
                'category' => 'Hardware',
                'priority' => 'medium',
                'status' => RequestStatus::RETURNED->value,
                'assigned_to' => null,
            ],
            [
                'employee' => $employee2,
                'title' => 'Account Setup',
                'description' => 'Need a new company account.',
                'category' => 'Access',
                'priority' => 'high',
                'status' => RequestStatus::ASSIGNED->value,
                'assigned_to' => $operations->id,
            ],
            [
                'employee' => $employee2,
                'title' => 'Network Problem',
                'description' => 'Experiencing network connectivity issues.',
                'category' => 'Network',
                'priority' => 'high',
                'status' => RequestStatus::IN_PROGRESS->value,
                'assigned_to' => $operations->id,
            ],
            [
                'employee' => $employee2,
                'title' => 'Email Configuration',
                'description' => 'Email configuration has been completed.',
                'category' => 'Software',
                'priority' => 'low',
                'status' => RequestStatus::COMPLETED->value,
                'assigned_to' => $operations->id,
            ],
        ];

        foreach ($requests as $data) {
            $serviceRequest = ServiceRequest::updateOrCreate(
                [
                    'employee_id' => $data['employee']->id,
                    'title' => $data['title'],
                ],
                [
                    'description' => $data['description'],
                    'category' => $data['category'],
                    'priority' => $data['priority'],
                    'status' => $data['status'],
                    'assigned_to' => $data['assigned_to'],
                ]
            );

            // Add an example activity record
            RequestActivity::firstOrCreate(
                [
                    'service_request_id' => $serviceRequest->id,
                    'action' => 'seeded',
                ],
                [
                    'user_id' => $data['employee']->id,
                    'comment' => 'Sample activity created for testing.',
                ]
            );
        }

        // Add an example comment
        $exampleRequest = ServiceRequest::where(
            'title',
            'Laptop Repair'
        )->first();

        Comment::firstOrCreate(
            [
                'service_request_id' => $exampleRequest->id,
                'user_id' => $employee1->id,
                'body' => 'My laptop stopped working this morning.',
            ]
        );
    }
}
