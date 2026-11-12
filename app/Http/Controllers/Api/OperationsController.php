<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\ServiceRequestWorkflow;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    use AuthorizesRequests;

    public function assign(
        Request $request,
        ServiceRequest $serviceRequest,
        ServiceRequestWorkflow $workflow
    ) {
        // Check whether the logged-in user can assign this request
        $this->authorize('assign', $serviceRequest);

        // Validate the selected Operations user
        $validated = $request->validate([
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'operations'),
            ],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $assignee = User::findOrFail($validated['assigned_to']);

        // Assign the request and update its status
        $updatedRequest = $workflow->assign(
            $serviceRequest,
            $request->user(),
            $assignee,
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Service request assigned successfully.',
            'data' => $updatedRequest,
        ]);
    }

    /**
     * Start working on a service request.
     */
    public function start(
        Request $request,
        ServiceRequest $serviceRequest,
        ServiceRequestWorkflow $workflow
    ) {
        $this->authorize('start', $serviceRequest);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $updatedRequest = $workflow->transition(
            $serviceRequest,
            $request->user(),
            RequestStatus::IN_PROGRESS,
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Service request started successfully.',
            'data' => $updatedRequest,
        ]);
    }

    /**
     * Complete a service request.
     */
    public function complete(
        Request $request,
        ServiceRequest $serviceRequest,
        ServiceRequestWorkflow $workflow
    ) {
        $this->authorize('complete', $serviceRequest);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $updatedRequest = $workflow->transition(
            $serviceRequest,
            $request->user(),
            RequestStatus::COMPLETED,
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Service request completed successfully.',
            'data' => $updatedRequest,
        ]);
    }
}
