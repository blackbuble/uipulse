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
        Schema::create('accessibility_issues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('component_id')->nullable()->constrained()->cascadeOnDelete();

            // Issue details
            $table->string('type'); // contrast, text_size, touch_target, alt_text, heading_hierarchy, etc.
            $table->string('wcag_criterion'); // e.g., "1.4.3", "2.5.5", "4.1.2"
            $table->string('wcag_level'); // A, AA, AAA
            $table->enum('severity', ['critical', 'high', 'medium', 'low'])->default('medium');

            // Issue description
            $table->string('title');
            $table->text('description');
            $table->text('recommendation');

            // Technical details
            $table->json('details')->nullable(); // Specific values, calculations, etc.
            $table->json('element_info')->nullable(); // Element selector, position, etc.

            // Resolution
            $table->enum('status', ['open', 'in_progress', 'resolved', 'ignored'])->default('open');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['design_id', 'severity']);
            $table->index(['design_id', 'status']);
            $table->index(['component_id', 'type']);
            $table->index('wcag_criterion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accessibility_issues');
    }
};
