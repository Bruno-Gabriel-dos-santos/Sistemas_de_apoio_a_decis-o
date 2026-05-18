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
        Schema::rename('interfaces_de_hiperprocessamento', 'relatorios_situacionais');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('relatorios_situacionais', 'interfaces_de_hiperprocessamento');
    }
};
