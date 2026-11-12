<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\ServiceRequestStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceRequestWorkflow
{
    /**
     * Define the allowed status transitions.
     */
    private const TRANSITIONS = [
        'draft' => ['submitted'],
        'returned' => ['submitted'],
        'submitted' => ['approved', 'rejected', 'returned'],
        'approved' => ['assigned'],
        'assigned' => ['in_progress'],
        'in_progress' => ['completed'],
        'rejected' => [],
        'completed' => [],
    ];

    /**
     * Change a request's status and record its activity.
     */
    public function transition(
        ServiceRequest $serviceRequest,
        User $actor,
        RequestStatus $newStatus,
        ?string $comment = null,
        array $metadata = []
    ): ServiceRequest {

        return DB::transaction(function () use (
            $serviceRequest,
            $actor,
            $newStatus,
            $comment,
            $metadata
        ) {
            // Lock the record while processing the transition.
            $request = ServiceRequest::query()
                ->lockForUpdate()
                ->findOrFail($serviceRequest->id);

            $oldStatus = $request->status;

            // Check whether the requested transition is valid.
            $allowedStatuses = self::TRANSITIONS[$oldStatus] ?? [];

            if (! in_array($newStatus->value, $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'status' => [
                        "Cannot change request status from {$oldStatus} to {$newStatus->value}.",
                    ],
                ]);
            }

            // Update the request status.
            $request->status = $newStatus->value;
            $request->save();

            // Record the status change in the activity history.
            RequestActivity::create([
                'service_request_id' => $request->id,
                'user_id' => $actor->id,
                'action' => $newStatus->value,
                'from_status' => $oldStatus,
                'to_status' => $newStatus->value,
                'comment' => $comment,
                'metadata' => $metadata,
            ]);

            $this->notifyRelevantUsers($request, $newStatus);

            return $request->fresh();
        });

    }

    /**
     * Assign a service request to an Operations user.
     */
    public function assign(
        ServiceRequest $serviceRequest,
        User $actor,
        User $assignee,
        ?string $comment = null
    ): ServiceRequest {
        return DB::transaction(function () use (
            $serviceRequest,
            $actor,
            $assignee,
            $comment
        ) {
            $request = ServiceRequest::query()
                ->lockForUpdate()
                ->findOrFail($serviceRequest->id);

            // Only approved requests can be assigned
            if ($request->status !== RequestStatus::APPROVED->value) {
                throw ValidationException::withMessages([
                    'status' => ['Only approved requests can be assigned.'],
                ]);
            }

            // Set the assigned Operations user and change status
            $request->assigned_to = $assignee->id;
            $request->status = RequestStatus::ASSIGNED->value;
            $request->save();

            // Record the assignment in the activity history
            RequestActivity::create([
                'service_request_id' => $request->id,
                'user_id' => $actor->id,
                'action' => 'assigned',
                'from_status' => RequestStatus::APPROVED->value,
                'to_status' => RequestStatus::ASSIGNED->value,
                'comment' => $comment,
                'metadata' => [
                    'assigned_to' => $assignee->id,
                ],
            ]);

            $this->notifyRelevantUsers($request, RequestStatus::ASSIGNED);

            return $request->fresh();
        });
    }

    private function notifyRelevantUsers(
        ServiceRequest $request,
        RequestStatus $status
    ): void {
        $request->loadMissing(['employee.manager', 'assignedUser']);

        $recipient = match ($status) {
            RequestStatus::SUBMITTED => $request->employee?->manager,

            RequestStatus::APPROVED,
            RequestStatus::REJECTED,
            RequestStatus::RETURNED,
            RequestStatus::IN_PROGRESS,
            RequestStatus::COMPLETED => $request->employee,

            RequestStatus::ASSIGNED => $request->assignedUser,

            default => null,
        };

        if (! $recipient) {
            return;
        }

        $message = match ($status) {
            RequestStatus::SUBMITTED => 'A service request has been submitted for your review.',

            RequestStatus::APPROVED => 'Your service request has been approved.',

            RequestStatus::REJECTED => 'Your service request has been rejected.',

            RequestStatus::RETURNED => 'Your service request has been returned for more details.',

            RequestStatus::ASSIGNED => 'A service request has been assigned to you.',

            RequestStatus::IN_PROGRESS => 'Work on your service request has started.',

            RequestStatus::COMPLETED => 'Your service request has been completed.',

            default => 'Your service request status has changed.',
        };

        $recipient->notify(
            new ServiceRequestStatusNotification(
                $request->title,
                $status->value,
                $message
            )
        );
    }
}
