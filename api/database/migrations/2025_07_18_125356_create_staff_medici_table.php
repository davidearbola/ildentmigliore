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
        Schema::create('staff_medici', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_user_id')->constrained('users')->onDelete('cascade');
            $table->string('nome');
            $table->string('ruolo');
            $table->text('specializzazione')->nullable();
            $table->text('esperienza')->nullable();
            $table->string('foto_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_medici');
    }
};
