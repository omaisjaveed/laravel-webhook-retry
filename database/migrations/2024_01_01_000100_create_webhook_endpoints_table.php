<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->text('secret')->nullable();
            $table->json('auth_headers')->nullable();
            $table->json('subscribed_events')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('timeout')->nullable();
            $table->unsignedInteger('max_attempts')->nullable();
            $table->json('retry_intervals')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_endpoints');
    }
};
