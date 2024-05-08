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
        Schema::create('complains', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->foreignIdFor(\App\Models\Customer::class)
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignIdFor(\App\Models\MachineType::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->foreignIdFor(\App\Models\MachineModel::class)
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->enum('type', ['open', 'closed']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complains');
    }
};
