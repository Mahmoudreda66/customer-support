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
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('ink')->default(false);
            $table->boolean('duplex')->default(false);
            $table->boolean('magnetic')->default(false);
            $table->boolean('shelf')->default(false);
            $table->boolean('dorg')->default(false);
        });
    }
};
