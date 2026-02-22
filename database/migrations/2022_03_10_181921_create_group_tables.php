<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('groupables', function (Blueprint $table) {
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->string('groupable_type');
            $table->unsignedBigInteger('groupable_id');
            $table->index(['groupable_type', 'groupable_id']);
            $table->unique(['group_id', 'groupable_id', 'groupable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groupables');
        Schema::dropIfExists('groups');
    }
};
