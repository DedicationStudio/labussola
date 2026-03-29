<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('submissions_normalized', function (Blueprint $table) {
        $table->string('telefono')->nullable();
        $table->text('messaggio')->nullable();
        $table->string('acc_privacy')->nullable();
        $table->string('acc_newsletter')->nullable();
        $table->string('acc_whatsapp')->nullable();
        $table->string('citta_scuola')->nullable();
        $table->string('classe')->nullable();
        $table->string('grado')->nullable();
        $table->string('telefono_scuola')->nullable();
        $table->string('ruolo')->nullable();
        $table->string('scuola')->nullable();
        $table->string('durata')->nullable();
        $table->string('trasporto')->nullable();
        $table->string('meta_viaggio')->nullable();
        $table->string('citta_partenza')->nullable();
        $table->integer('num_studenti')->nullable();
        $table->integer('num_docenti')->nullable();
        $table->string('disabili')->nullable();
        $table->string('periodo')->nullable();
        $table->text('altre_info')->nullable();
        $table->text('viaggio')->nullable();
        $table->text('lastname')->nullable();
        $table->text('phone')->nullable();
        $table->text('message')->nullable();
        $table->string('prov_scuola')->nullable();
    });
}

public function down(): void
{
    Schema::table('submissions_normalized', function (Blueprint $table) {
        $table->dropColumn([
            'telefono','messaggio','acc_privacy','acc_newsletter','acc_whatsapp',
            'citta_scuola','classe','grado','telefono_scuola','ruolo','scuola','durata',
            'trasporto','meta_viaggio','citta_partenza','num_studenti','num_docenti',
            'disabili','periodo','altre_info','viaggio','lastname','phone','message','prov_scuola'
        ]);
    });
}
};

