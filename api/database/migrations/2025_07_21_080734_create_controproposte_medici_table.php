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
        Schema::create('controproposte_medici', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preventivo_paziente_id')->constrained('preventivi_pazienti')->onDelete('cascade');
            $table->foreignId('medico_user_id')->constrained('users')->onDelete('cascade');
            $table->json('json_proposta');
            $table->enum('stato', ['inviata', 'visualizzata', 'accettata', 'rifiutata'])->default('inviata');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controproposte_medici');
    }
};
