<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('sistemas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nome')->unique();
            $table->string('titulo');
            $table->text('descricao');
            $table->text('comandos')->nullable();
            $table->longText('documentacao')->nullable();
            $table->string('rota')->nullable();
            $table->string('pasta')->nullable();
            $table->timestamp('data_inicio')->nullable();
            $table->string('imagem_capa')->nullable();
            $table->boolean('ativo')->default(true);
            $table->integer('ordem')->nullable();
            $table->unsignedBigInteger('autor_id')->nullable();
            $table->string('tags')->nullable();
            $table->string('categoria')->nullable();
            $table->string('slug')->unique()->nullable();
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('sistemas');
    }
}; 