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
        Schema::create('email_preventive', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preventive_id')->constrained('preventives')->cascadeOnDelete();
            $table->foreignId(column: 'email_id')->constrained('emails')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_preventive');
    }
};
