<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('path');
            $table->text('url');
            $table->string('mime_type', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
}; 