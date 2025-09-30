<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('flags')->nullable();
            $table->timestamps();
            $table->index(['name', 'created_at']);
            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_submissions');
    }
};

