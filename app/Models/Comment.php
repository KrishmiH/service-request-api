<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_request_id',
        'user_id',
        'body',
    ];

    // The request this comment belongs to
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    // The user who wrote this comment
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
