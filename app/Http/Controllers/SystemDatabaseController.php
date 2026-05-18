<?php

namespace App\Http\Controllers;

use App\Models\Sistema;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SystemDatabaseController extends Controller
{
    public function tables(Sistema $sistema)
    {
        $connection = $this->connection($sistema);
        $tables = $connection->select('SHOW TABLE STATUS');
        // For more accurate row counts (SHOW TABLE STATUS may be imprecise for InnoDB), run COUNT(*) per table
        try {
            foreach ($tables as $t) {
                $name = $t->Name ?? $t->name ?? $t->Table ?? null;
                if ($name) {
                    try {
                        $cnt = $connection->selectOne("SELECT COUNT(*) AS cnt FROM `{$name}`");
                        $t->Rows = isset($cnt->cnt) ? (int)$cnt->cnt : (isset($cnt->COUNT) ? (int)$cnt->COUNT : 0);
                    } catch (\Throwable $e) {
                        // if counting fails (permissions, views, etc.), keep original Rows
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore and return what we have
        }

        return response()->json(['tables' => $tables]);
    }

    public function tableData(Sistema $sistema, string $table, Request $request)
    {
        $this->guardTableName($table);
        $connection = $this->connection($sistema);

        $limit = min(max((int) $request->input('limit', 50), 1), 500);
        $offset = max((int) $request->input('offset', 0), 0);

        $rows = $connection->table($table)->offset($offset)->limit($limit)->get();
        $columns = $connection->select("DESCRIBE `$table`");

        return response()->json([
            'rows' => $rows,
            'columns' => $columns,
        ]);
    }

    public function runSql(Sistema $sistema, Request $request)
    {
        $sql = trim($request->input('sql', ''));
        if ($sql === '') {
            return response()->json(['error' => 'SQL vazio'], 422);
        }

        $connection = $this->connection($sistema);
        try {
            if (Str::startsWith(Str::lower($sql), 'select')) {
                $result = $connection->select($sql);
                return response()->json(['result' => $result]);
            }

            $affected = $connection->affectingStatement($sql);
            return response()->json(['affected' => $affected]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function createTable(Sistema $sistema, Request $request)
    {
        $sql = trim($request->input('sql', ''));
        if ($sql === '') return response()->json(['error' => 'SQL vazio'], 422);
        $connection = $this->connection($sistema);
        try {
            $connection->statement($sql);
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function dropTable(Sistema $sistema, Request $request)
    {
        $table = $request->input('table');
        $this->guardTableName($table);
        $connection = $this->connection($sistema);
        try {
            $connection->statement("DROP TABLE IF EXISTS `{$table}`");
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function insertRow(Sistema $sistema, string $table, Request $request)
    {
        $this->guardTableName($table);
        $data = $request->input('data', []);
        if (!is_array($data)) return response()->json(['error' => 'Dados inválidos'], 422);
        $connection = $this->connection($sistema);
        try {
            $id = $connection->table($table)->insertGetId($data);
            return response()->json(['ok' => true, 'id' => $id]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function alterTable(Sistema $sistema, string $table, Request $request)
    {
        $this->guardTableName($table);
        $sql = trim($request->input('sql', ''));
        if ($sql === '') return response()->json(['error' => 'SQL vazio'], 422);

        // Basic safety: only allow ALTER/RENAME statements here
        $low = Str::lower(ltrim($sql));
        if (!Str::startsWith($low, ['alter', 'rename'])) {
            return response()->json(['error' => 'Apenas instruções ALTER/RENAME são permitidas neste endpoint.'], 403);
        }

        // Ensure table name appears in SQL (basic check)
        if (strpos($sql, $table) === false) {
            return response()->json(['error' => 'SQL deve referenciar a tabela especificada.'], 422);
        }

        $connection = $this->connection($sistema);
        try {
            $connection->statement($sql);
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateRow(Sistema $sistema, string $table, $id, Request $request)
    {
        $this->guardTableName($table);
        $data = $request->input('data', []);
        if (!is_array($data)) return response()->json(['error' => 'Dados inválidos'], 422);
        $connection = $this->connection($sistema);
        try {
            // detect primary key column
            $cols = $connection->select("DESCRIBE `{$table}`");
            $pk = null;
            foreach ($cols as $c) {
                if (isset($c->Key) && $c->Key === 'PRI') { $pk = $c->Field; break; }
            }
            if (!$pk) {
                // fallback to first column
                $pk = $cols[0]->Field ?? 'id';
            }
            $affected = $connection->table($table)->where($pk, $id)->update($data);
            return response()->json(['ok' => true, 'affected' => $affected]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deleteRow(Sistema $sistema, string $table, $id)
    {
        $this->guardTableName($table);
        $connection = $this->connection($sistema);
        try {
            $cols = $connection->select("DESCRIBE `{$table}`");
            $pk = null;
            foreach ($cols as $c) {
                if (isset($c->Key) && $c->Key === 'PRI') { $pk = $c->Field; break; }
            }
            if (!$pk) { $pk = $cols[0]->Field ?? 'id'; }
            $deleted = $connection->table($table)->where($pk, $id)->delete();
            return response()->json(['ok' => true, 'deleted' => $deleted]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    protected function connection(Sistema $sistema): Connection
    {
        if (!$sistema->db_name || !$sistema->db_username) {
            abort(400, 'Sistema sem credenciais de banco configuradas.');
        }

        $connectionName = 'sistema_db_' . $sistema->id;

        if (!Config::has("database.connections.$connectionName")) {
            Config::set("database.connections.$connectionName", [
                'driver' => 'mysql',
                'host' => $sistema->db_host ?: config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'database' => $sistema->db_name,
                'username' => $sistema->db_username,
                'password' => $sistema->db_password,
                'charset' => config('database.connections.mysql.charset', 'utf8mb4'),
                'collation' => config('database.connections.mysql.collation', 'utf8mb4_unicode_ci'),
                'prefix' => '',
                'strict' => false,
                'engine' => null,
            ]);
        }

        return DB::connection($connectionName);
    }

    protected function guardTableName(string $table): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            abort(400, 'Nome de tabela inválido.');
        }
    }
}

