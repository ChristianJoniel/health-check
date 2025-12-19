<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('health_status')->default('unknown')->after('is_active');
            $table->integer('consecutive_failures')->default(0)->after('health_status');
            $table->timestamp('first_failed_at')->nullable()->after('consecutive_failures');
            $table->timestamp('last_failed_at')->nullable()->after('first_failed_at');
            $table->timestamp('last_notification_sent_at')->nullable()->after('last_failed_at');
            $table->timestamp('last_recovered_at')->nullable()->after('last_notification_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'health_status',
                'consecutive_failures',
                'first_failed_at',
                'last_failed_at',
                'last_notification_sent_at',
                'last_recovered_at',
            ]);
        });
    }
};
