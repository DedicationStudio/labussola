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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reliability_id')->nullable()->constrained('reliabilities')->cascadeOnDelete();
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('ragione_sociale')->nullable();
            $table->string('piva_cf')->nullable();
            $table->string('codice_fiscale')->nullable();
            $table->string('indirizzo')->nullable();
            $table->json('email')->nullable();
            $table->json('telefono')->nullable();
            $table->json('sito_web')->nullable();
            $table->json('portale_web')->nullable();
            $table->string('regione')->nullable();
            $table->string('stato')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap')->nullable();
            $table->string('provincia')->nullable();
            $table->text('descrizione')->nullable();
            $table->json('allegati')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
