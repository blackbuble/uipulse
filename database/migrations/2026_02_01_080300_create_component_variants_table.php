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
        Schema::create('component_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('component_id')->constrained()->cascadeOnDelete();

            // Variant identification
            $table->string('variant_name'); // primary, secondary, disabled, hover, etc.
            $table->text('description')->nullable();

            // Variant-specific properties
            $table->json('properties'); // Colors, sizes, states specific to this variant
            $table->json('figma_node')->nullable(); // Figma node data for this variant

            // State information
            $table->string('state')->nullable(); // default, hover, active, disabled, focus
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['component_id', 'variant_name']);
            $table->index(['component_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('component_variants');
    }
};
