<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('design_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->foreignUuid('parent_id')->nullable()->constrained('design_comments')->cascadeOnDelete();

            // Comment content
            $table->text('content');
            $table->json('mentions')->nullable(); // Array of mentioned user IDs

            // Position on design (for pinned comments)
            $table->json('position')->nullable(); // {x, y} coordinates

            // Status
            $table->enum('status', ['open', 'resolved'])->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();

            // Metadata
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['design_id', 'status']);
            $table->index(['design_id', 'parent_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_comments');
    }
};
