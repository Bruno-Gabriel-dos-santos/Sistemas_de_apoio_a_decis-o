<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('arquivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('categoria');
            $table->string('path');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->timestamp('data')->nullable();
            $table->bigInteger('tamanho_arquivo')->nullable();
            $table->enum('tipo', ['arquivo', 'pasta'])->default('arquivo');
            $table->timestamps();

            $table->foreign('categoria')->references('id')->on('arquivos_categoria')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('arquivos');
    }
}; 