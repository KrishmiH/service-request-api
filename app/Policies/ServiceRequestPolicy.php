<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    // Any authenticated role can request a list.
    // The controller must still filter which records they can see.
    public function viewAny(User $user): bool
    {
        return true;
    }

    // Check whether a user can view a particular request.
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        // Employees can view their own requests.
        if (
            $user->role === UserRole::EMPLOYEE->value &&
            $serviceRequest->employee_id === $user->id
        ) {
            return true;
        }

        // Managers can view requests from their direct employees.
        if (
            $user->role === UserRole::MANAGER->value &&
            $serviceRequest->employee?->manager_id === $user->id
        ) {
            return true;
        }

        // Operations can view approved requests awaiting assignment
        // and requests assigned to them.
        if ($user->role === UserRole::OPERATIONS->value) {
            return $serviceRequest->status === RequestStatus::APPROVED->value
                || $serviceRequest->assigned_to === $user->id;
        }

        return false;
    }

    // Only employees can create service requests.
    public function create(User $user): bool
    {
        return $user->role === UserRole::EMPLOYEE->value;
    }

    // Employees can edit their own draft or returned requests.
    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->role === UserRole::EMPLOYEE->value
            && $serviceRequest->employee_id === $user->id
            && in_array($serviceRequest->status, [
                RequestStatus::DRAFT->value,
                RequestStatus::RETURNED->value,
            ], true);
    }

    // Employees can submit their own draft or returned requests.
    public function submit(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->role === UserRole::EMPLOYEE->value
            && $serviceRequest->employee_id === $user->id
            && in_array($serviceRequest->status, [
                RequestStatus::DRAFT->value,
                RequestStatus::RETURNED->value,
            ], true);
    }

    // Managers can approve submitted requests from direct reports.
    public function approve(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->role === UserRole::MANAGER->value
            && $serviceRequest->employee?->manager_id === $user->id
            && $serviceRequest->status === RequestStatus::SUBMITTED->value;
    }

    // Managers can reject submitted requests from direct reports.
    public function reject(User $user, ServiceRequest $serviceRequest): bool
    {
        return $this->approve($user, $serviceRequest);
    }

    // Managers can return submitted requests to employees.
    public function sendBack(User $user, ServiceRequest $serviceRequest): bool
    {
        return $this->approve($user, $serviceRequest);
    }

    // Operations can assign approved requests.
    public function assign(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->role === UserRole::OPERATIONS->value
            && $serviceRequest->status === RequestStatus::APPROVED->value;
    }

    // Only the assigned Operations user can start the work.
    public function start(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->role === UserRole::OPERATIONS->value
            && $serviceRequest->assigned_to === $user->id
            && $serviceRequest->status === RequestStatus::ASSIGNED->value;
    }

    // Only the assigned Operations user can complete the work.
    public function complete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->role === UserRole::OPERATIONS->value
            && $serviceRequest->assigned_to === $user->id
            && $serviceRequest->status === RequestStatus::IN_PROGRESS->value;
    }

    // Request participants can comment.
    public function comment(User $user, ServiceRequest $serviceRequest): bool
    {
        return $this->view($user, $serviceRequest);
    }
}
