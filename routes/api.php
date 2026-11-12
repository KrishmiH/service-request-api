<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\ManagerApprovalController;
use App\Http\Controllers\Api\OperationsController;
use App\Http\Controllers\Api\CommentController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Service Requests
    Route::get('/requests', [ServiceRequestController::class, 'index']);
    Route::post('/requests', [ServiceRequestController::class, 'store']);
    Route::get('/requests/{serviceRequest}', [ServiceRequestController::class, 'show']);
    Route::put('/requests/{serviceRequest}', [ServiceRequestController::class, 'update']);
    Route::patch('/requests/{serviceRequest}', [ServiceRequestController::class, 'update']);

    // Manager approval actions
    Route::post('/requests/{serviceRequest}/approve',
        [ManagerApprovalController::class, 'approve']);

    Route::post('/requests/{serviceRequest}/reject',
        [ManagerApprovalController::class, 'reject']);

    Route::post('/requests/{serviceRequest}/return',
        [ManagerApprovalController::class, 'sendBack']);

    Route::post(
        '/requests/{serviceRequest}/submit',
        [ServiceRequestController::class, 'submit']
    );
    
    Route::post(
        '/requests/{serviceRequest}/assign',
        [OperationsController::class, 'assign']
    );

    Route::post(
        '/requests/{serviceRequest}/start',
        [OperationsController::class, 'start']
    );

    Route::post(
        '/requests/{serviceRequest}/complete',
        [OperationsController::class, 'complete']
    );
    
    Route::get(
        '/requests/{serviceRequest}/comments',
        [CommentController::class, 'index']
    );

    Route::post(
        '/requests/{serviceRequest}/comments',
        [CommentController::class, 'store']
    );
});