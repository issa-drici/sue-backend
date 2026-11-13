<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chat d'une session (remplace les anciens commentaires, jamais sortis).
     *
     * session_chat_messages : id auto-incrémenté = ordre des messages et curseur de rattrapage
     * (« tout ce qui suit le message 120 »). client_id : identifiant créé par le téléphone avant
     * l'envoi, unique par auteur, pour qu'un message renvoyé après une coupure ne soit pas dupliqué.
     *
     * session_chat_states : par membre, dernier message lu (non-lus) et sourdine des notifications.
     */
    public function up(): void
    {
        Schema::create('session_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            $table->uuid('user_id');
            $table->uuid('client_id');
            $table->text('body');
            $table->json('mentions')->nullable();
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('sport_sessions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'client_id']);
            $table->index(['session_id', 'id']);
        });

        Schema::create('session_chat_states', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            $table->uuid('user_id');
            $table->unsignedBigInteger('last_read_message_id')->default(0);
            $table->boolean('muted')->default(false);
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('sport_sessions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_chat_states');
        Schema::dropIfExists('session_chat_messages');
    }
};
