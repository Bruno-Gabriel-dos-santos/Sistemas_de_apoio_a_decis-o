<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('backup', function (Blueprint $table) {
            $table->id();
            $table->timestamp('data_backup')->nullable();
            $table->string('descricao')->nullable();
            $table->timestamp('data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('backup');
    }
}; 