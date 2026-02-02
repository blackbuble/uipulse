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
        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->foreignUuid('component_id')->nullable()->after('design_id')
                ->constrained()->cascadeOnDelete();

            // Add index for efficient queries
            $table->index(['component_id', 'type']);
            $table->index(['component_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_analyses', function (Blueprint $table) {
            $table->dropForeign(['component_id']);
            $table->dropIndex(['component_id', 'type']);
            $table->dropIndex(['component_id', 'created_at']);
            $table->dropColumn('component_id');
        });
    }
};
