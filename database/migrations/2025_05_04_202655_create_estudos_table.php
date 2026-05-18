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
        Schema::create('estudos', function (Blueprint $table) {
            $table->id();
            $table->string('capa');
            $table->string('titulo');
            $table->string('descricao');
            $table->text('conteudo');
            $table->date('data');
            $table->string('tag')->nullable();
            $table->string('autor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudos');
    }
};
