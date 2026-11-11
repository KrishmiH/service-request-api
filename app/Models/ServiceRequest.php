<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'assigned_to',
        'title',
        'description',
        'category',
        'priority',
        'status',
    ];

    // Employee who created this request
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    // Operations user assigned to this request
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Comments belonging to this request
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // Activity history belonging to this request
    public function activities(): HasMany
    {
        return $this->hasMany(RequestActivity::class);
    }
}
