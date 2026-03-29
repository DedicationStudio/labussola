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
        Schema::create('preventives', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_preventivo', [
                'libero',
                'con_richiesta',
            ])->default('libero');
            $table->foreignId('quote_request_id')->nullable()->constrained('quote_requests')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('itinerary_id')->nullable()->constrained('itineraries')->nullOnDelete();
            $table->string('cod_alfa')->nullable();
            $table->string('meta_viaggio')->nullable();
            $table->string('nome_itinerario')->nullable();
            $table->boolean('gita_giornaliera')->nullable();
            $table->boolean('allego_file')->nullable();
            $table->json('itinerario')->nullable();
            $table->string('titolo')->nullable();
            $table->integer('numero')->nullable();
            $table->integer('anno')->nullable();
            $table->date('data_preventivo')->nullable();
            $table->date('data_invio')->nullable();
            $table->string('tag')->nullable();
            $table->string('email_cliente')->nullable();
            $table->integer('numero_persone')->nullable();
            $table->integer('prezzo_per_persona')->nullable();
            $table->integer('numero_gratuita')->nullable();
            $table->integer('markup')->nullable();
            $table->date('data_inizio_viaggio')->nullable();
            $table->date('data_fine_viaggio')->nullable();
            $table->string('luogo_di_partenza_andata')->nullable();
            $table->string('luogo_di_arrivo_andata')->nullable();
            $table->datetime('data_ora_partenza_andata')->nullable();
            $table->datetime('data_ora_arrivo_andata')->nullable();
            $table->string('luogo_di_partenza_rientro')->nullable();
            $table->string('luogo_di_arrivo_rientro')->nullable();
            $table->datetime('data_ora_partenza_rientro')->nullable();
            $table->datetime('data_ora_arrivo_rientro')->nullable();
            $table->string('foto_introduttiva')->nullable();
            $table->json('immagini')->nullable();
            $table->integer('n_persone_forzato')->nullable();
            $table->integer('prezzo_forzato')->nullable();
            $table->integer('costo_polizza')->nullable();
            $table->text('quota_comprende_generico')->nullable();
            $table->text('quota_non_comprende_generico')->nullable();
            $table->json('files_pratica_accettata')->nullable();
            $table->string('file_preventivo')->nullable();
            $table->string('file_polizza')->nullable();
            $table->date('date_expiration')->nullable();
            $table->text('note')->nullable();
            $table->enum('stato', [
                'inviato',
                'in attesa',
                'accettato',
                'incompleto',
                'rifiutato',
                'visualizzato',
                'bozza',
                'da inviare',
                'interesse più tempo',
                'superiore budget',
                'oltre tempi',
                'programma non interessa',
                'rivedere proposta',
                'altro'
            ])->default('in attesa');
            $table->text('stato_altro_testo')->nullable();
            $table->longText('campo_attenzione')->nullable();
            $table->integer('totale_incasso')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preventives');
    }
};
