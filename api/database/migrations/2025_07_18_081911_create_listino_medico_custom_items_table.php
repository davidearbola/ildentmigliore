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
        Schema::create('listino_medico_custom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_user_id')->constrained('users')->onDelete('cascade');
            $table->string('nome');
            $table->text('descrizione')->nullable();
            $table->decimal('prezzo', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listino_medico_custom_items');
    }
};
