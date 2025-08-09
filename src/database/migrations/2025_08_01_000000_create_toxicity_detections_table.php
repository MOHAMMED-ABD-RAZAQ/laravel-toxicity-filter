<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toxicity_detections', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->decimal('toxicity_score', 5, 4)->index();
            $table->json('categories')->nullable();
            $table->text('content_hash')->index(); // MD5 hash of content for privacy
            $table->text('content')->nullable(); // Optional: store actual content if configured
            $table->json('metadata')->nullable(); // Provider-specific metadata
            $table->string('action_taken')->nullable()->index(); // block, flag, warn, none
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('request_path')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['toxicity_score', 'created_at']);
            $table->index(['provider', 'created_at']);
            $table->index(['action_taken', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toxicity_detections');
    }
};
