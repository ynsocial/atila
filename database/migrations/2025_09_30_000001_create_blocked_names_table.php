<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blocked_names', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('reason')->nullable();
            $table->string('added_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_names');
    }
};

