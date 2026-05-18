<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livros', function (Blueprint $table) {
            $table->id();
            $table->string('hash')->unique()->nullable();
            $table->string('titulo');
            $table->string('autor')->nullable();
            $table->string('categoria');
            $table->string('genero');
            $table->string('materia')->nullable();
            $table->date('data_publicacao');
            $table->text('descricao')->nullable();
            $table->string('arquivo_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->enum('status', ['pendente', 'validado', 'uploading', 'completo', 'erro'])->default('pendente');
            $table->string('capa_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livros');
    }
}; 