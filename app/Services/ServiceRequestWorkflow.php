<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use App\Models\User;
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

            if (!in_array($newStatus->value, $allowedStatuses, true)) {
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

            return $request->fresh();
        });
    }
}
