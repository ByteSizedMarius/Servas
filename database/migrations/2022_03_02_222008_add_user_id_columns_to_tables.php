<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->default(1);
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
