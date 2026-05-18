<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('arquivos_categoria', function (Blueprint $table) {
            $table->id();
            $table->string('categoria');
            $table->unsignedBigInteger('id_categoria')->nullable();
            $table->string('capa')->nullable();
            $table->timestamps();

            $table->foreign('id_categoria')->references('id')->on('arquivos_categoria')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('arquivos_categoria');
    }
}; 