<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('api_sistemas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sistema_id');
            $table->string('titulo');
            $table->text('descricao');
            $table->timestamp('data')->nullable();
            $table->longText('conteudo');
            $table->json('imagens')->nullable();
            $table->json('videos')->nullable();
            $table->json('musicas')->nullable();
            $table->json('graficos')->nullable();
            $table->json('assets')->nullable();
            $table->unsignedBigInteger('autor_id')->nullable();
            $table->string('tags')->nullable();
            $table->integer('ordem')->nullable();
            $table->string('tipo')->default('post');
            $table->boolean('publicado')->default(true);
            $table->string('slug')->nullable();
            $table->boolean('destaque')->default(false);
            $table->timestamps();

            $table->foreign('sistema_id')->references('id')->on('sistemas')->onDelete('cascade');
        });
    }
    public function down()
    {
        Schema::dropIfExists('api_sistemas');
    }
}; 