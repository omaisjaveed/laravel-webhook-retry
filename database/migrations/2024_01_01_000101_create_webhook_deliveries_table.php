<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $table->uuid('event_id');
            $table->string('event_name');
            $table->json('payload');
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->unsignedInteger('max_attempts');
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->text('last_response_body')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(['webhook_endpoint_id', 'event_id']);
            $table->index(['event_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
