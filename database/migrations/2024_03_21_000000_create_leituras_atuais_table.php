<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leituras_atuais', function (Blueprint $table) {
            $table->id();
            $table->string('titulo_livro');
            $table->integer('pagina_atual')->default(0);
            $table->integer('total_paginas')->nullable();
            $table->date('meta_conclusao')->nullable();
            $table->text('notas_leitura')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leituras_atuais');
    }
}; 