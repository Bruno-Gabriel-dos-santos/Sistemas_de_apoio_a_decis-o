<?php

namespace App\Services;

use App\Models\Sistema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SystemDatabaseProvisioner
{
    public function provision(Sistema $sistema): array
    {
        $dbName = 'api_sys_' . $sistema->id;
        $dbUser = 'api_user_' . $sistema->id;
        $dbPass = Str::random(32);
        $host = config('database.provisioner.host', 'localhost');
        $charset = config('database.connections.mysql.charset', 'utf8mb4');
        $collation = config('database.connections.mysql.collation', 'utf8mb4_unicode_ci');

        DB::statement("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET {$charset} COLLATE {$collation}");
        DB::statement("DROP USER IF EXISTS '$dbUser'@'{$host}'");
        DB::statement("CREATE USER '$dbUser'@'{$host}' IDENTIFIED BY '$dbPass'");
        DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER ON `$dbName`.* TO '$dbUser'@'{$host}'");
        DB::statement('FLUSH PRIVILEGES');

        return [
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPass,
            'host' => $host,
        ];
    }
}

