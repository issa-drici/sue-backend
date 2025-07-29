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
        Schema::table('notifications', function (Blueprint $table) {
            $table->boolean('push_sent')->default(false)->after('read_at');
            $table->timestamp('push_sent_at')->nullable()->after('push_sent');
            $table->json('push_data')->nullable()->after('push_sent_at'); // Données supplémentaires pour la notification push
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['push_sent', 'push_sent_at', 'push_data']);
        });
    }
};
