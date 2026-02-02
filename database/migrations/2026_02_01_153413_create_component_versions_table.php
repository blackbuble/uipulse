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
        Schema::create('component_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('component_id')->constrained()->cascadeOnDelete();

            // Version information
            $table->string('version_number'); // 1.0, 1.1, 2.0, etc.
            $table->string('version_tag')->nullable(); // beta, rc, stable
            $table->string('branch')->default('main'); // main, experimental, feature-x

            // Snapshot of component state at this version
            $table->json('snapshot'); // Full component data at this point
            $table->json('diff')->nullable(); // Diff from previous version

            // Metadata
            $table->text('changelog')->nullable();
            $table->text('breaking_changes')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Relationships
            $table->foreignUuid('previous_version_id')->nullable()->constrained('component_versions')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['component_id', 'version_number']);
            $table->index(['component_id', 'branch']);
            $table->index(['component_id', 'is_published']);
            $table->index('created_at');
            $table->unique(['component_id', 'version_number', 'branch']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('component_versions');
    }
};
