<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestWorkflow;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceRequestController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display requests accessible to the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:30'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'category' => ['nullable', 'string', 'max:100'],
            'sort_by' => ['nullable', 'in:created_at,title,priority,status'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
        ]);

        $query = ServiceRequest::query()
            ->with(['employee', 'assignedUser']);

        // Restrict results according to the user's role.
        if ($user->role === 'employee') {
            $query->where('employee_id', $user->id);
        } elseif ($user->role === 'manager') {
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where('manager_id', $user->id);
            });
        } elseif ($user->role === 'operations') {
            $query->where(function ($q) use ($user) {
                $q->where('status', 'approved')
                    ->orWhere('assigned_to', $user->id);
            });
        }

        // Search by title, description, or category.
        if (! empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Filter by status.
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Filter by priority.
        if (! empty($validated['priority'])) {
            $query->where('priority', $validated['priority']);
        }

        // Filter by category.
        if (! empty($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        // Sort results.
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';

        $requests = $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'data' => $requests,
        ]);
    }

    /**
     * Create a new service request.
     */
    public function store(Request $request)
    {
        $this->authorize('create', ServiceRequest::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'priority' => ['sometimes', 'in:low,medium,high'],
        ]);

        $serviceRequest = DB::transaction(function () use ($request, $validated) {
            $serviceRequest = ServiceRequest::create([
                ...$validated,
                'employee_id' => $request->user()->id,
                'status' => RequestStatus::DRAFT->value,
            ]);

            RequestActivity::create([
                'service_request_id' => $serviceRequest->id,
                'user_id' => $request->user()->id,
                'action' => 'created',
                'to_status' => RequestStatus::DRAFT->value,
                'metadata' => [
                    'message' => 'Service request created.',
                ],
            ]);

            return $serviceRequest;
        });

        return response()->json([
            'message' => 'Service request created successfully.',
            'data' => $serviceRequest->load('employee:id,name,email'),
        ], 201);
    }

    /**
     * Display one service request.
     */
    public function show(ServiceRequest $serviceRequest)
    {
        $this->authorize('view', $serviceRequest);

        return response()->json([
            'data' => $serviceRequest->load([
                'employee:id,name,email',
                'assignedUser:id,name,email',
                'comments.user:id,name',
                'activities.user:id,name',
            ]),
        ]);
    }

    /**
     * Update an eligible service request.
     */
    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $this->authorize('update', $serviceRequest);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'priority' => ['sometimes', 'required', 'in:low,medium,high'],
        ]);

        DB::transaction(function () use ($request, $serviceRequest, $validated) {
            $serviceRequest->update($validated);

            RequestActivity::create([
                'service_request_id' => $serviceRequest->id,
                'user_id' => $request->user()->id,
                'action' => 'updated',
                'from_status' => $serviceRequest->status,
                'to_status' => $serviceRequest->status,
                'metadata' => [
                    'updated_fields' => array_keys($validated),
                ],
            ]);
        });

        return response()->json([
            'message' => 'Service request updated successfully.',
            'data' => $serviceRequest->fresh(),
        ]);
    }

    public function submit(
        Request $request,
        ServiceRequest $serviceRequest,
        ServiceRequestWorkflow $workflow
    ) {
        // Check whether this employee is allowed to submit this request
        $this->authorize('submit', $serviceRequest);

        // Validate the optional comment
        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // Change the status to submitted and record the activity
        $updatedRequest = $workflow->transition(
            $serviceRequest,
            $request->user(),
            RequestStatus::SUBMITTED,
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Service request submitted successfully.',
            'data' => $updatedRequest,
        ]);
    }
}
