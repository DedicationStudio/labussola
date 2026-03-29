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
        Schema::create('preventive_transports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preventive_id')->nullable()->constrained('preventives')->cascadeOnDelete();
            $table->foreignId('transport_id')->nullable()->constrained('transports')->nullOnDelete();
            $table->foreignId('transport_company_id')->nullable()->constrained('transport_companies')->cascadeOnDelete()->nullOnDelete();
            $table->enum('direzione_trasporto', [
                'andata',
                'rientro',
                'intermedio'
            ])->default('andata')->nullable();
            $table->enum('tipo_trasporto', [
                'aereo',
                'bus',
                'treno',
                'altro'
            ])->default('aereo')->nullable();
            $table->enum('tipo_costo', [
                'una tantum',
                'a persona',
            ])->default('una tantum')->nullable();
            $table->string('luogo_di_partenza_andata')->nullable();
            $table->string('luogo_di_arrivo_andata')->nullable();
            $table->datetime('data_ora_partenza_andata')->nullable();
            $table->datetime('data_ora_arrivo_andata')->nullable();
            $table->string('luogo_di_partenza_rientro')->nullable();
            $table->string('luogo_di_arrivo_rientro')->nullable();
            $table->datetime('data_ora_partenza_rientro')->nullable();
            $table->datetime('data_ora_arrivo_rientro')->nullable();
            $table->integer('prezzo')->nullable();
            $table->boolean('scorpora_trasporto')->default(false);
            $table->integer('kg_bg_a_mano')->nullable();
            $table->integer('kg_bg_in_stiva')->nullable();
            $table->string('misura_bg_a_mano')->nullable();
            $table->text('quota_comprende_trasporti')->nullable();
            $table->text('quota_non_comprende_trasporti')->nullable();
            $table->json('file_fornitore_trasporto')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preventive_transports');
    }
};
