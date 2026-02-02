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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url')->nullable()->after('email');
            $table->string('job_title')->nullable()->after('avatar_url');
            $table->string('department')->nullable()->after('job_title');
            $table->string('phone')->nullable()->after('department');
            $table->text('bio')->nullable()->after('phone');
            $table->json('preferences')->nullable()->after('bio');
            $table->timestamp('last_active_at')->nullable()->after('preferences');
            $table->boolean('is_active')->default(true)->after('last_active_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_url',
                'job_title',
                'department',
                'phone',
                'bio',
                'preferences',
                'last_active_at',
                'is_active',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
