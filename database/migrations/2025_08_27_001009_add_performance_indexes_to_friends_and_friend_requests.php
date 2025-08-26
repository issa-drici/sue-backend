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
        // Index pour la table friends
        Schema::table('friends', function (Blueprint $table) {
            // Index composite pour les requêtes de recherche d'amis
            $table->index(['user_id', 'friend_id']);

            // Index pour les requêtes de comptage d'amis en commun
            $table->index('friend_id');
        });

        // Index pour la table friend_requests
        Schema::table('friend_requests', function (Blueprint $table) {
            // Index composite pour les requêtes de statut de relation
            $table->index(['sender_id', 'receiver_id']);
            $table->index(['receiver_id', 'sender_id']);

            // Index pour les requêtes par statut
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('friends', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'friend_id']);
            $table->dropIndex(['friend_id']);
        });

        Schema::table('friend_requests', function (Blueprint $table) {
            $table->dropIndex(['sender_id', 'receiver_id']);
            $table->dropIndex(['receiver_id', 'sender_id']);
            $table->dropIndex(['status']);
        });
    }
};
