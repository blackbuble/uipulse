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
        Schema::table('components', function (Blueprint $table) {
            // Versioning
            $table->string('version')->default('1.0')->after('name');
            $table->uuid('parent_component_id')->nullable()->after('design_id');
            $table->text('changelog')->nullable()->after('description');

            // Version metadata
            $table->boolean('is_latest_version')->default(true)->after('version');
            $table->timestamp('version_created_at')->nullable()->after('changelog');
            $table->foreignId('version_created_by')->nullable()->constrained('users')->nullOnDelete();

            // Foreign key for parent component
            $table->foreign('parent_component_id')
                ->references('id')
                ->on('components')
                ->nullOnDelete();

            // Indexes
            $table->index(['parent_component_id', 'version']);
            $table->index('is_latest_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropForeign(['parent_component_id']);
            $table->dropForeign(['version_created_by']);
            $table->dropIndex(['parent_component_id', 'version']);
            $table->dropIndex(['is_latest_version']);

            $table->dropColumn([
                'version',
                'parent_component_id',
                'changelog',
                'is_latest_version',
                'version_created_at',
                'version_created_by',
            ]);
        });
    }
};
