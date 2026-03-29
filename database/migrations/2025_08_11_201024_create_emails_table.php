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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->enum('tipo_preventivo', [
                'libero',
                'con_richiesta',
            ])->default('libero');
            $table->foreignId('quote_request_id')->nullable()->constrained('quote_requests')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->cascadeOnDelete();
            $table->string('slug')->nullable();
            $table->string('email_cliente')->nullable();
            $table->json('email_cc')->nullable();
            $table->longText('corpo_email')->nullable();
            $table->json('allegati')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
