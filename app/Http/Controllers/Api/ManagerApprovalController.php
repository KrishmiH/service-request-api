<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestWorkflow;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ManagerApprovalController extends Controller
{
    use AuthorizesRequests;

    /**
     * Approve a submitted service request.
     */
    public function approve(
        Request $request,
        ServiceRequest $serviceRequest,
        ServiceRequestWorkflow $workflow
    ) {
        $this->authorize('approve', $serviceRequest);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $updatedRequest = $workflow->transition(
            $serviceRequest,
            $request->user(),
            RequestStatus::APPROVED,
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Service request approved successfully.',
            'data' => $updatedRequest,
        ]);
    }

    /**
     * Reject a submitted service request.
     */
    public function reject(
        Request $request,
        ServiceRequest $serviceRequest,
        ServiceRequestWorkflow $workflow
    ) {
        $this->authorize('reject', $serviceRequest);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $updatedRequest = $workflow->transition(
            $serviceRequest,
            $request->user(),
            RequestStatus::REJECTED,
            $validated['comment']
        );

        return response()->json([
            'message' => 'Service request rejected successfully.',
            'data' => $updatedRequest,
        ]);
    }

    /**
     * Return a submitted request to the employee.
     */
    public function sendBack(
        Request $request,
        ServiceRequest $serviceRequest,
        ServiceRequestWorkflow $workflow
    ) {
        $this->authorize('sendBack', $serviceRequest);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $updatedRequest = $workflow->transition(
            $serviceRequest,
            $request->user(),
            RequestStatus::RETURNED,
            $validated['comment']
        );

        return response()->json([
            'message' => 'Service request returned successfully.',
            'data' => $updatedRequest,
        ]);
    }
}
