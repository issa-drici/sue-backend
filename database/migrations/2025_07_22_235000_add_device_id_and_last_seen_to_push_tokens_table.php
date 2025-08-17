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
        Schema::table('push_tokens', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('platform');
            $table->timestamp('last_seen_at')->nullable()->after('device_id');
            $table->index(['user_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('push_tokens', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'device_id']);
            $table->dropColumn(['device_id', 'last_seen_at']);
        });
    }
};


