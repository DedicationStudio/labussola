<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('preventive_extra_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preventive_id')->constrained('preventives')->cascadeOnDelete();
            $table->foreignId('extra_service_id')->constrained('extra_services')->cascadeOnDelete();
            $table->enum('tipo_costo', ['a_persona', 'una_tantum'])->default('a_persona');
            $table->integer('prezzo')->nullable();
            $table->integer('quantita')->nullable();
            $table->integer('quantita_a_persona')->nullable();
            $table->boolean('scorpora_servizio')->default(false);
            $table->longText('descrizione_servizio')->nullable();
            $table->text('quota_comprende_servizi')->nullable();
            $table->text('quota_non_comprende_servizi')->nullable();
            $table->json('file_fornitore_servizi_extra')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preventive_extra_services');
    }
};
