<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class TerminalController extends Controller
{
    protected $connections = [];
    protected $config;

    public function __construct()
    {
        $this->config = [
            'host' => env('SSH_HOST', 'localhost'),
            'port' => env('SSH_PORT', 22),
            'username' => env('SSH_USERNAME', ''),
            'password' => env('SSH_PASSWORD', ''),
            'key_file' => env('SSH_KEY_FILE', '')
        ];
    }

    protected function getConnection($terminalId)
    {
        if (!isset($this->connections[$terminalId])) {
            $ssh = new SSH2($this->config['host'], $this->config['port']);
            
            try {
                if ($this->config['key_file'] && file_exists($this->config['key_file'])) {
                    $key = PublicKeyLoader::load(file_get_contents($this->config['key_file']));
                    if (!$ssh->login($this->config['username'], $key)) {
                        throw new \Exception('Falha no login SSH com chave');
                    }
                } else if ($this->config['password']) {
                    if (!$ssh->login($this->config['username'], $this->config['password'])) {
                        throw new \Exception('Falha no login SSH com senha');
                    }
                } else {
                    throw new \Exception('Credenciais SSH não configuradas');
                }

                $this->connections[$terminalId] = $ssh;
            } catch (\Exception $e) {
                Log::error('Erro na conexão SSH', [
                    'error' => $e->getMessage(),
                    'terminal_id' => $terminalId
                ]);
                throw $e;
            }
        }

        return $this->connections[$terminalId];
    }

    public function execute(Request $request)
    {
        try {
            $command = trim($request->input('command'));
            $terminalId = $request->input('terminal_id', 1);
            
            if (empty($command)) {
                return response()->json([
                    'error' => 'Comando vazio'
                ]);
            }

            Log::info('Comando SSH executado', [
                'user' => auth()->user()->name ?? 'unknown',
                'command' => $command,
                'terminal_id' => $terminalId
            ]);

            $ssh = $this->getConnection($terminalId);
            
            // Executa o comando e captura a saída
            $output = $ssh->exec($command);
            
            if ($output === false) {
                throw new \Exception('Falha ao executar comando');
            }

            // Obtém o diretório atual
            $pwd = trim($ssh->exec('pwd'));

            return response()->json([
                'output' => $output,
                'pwd' => $pwd
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no terminal SSH', [
                'error' => $e->getMessage(),
                'command' => $command ?? null,
                'terminal_id' => $terminalId ?? null
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function __destruct()
    {
        // Fecha todas as conexões ao destruir o controller
        foreach ($this->connections as $ssh) {
            try {
                $ssh->disconnect();
            } catch (\Exception $e) {
                Log::error('Erro ao desconectar SSH', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
} 