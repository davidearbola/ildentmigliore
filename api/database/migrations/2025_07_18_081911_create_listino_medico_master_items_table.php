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
        Schema::create('listino_medico_master_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('listino_master_id')->constrained('listino_master')->onDelete('cascade');
            $table->decimal('prezzo', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['medico_user_id', 'listino_master_id'], 'medico_master_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listino_medico_master_items');
    }
};
