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
        Schema::create('hotel_preventive', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preventive_id')->nullable()->constrained('preventives')->cascadeOnDelete();
            $table->foreignId('hotel_id')->nullable()->constrained('hotels')->cascadeOnDelete();
            $table->text('quota_comprende_hotel')->nullable();
            $table->text('quota_non_comprende_hotel')->nullable();
            $table->json('file_fornitore_hotel')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_preventive');
    }
};
