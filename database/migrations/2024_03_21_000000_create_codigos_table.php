<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('codigos', function (Blueprint $table) {
            $table->id();
            $table->string('nome_projeto');
            $table->string('tipo_linguagem');
            $table->string('link_github')->nullable();
            $table->string('link_gitlab')->nullable();
            $table->string('hash_identidade')->unique();
            $table->text('descricao');
            $table->timestamp('data_publicacao');
            $table->timestamp('data_inicio');
            $table->string('path_arquivo');
            $table->string('categoria');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('codigos');
    }
}; 