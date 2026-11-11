<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();

            // Employee who created the request
            $table->foreignId('employee_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Operations user responsible for the request
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description');

            $table->string('category');
            $table->string('priority')->default('medium');
            $table->string('status')->default('draft');

            $table->timestamps();

            // Indexes for common filtering and sorting
            $table->index(['status', 'created_at']);
            $table->index(['employee_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
