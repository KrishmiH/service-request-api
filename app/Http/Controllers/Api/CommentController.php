<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\RequestActivity;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    use AuthorizesRequests;

    // Get comments for a service request
    public function index(ServiceRequest $serviceRequest)
    {
        $this->authorize('view', $serviceRequest);

        $comments = $serviceRequest->comments()
            ->with('user:id,name')
            ->oldest()
            ->paginate(10);

        return response()->json([
            'data' => $comments,
        ]);
    }

    // Add a comment to a service request
    public function store(Request $request, ServiceRequest $serviceRequest)
    {
        $this->authorize('comment', $serviceRequest);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment = DB::transaction(function () use ($request, $serviceRequest, $validated) {

            $comment = Comment::create([
                'service_request_id' => $serviceRequest->id,
                'user_id' => $request->user()->id,
                'body' => $validated['body'],
            ]);

            RequestActivity::create([
                'service_request_id' => $serviceRequest->id,
                'user_id' => $request->user()->id,
                'action' => 'commented',
                'from_status' => $serviceRequest->status,
                'to_status' => $serviceRequest->status,
                'comment' => $validated['body'],
            ]);

            return $comment;
        });

        return response()->json([
            'message' => 'Comment added successfully.',
            'data' => $comment->load('user:id,name'),
        ], 201);
    }
}
