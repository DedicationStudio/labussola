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
        Schema::create('hotel_preventive_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_preventive_id')->nullable()->constrained('hotel_preventive')->cascadeOnDelete();
            $table->string('tipologia_stanza')->nullable();
            $table->enum('tipo_costo', ['a persona', 'a camera'])->default('a persona');
            $table->integer('quantita_camere')->nullable();
            $table->integer('n_notti')->nullable();
            $table->integer('costo_notte')->nullable();
            $table->integer('numero_gratuita_stanza')->nullable();
            $table->integer('numero_paganti_stanza')->nullable();
            $table->boolean('gratuita')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_preventive_rooms');
    }
};
