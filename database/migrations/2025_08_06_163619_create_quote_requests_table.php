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
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->foreignId('assegnata_da')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('request_wp_id')->nullable()->constrained('request_wps')->nullOnDelete();//da aggiungere
            $table->date('data_ricezione_richiesta')->nullable();
            $table->string('oggetto')->nullable();
            $table->string('email_cliente')->nullable();
            $table->string('meta_viaggio')->nullable();
            $table->date('data_assegnazione')->nullable();
            $table->boolean('sono_gia_disponibile')->nullable();
            $table->boolean('cliente_gestito_da_me')->nullable();
            $table->enum('stato_richiesta', [
                'inviata',
                'risposta pervenuta',
                'in lavorazione',
                'non completata',
                'archiviata',
                'evasa',
            ])->default('inviata');
            $table->date('scadenza')->nullable();
            $table->longText('messaggio_wp')->nullable();//da aggiungere
            $table->longText('note')->nullable();
            $table->json('tipo_richieste')->nullable();
            $table->longText('motivazione_archivio')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
