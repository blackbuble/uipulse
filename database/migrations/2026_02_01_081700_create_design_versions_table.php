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
        Schema::create('design_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');

            // Version info
            $table->integer('version_number');
            $table->string('version_name')->nullable(); // Optional custom name
            $table->text('description')->nullable();

            // Snapshot data
            $table->json('snapshot'); // Complete design state at this version
            $table->json('metadata_snapshot')->nullable(); // Metadata at this version
            $table->string('image_snapshot_url')->nullable(); // Screenshot of design

            // Changes from previous version
            $table->json('changes')->nullable(); // Diff from previous version
            $table->foreignUuid('previous_version_id')->nullable()->constrained('design_versions');

            // Tags
            $table->json('tags')->nullable(); // e.g., ['milestone', 'approved', 'production']

            // Auto-versioning
            $table->boolean('is_auto_version')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['design_id', 'version_number']);
            $table->index('user_id');
            $table->index('created_at');

            // Unique constraint on version number per design
            $table->unique(['design_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_versions');
    }
};
