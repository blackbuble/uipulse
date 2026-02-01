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
        Schema::create('design_annotations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');

            // Annotation type
            $table->enum('type', ['arrow', 'rectangle', 'circle', 'line', 'text', 'highlight'])->default('rectangle');

            // Annotation data
            $table->json('data'); // Shape-specific data (coordinates, dimensions, etc.)
            $table->string('color')->default('#FF0000');
            $table->integer('stroke_width')->default(2);

            // Optional text/label
            $table->text('label')->nullable();

            // Link to comment
            $table->foreignUuid('comment_id')->nullable()->constrained('design_comments')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['design_id', 'type']);
            $table->index('user_id');
            $table->index('comment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_annotations');
    }
};
