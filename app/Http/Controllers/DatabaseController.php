<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class DatabaseController extends Controller
{
    public function selectDatabase(Request $request)
    {
        try {
            $request->validate([
                'database_name' => 'required|string|max:64'
            ]);

            $dbName = $request->database_name;
            
            // Cria uma nova conexão temporária para verificar o banco
            $tempConnection = [
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'database' => $dbName
            ];

            try {
                $pdo = new \PDO(
                    "mysql:host={$tempConnection['host']};dbname={$dbName}",
                    $tempConnection['username'],
                    $tempConnection['password']
                );
                
                // Se chegou aqui, o banco existe e está acessível
                session(['selected_database' => $dbName]);
                
                return back()->with('success', "Banco de dados '$dbName' selecionado com sucesso!");
            } catch (\PDOException $e) {
                return back()->with('error', 'Banco de dados não encontrado ou inacessível.');
            }
        } catch (Exception $e) {
            return back()->with('error', 'Erro ao selecionar banco de dados: ' . $e->getMessage());
        }
    }

    public function index()
    {
        try {
            // Conexão principal para listar bancos
            $mainConnection = DB::connection();
            $databases = $mainConnection->select("SHOW DATABASES");
            $tables = [];
            $currentDb = session('selected_database', config('database.connections.mysql.database'));

            if ($currentDb) {
                try {
                    // Cria uma nova conexão para o banco selecionado
                    $connection = new \PDO(
                        "mysql:host=" . config('database.connections.mysql.host') . ";dbname=" . $currentDb,
                        config('database.connections.mysql.username'),
                        config('database.connections.mysql.password')
                    );

                    // Obtém as tabelas do banco selecionado
                    $stmt = $connection->query("SHOW TABLES FROM `$currentDb`");
                    $tablesQuery = $stmt->fetchAll(\PDO::FETCH_NUM);

                    foreach ($tablesQuery as $table) {
                        $tableName = $table[0];
                        
                        // Obtém informações da tabela
                        $rowCount = $connection->query("SELECT COUNT(*) FROM `$currentDb`.`$tableName`")->fetchColumn();
                        $columnsCount = $connection->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '$currentDb' AND TABLE_NAME = '$tableName'")->fetchColumn();
                        $sizeQuery = $connection->query("SELECT 
                            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size 
                            FROM information_schema.TABLES 
                            WHERE table_schema = '$currentDb' 
                            AND table_name = '$tableName'")->fetch(\PDO::FETCH_ASSOC);

                        $tables[$tableName] = [
                            'name' => $tableName,
                            'rows' => $rowCount,
                            'columns' => $columnsCount,
                            'size' => $sizeQuery['size'] ?? 0
                        ];
                    }
                } catch (\PDOException $e) {
                    // Se houver erro ao conectar no banco selecionado, limpa a seleção
                    session()->forget('selected_database');
                    return back()->with('error', 'Erro ao acessar o banco selecionado: ' . $e->getMessage());
                }
            }

            return view('databases.index', compact('databases', 'tables', 'currentDb'));
        } catch (Exception $e) {
            return back()->with('error', 'Erro ao carregar informações: ' . $e->getMessage());
        }
    }

    public function createDatabase(Request $request)
    {
        try {
            $request->validate([
                'database_name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/'
            ]);

            DB::statement("CREATE DATABASE `{$request->database_name}`");
            return back()->with('success', 'Banco de dados criado com sucesso!');
        } catch (Exception $e) {
            return back()->with('error', 'Erro ao criar banco de dados: ' . $e->getMessage());
        }
    }

    public function dropDatabase(Request $request)
    {
        try {
            $request->validate([
                'database_name' => 'required|string|max:64'
            ]);

            if ($request->database_name === config('database.connections.mysql.database')) {
                return back()->with('error', 'Não é possível excluir o banco de dados atual!');
            }

            DB::statement("DROP DATABASE `{$request->database_name}`");
            return back()->with('success', 'Banco de dados excluído com sucesso!');
        } catch (Exception $e) {
            return back()->with('error', 'Erro ao excluir banco de dados: ' . $e->getMessage());
        }
    }

    public function createTable(Request $request)
    {
        try {
            $request->validate([
                'table_name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
                'columns' => 'required|array|min:1',
                'columns.*.name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
                'columns.*.type' => 'required|string',
                'columns.*.length' => 'nullable|integer|min:1',
            ]);

            $sql = "CREATE TABLE `{$request->table_name}` (";
            $columns = [];

            foreach ($request->columns as $column) {
                $columnDef = "`{$column['name']}` {$column['type']}";
                if (!empty($column['length']) && in_array(strtoupper($column['type']), ['VARCHAR', 'CHAR', 'INT', 'BIGINT', 'DECIMAL'])) {
                    $columnDef .= "({$column['length']})";
                }
                // Verifica se o campo nullable existe no request e está marcado como true
                $isNullable = isset($column['nullable']) && $column['nullable'] === 'on';
                $columnDef .= $isNullable ? " NULL" : " NOT NULL";
                $columns[] = $columnDef;
            }

            $sql .= implode(', ', $columns) . ")";
            DB::statement($sql);

            return back()->with('success', 'Tabela criada com sucesso!');
        } catch (Exception $e) {
            return back()->with('error', 'Erro ao criar tabela: ' . $e->getMessage());
        }
    }

    public function dropTable(Request $request)
    {
        try {
            $request->validate([
                'table_name' => 'required|string|max:64'
            ]);

            DB::statement("DROP TABLE `{$request->table_name}`");
            return back()->with('success', 'Tabela excluída com sucesso!');
        } catch (Exception $e) {
            return back()->with('error', 'Erro ao excluir tabela: ' . $e->getMessage());
        }
    }

    public function showTableDetails($table)
    {
        try {
            $perPage = request()->get('per_page', 5);
            $page = request()->get('page', 1);
            $perPage = in_array($perPage, [5, 10, 25]) ? $perPage : 5;
            $offset = ($page - 1) * $perPage;
            
            // Obter o banco de dados selecionado da sessão
            $currentDb = session('selected_database');
            if (!$currentDb) {
                return back()->with('error', 'Nenhum banco de dados selecionado.');
            }

            // Criar conexão PDO para o banco selecionado
            $connection = new \PDO(
                "mysql:host=" . config('database.connections.mysql.host') . ";dbname=" . $currentDb,
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password')
            );
            
            // Obter estrutura da tabela
            $columnsQuery = $connection->query("SHOW FULL COLUMNS FROM `$table`");
            $columns = $columnsQuery->fetchAll(\PDO::FETCH_OBJ);
            
            // Obter índices
            $indexesQuery = $connection->query("SHOW INDEX FROM `$table`");
            $indexes = $indexesQuery->fetchAll(\PDO::FETCH_OBJ);
            
            // Obter total de registros
            $totalRows = $connection->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            
            // Calcular total de páginas
            $totalPages = ceil($totalRows / $perPage);
            
            // Obter amostra de dados
            $sampleQuery = $connection->query("SELECT * FROM `$table` LIMIT $offset, $perPage");
            $sample = $sampleQuery->fetchAll(\PDO::FETCH_OBJ);

            // Adicionar variáveis para pesquisa
            $selectedColumn = request()->get('column');
            $searchTerm = request()->get('search');
            
            return view('databases.table-details', compact(
                'table',
                'columns',
                'indexes',
                'sample',
                'totalRows',
                'perPage',
                'page',
                'totalPages',
                'selectedColumn',
                'searchTerm'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao carregar detalhes da tabela: ' . $e->getMessage());
        }
    }

    public function searchTable(Request $request)
    {
        try {
            $table = $request->input('table_name');
            $searchTerm = $request->input('search');
            $selectedColumn = $request->input('column');
            $perPage = request()->get('per_page', 5);
            
            // Obter o banco de dados selecionado da sessão
            $currentDb = session('selected_database');
            if (!$currentDb) {
                return back()->with('error', 'Nenhum banco de dados selecionado.');
            }

            // Criar conexão PDO para o banco selecionado
            $connection = new \PDO(
                "mysql:host=" . config('database.connections.mysql.host') . ";dbname=" . $currentDb,
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password')
            );
            
            // Obter estrutura da tabela
            $columnsQuery = $connection->query("SHOW FULL COLUMNS FROM `$table`");
            $columns = $columnsQuery->fetchAll(\PDO::FETCH_OBJ);
            
            // Obter índices
            $indexesQuery = $connection->query("SHOW INDEX FROM `$table`");
            $indexes = $indexesQuery->fetchAll(\PDO::FETCH_OBJ);
            
            // Construir a query de pesquisa
            $searchQuery = "SELECT * FROM `$table`";
            if ($searchTerm && $selectedColumn) {
                $searchQuery .= " WHERE `$selectedColumn` LIKE :search";
                $countQuery = "SELECT COUNT(*) FROM `$table` WHERE `$selectedColumn` LIKE :search";
            } else {
                $countQuery = "SELECT COUNT(*) FROM `$table`";
            }
            
            // Preparar e executar a query de contagem
            $countStmt = $connection->prepare($countQuery);
            if ($searchTerm && $selectedColumn) {
                $countStmt->bindValue(':search', "%$searchTerm%");
            }
            $countStmt->execute();
            $totalRows = $countStmt->fetchColumn();
            
            // Calcular paginação
            $page = request()->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $totalPages = ceil($totalRows / $perPage);
            
            // Adicionar paginação à query de pesquisa
            $searchQuery .= " LIMIT $offset, $perPage";
            
            // Executar a query de pesquisa
            $stmt = $connection->prepare($searchQuery);
            if ($searchTerm && $selectedColumn) {
                $stmt->bindValue(':search', "%$searchTerm%");
            }
            $stmt->execute();
            $sample = $stmt->fetchAll(\PDO::FETCH_OBJ);
            
            return view('databases.table-details', compact(
                'table',
                'columns',
                'indexes',
                'sample',
                'totalRows',
                'perPage',
                'page',
                'totalPages',
                'selectedColumn',
                'searchTerm'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao pesquisar: ' . $e->getMessage());
        }
    }

    public function editRecord(Request $request, $table, $id)
    {
        try {
            $record = DB::table($table)->where('id', $id)->first();
            $columns = Schema::getColumnListing($table);
            
            return view('databases.edit-record', compact('record', 'columns', 'table', 'id'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao carregar registro: ' . $e->getMessage());
        }
    }

    public function updateRecord(Request $request, $table, $id)
    {
        try {
            $data = $request->except(['_token', '_method']);
            DB::table($table)->where('id', $id)->update($data);
            
            return redirect()->route('databases.table.details', $table)
                           ->with('success', 'Registro atualizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao atualizar: ' . $e->getMessage());
        }
    }
} 