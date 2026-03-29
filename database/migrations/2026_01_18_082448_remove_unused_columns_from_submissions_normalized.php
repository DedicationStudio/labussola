<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions_normalized', function (Blueprint $table) {
            $table->dropColumn([
                'lastname',
                'phone',
                'message',
                'prov_scuola',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('submissions_normalized', function (Blueprint $table) {
            $table->text('lastname')->nullable();
            $table->text('phone')->nullable();
            $table->text('message')->nullable();
            $table->string('prov_scuola')->nullable();
        });
    }
};
