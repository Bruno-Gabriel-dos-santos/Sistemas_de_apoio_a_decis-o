<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('registros_leitura', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['metas', 'historico']);
            $table->longText('conteudo');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('registros_leitura');
    }
}; 