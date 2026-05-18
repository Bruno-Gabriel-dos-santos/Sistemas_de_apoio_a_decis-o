<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('sistemas', function (Blueprint $table) {
            $table->string('db_name')->nullable();
            $table->string('db_username')->nullable();
            $table->string('db_password')->nullable();
            $table->string('db_host')->nullable();
        });
    }

    public function down()
    {
        Schema::table('sistemas', function (Blueprint $table) {
            $table->dropColumn(['db_name', 'db_username', 'db_password', 'db_host']);
        });
    }
};

