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
        Schema::create('preventivi_pazienti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anagrafica_paziente_id')->constrained('anagrafica_pazienti')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name_originale');
            $table->enum('stato_elaborazione', ['caricato', 'in_elaborazione', 'completato', 'errore'])->default('caricato');
            $table->json('json_preventivo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preventivi_pazienti');
    }
};
