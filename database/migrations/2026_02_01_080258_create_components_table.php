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
        Schema::create('components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('organization_id')->nullable();

            // Component identification
            $table->string('type'); // button, input, card, modal, etc.
            $table->string('name');
            $table->text('description')->nullable();

            // Component data
            $table->json('properties'); // size, colors, spacing, typography, etc.
            $table->json('figma_node'); // Original Figma node data
            $table->json('bounding_box'); // x, y, width, height

            // Categorization
            $table->string('category')->nullable(); // navigation, form, layout, overlay
            $table->string('subcategory')->nullable();

            // Usage tracking
            $table->integer('usage_count')->default(1);
            $table->integer('variant_count')->default(0);

            // Metadata
            $table->string('thumbnail_url')->nullable();
            $table->boolean('is_in_library')->default(false);
            $table->timestamp('added_to_library_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['design_id', 'type']);
            $table->index(['organization_id', 'category']);
            $table->index(['type', 'is_in_library']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
