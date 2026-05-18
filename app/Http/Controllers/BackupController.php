<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Models\Backup;
use Carbon\Carbon;

class BackupController extends Controller
{
    public function criarBackup(Request $request)
    {
        $data = Carbon::now()->format('Ymd_His');
        $nomeArquivo = "backup_{$data}.sql";
        $caminho = "backup/{$nomeArquivo}";
        $storagePath = storage_path("app/{$caminho}");

        // DADOS FIXOS DO BANCO (ajuste conforme necessário)
        $db = '--all-databases';
        $user = 'laravel';
        $pass = 'lara';
        $host = '127.0.0.1';
        $port = '3306';

        @mkdir(dirname($storagePath), 0777, true);
        $cmd = "mysqldump --user={$user} --password='{$pass}' --host={$host} --port={$port} --routines --triggers --single-transaction --quick {$db} > {$storagePath}";
        $result = null;
        $output = null;
        exec($cmd, $output, $result);

        if ($result !== 0) {
            return response()->json(['success' => false, 'message' => 'Erro ao criar backup.']);
        }

        // Registrar no banco
        $backup = new Backup();
        $backup->descricao = $nomeArquivo;
        $backup->data_backup = Carbon::now();
        $backup->data = Carbon::now();
        $backup->save();

        return response()->json(['success' => true, 'message' => 'Backup criado com sucesso!']);
    }

    public function listarBackups()
    {
        $backups = Backup::orderBy('data_backup', 'desc')->get();
        // Adicionar caminho do arquivo para download na resposta
        $backups = $backups->map(function($item) {
            $item->nome_arquivo = $item->descricao;
            $item->caminho = 'backup/' . $item->descricao;
            return $item;
        });
        return response()->json($backups);
    }

    public function downloadBackup($id)
    {
        $backup = Backup::findOrFail($id);
        $path = storage_path('app/backup/' . $backup->descricao);
        if (!file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'Arquivo não encontrado.'], 404);
        }
        return response()->download($path, $backup->descricao);
    }
} 