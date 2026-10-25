<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Trouver un créneau" : un invité peut proposer un créneau en plus de ceux de l'organisateur.
     * Null = créneau proposé par l'organisateur.
     */
    public function up(): void
    {
        Schema::table('session_slot_proposals', function (Blueprint $table) {
            $table->uuid('proposed_by')->nullable()->after('session_id');
            $table->foreign('proposed_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('session_slot_proposals', function (Blueprint $table) {
            $table->dropForeign(['proposed_by']);
            $table->dropColumn('proposed_by');
        });
    }
};
