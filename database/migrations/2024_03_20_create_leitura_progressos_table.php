<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leitura_progressos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livro_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['lendo', 'lido', 'pausado'])->default('lendo');
            $table->integer('pagina_atual')->default(1);
            $table->integer('total_paginas');
            $table->timestamp('data_inicio')->nullable();
            $table->timestamp('data_conclusao')->nullable();
            $table->text('meta_estudo')->nullable();
            $table->timestamps();

            $table->unique(['livro_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('leitura_progressos');
    }
}; 