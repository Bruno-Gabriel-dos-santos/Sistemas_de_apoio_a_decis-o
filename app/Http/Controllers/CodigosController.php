<?php

namespace App\Http\Controllers;

use App\Models\Codigo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use App\Services\Streaming\StreamingConfigService;

class CodigosController extends Controller
{
    public function __construct(private StreamingConfigService $streamingConfig)
    {
    }

    public function index(Request $request)
    {
        try {
            $query = Codigo::query();

            // Filtro por linguagem
            if ($request->filled('language')) {
                $query->where('tipo_linguagem', $request->language);
            }

            // Pesquisa por nome do projeto
            if ($request->filled('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('nome_projeto', 'like', '%' . $request->search . '%')
                      ->orWhere('descricao', 'like', '%' . $request->search . '%');
                });
            }

            // Ordenar por mais recentes
            $query->orderBy('created_at', 'desc');

            // Paginar 6 itens por página
            $codigos = $query->paginate(6);

            if ($request->ajax()) {
                $view = view('codigos.partials.card-list', ['codigos' => $codigos])->render();
                return response()->json([
                    'success' => true,
                    'html' => $view,
                    'current_page' => $codigos->currentPage(),
                    'last_page' => $codigos->lastPage(),
                    'total' => $codigos->total()
                ]);
            }

            return view('codigos.index', [
                'codigos' => $codigos,
                'filtros' => [
                    'language' => $request->language,
                    'search' => $request->search
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao carregar projetos: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao carregar os projetos. Por favor, tente novamente.'
                ], 500);
            }

            return view('codigos.index', [
                'codigos' => new \Illuminate\Pagination\LengthAwarePaginator(
                    Collection::make([]),
                    0,
                    6,
                    1,
                    ['path' => $request->url()]
                ),
                'error' => 'Erro ao carregar os projetos. Por favor, tente novamente.'
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nome_projeto' => 'required|string|max:255',
                'tipo_linguagem' => 'required|string|max:255',
                'categoria' => 'required|string|max:255',
                'descricao' => 'required|string',
                'link_github' => 'nullable|url|max:255',
                'link_gitlab' => 'nullable|url|max:255'
            ]);

            // Gerar hash único
            $hashIdentidade = Str::random(40);
            
            // Criar o caminho da pasta do projeto
            $projetoPasta = 'codigos/' . $hashIdentidade;
            
            // Criar a pasta do projeto no storage
            if (!Storage::exists($projetoPasta)) {
                Storage::makeDirectory($projetoPasta);
            }

            $codigo = new Codigo($validated);
            $codigo->hash_identidade = $hashIdentidade;
            $codigo->data_publicacao = now();
            $codigo->data_inicio = now();
            $codigo->path_arquivo = $projetoPasta;
            
            $codigo->save();

            return response()->json([
                'success' => true,
                'message' => 'Projeto criado com sucesso!'
            ]);

        } catch (\Exception $e) {
            // Se ocorrer algum erro, tentar limpar a pasta criada
            if (isset($projetoPasta) && Storage::exists($projetoPasta)) {
                Storage::deleteDirectory($projetoPasta);
            }

            Log::error('Erro ao criar projeto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar o projeto. Por favor, tente novamente.'
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $request->validate([
            'password' => 'required'
        ]);

        // Senha para exclusão (você deve alterar isso para uma senha mais segura)
        $deletePassword = 'senha123';

        if (!Hash::check($request->password, Hash::make($deletePassword))) {
            return response()->json([
                'error' => 'Senha incorreta'
            ], 403);
        }

        try {
            $path = $request->input('path');
            if ($path) {
                // Se path foi enviado, deleta arquivo ou pasta individual
                if (\Storage::exists($path)) {
                    if (\Storage::delete($path)) {
                        return response()->json(['success' => true, 'message' => 'Arquivo apagado com sucesso']);
                    }
                } else if (\Storage::directories($path)) {
                    if (\Storage::deleteDirectory($path)) {
                        return response()->json(['success' => true, 'message' => 'Pasta apagada com sucesso']);
                    }
                }
                return response()->json([
                    'success' => false,
                    'error' => 'Arquivo ou pasta não encontrado(a)'
                ], 404);
            } else {
                // Deletar a pasta do projeto (comportamento antigo)
                $codigo = Codigo::findOrFail($id);
                if (Storage::exists($codigo->path_arquivo)) {
                    Storage::deleteDirectory($codigo->path_arquivo);
                }
                $codigo->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Projeto excluído com sucesso'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao excluir projeto/arquivo/pasta'
            ], 500);
        }
    }

    public function show(Request $request, Codigo $codigo)
    {
        $config = $this->streamingConfig->build($request);
        return view('codigos.show', ['codigo' => $codigo] + $config);
    }

    public function fileTree($id)
    {
        // Consulta ao banco para buscar o path_arquivo usando o id
        $codigo = Codigo::where('id', $id)->firstOrFail();
        $basePath = $codigo->path_arquivo;
        
        $tree = $this->buildFileTree($basePath);
        return response()->json(['tree' => $tree]);
    }

    // Função auxiliar para montar a árvore de arquivos
    private function buildFileTree($basePath)
    {
        $tree = [];
        if (!\Storage::exists($basePath)) {
            return $tree;
        }
        $allFiles = \Storage::allFiles($basePath);
        $allDirs = \Storage::allDirectories($basePath);
        foreach ($allDirs as $dir) {
            $relativePath = \Illuminate\Support\Str::after($dir, $basePath . '/');
            $this->addToTree($tree, $relativePath, true);
        }
        foreach ($allFiles as $file) {
            $relativePath = \Illuminate\Support\Str::after($file, $basePath . '/');
            $this->addToTree($tree, $relativePath, false);
        }
        return $tree;
    }

    // Função auxiliar para adicionar itens à árvore
    private function addToTree(&$tree, $path, $isDir)
    {
        $parts = explode('/', $path);
        $current = &$tree;
        $currentPath = '';
        foreach ($parts as $i => $part) {
            $currentPath .= ($currentPath ? '/' : '') . $part;
            if ($i === count($parts) - 1 && !$isDir) {
                $current[] = [
                    'name' => $part,
                    'path' => $currentPath,
                    'type' => 'file'
                ];
                continue;
            }
            $found = false;
            foreach ($current as &$item) {
                if ($item['name'] === $part && $item['type'] === 'directory') {
                    $current = &$item['children'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $newDir = [
                    'name' => $part,
                    'path' => $currentPath,
                    'type' => 'directory',
                    'children' => []
                ];
                $current[] = $newDir;
                $current = &$current[count($current) - 1]['children'];
            }
        }
    }


    // --- UPLOAD DE ARQUIVO ---
    public function uploadArquivo(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file',
            'currentPath' => 'required|string'
        ]);

        
        $file = $request->file('file');
        $currentPath = $request->input('currentPath');

        // Monta o caminho final (garante que não duplica barras)
        $fullPath = rtrim($currentPath, '/') . '/' . $file->getClientOriginalName();

        // Verifica se já existe
        if (\Storage::exists($fullPath)) {
            return response()->json(['success' => false, 'error' => 'Arquivo ou pasta já existe!'], 409);
        }

        // Cria diretórios se necessário
        \Storage::makeDirectory(dirname($fullPath));

        // Salva o arquivo
        \Storage::put($fullPath, file_get_contents($file));

        return response()->json(['success' => true, 'path' => $fullPath]);
    }

    // --- DELETAR ARQUIVO OU PASTA INDIVIDUAL ---
    public function destroyArquivo(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'path' => 'required|string'
        ]);

        $deletePassword = '123';
        if ($request->password !== $deletePassword) {
            return response()->json([
                'success' => false,
                'error' => 'Senha incorreta'
            ], 403);
        }

        try {
            $targetPath = $request->input('path');
            if (\Storage::exists($targetPath)) {
                // Tenta apagar como arquivo
                if (\Storage::delete($targetPath)) {
                    return response()->json(['success' => true, 'message' => 'Arquivo apagado com sucesso']);
                }
                // Se não conseguiu apagar como arquivo, tenta como pasta
                if (\Storage::deleteDirectory($targetPath)) {
                    return response()->json(['success' => true, 'message' => 'Pasta apagada com sucesso']);
                }
            }
            return response()->json([
                'success' => false,
                'error' => 'Arquivo ou pasta não encontrado(a)'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao apagar arquivo ou pasta'
            ], 500);
        }
    }

    // --- SALVAR ARQUIVO DE TEXTO ---
    public function salvarArquivo(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'conteudo' => 'required|string'
        ]);
        try {
            \Storage::put($request->input('path'), $request->input('conteudo'));
            return response()->json(['success' => true, 'message' => 'Arquivo salvo com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao salvar arquivo!'], 500);
        }
    }

    // --- GET ARQUIVO DE TEXTO ---
    public function getArquivo(Request $request)
    {
        $request->validate([
            'file' => 'required|string'
        ]);
        $path = $request->input('file');
        if (!\Storage::exists($path)) {
            return response('Arquivo não encontrado', 404);
        }
        return response(\Storage::get($path), 200, [
            'Content-Type' => 'text/plain; charset=utf-8'
        ]);
    }

    // --- CRIAR NOVO ARQUIVO ---
    public function criarArquivo(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'nome' => 'required|string'
        ]);
        $fullPath = rtrim($request->input('path'), '/') . '/' . $request->input('nome');
        if (\Storage::exists($fullPath)) {
            return response()->json(['success' => false, 'error' => 'Arquivo já existe!'], 400);
        }
        try {
            \Storage::put($fullPath, '');
            return response()->json(['success' => true, 'message' => 'Arquivo criado com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao criar arquivo!'], 500);
        }
    }

    // --- CRIAR NOVA PASTA ---
    public function criarPasta(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'nome' => 'required|string'
        ]);
        $fullPath = rtrim($request->input('path'), '/') . '/' . $request->input('nome');
        if (\Storage::exists($fullPath)) {
            return response()->json(['success' => false, 'error' => 'Pasta já existe!'], 400);
        }
        try {
            \Storage::makeDirectory($fullPath);
            return response()->json(['success' => true, 'message' => 'Pasta criada com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao criar pasta!'], 500);
        }
    }

    // --- DOWNLOAD DE ARQUIVO OU PASTA (COMPACTA SE FOR PASTA) ---
    public function downloadArquivoOuPasta(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);
        $path = $request->input('path');
        if (!\Storage::exists($path)) {
            return response('Arquivo ou pasta não encontrado', 404);
        }
        $disk = \Storage::disk();
        $realPath = $disk->path($path);
        if (is_file($realPath)) {
            // Download de arquivo
            return response()->download($realPath);
        } elseif (is_dir($realPath)) {
            // Compactar pasta e fazer download
            $rootFolder = basename($realPath);
            $zipName = $rootFolder . '.zip';
            $zipPath = storage_path('app/' . uniqid() . '_' . $zipName);
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                $zip->addEmptyDir($rootFolder);
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($realPath, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($files as $file) {
                    $filePath = $file->getRealPath();
                    $relativePath = $rootFolder . '/' . substr($filePath, strlen($realPath) + 1);
                    if ($file->isDir()) {
                        $zip->addEmptyDir($relativePath);
                    } else {
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                $zip->close();
                $response = response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
                return $response;
            } else {
                return response('Erro ao compactar pasta', 500);
            }
        } else {
            return response('Tipo de caminho inválido', 400);
        }
    }

    // --- EXCLUIR PROJETO (com senha) ---
    public function excluirProjeto(Request $request)
    {
        $request->validate([
            'senha' => 'required|string',
            'path' => 'required|string'
        ]);
        $senhaCorreta = '123'; // Troque para sua senha real
        if ($request->input('senha') !== $senhaCorreta) {
            return response()->json(['success' => false, 'error' => 'Senha incorreta'], 403);
        }
        try {
            $path = $request->input('path');
            $codigo = Codigo::where('path_arquivo', $path)->first();
            if (!$codigo) {
                return response()->json(['success' => false, 'error' => 'Projeto não encontrado'], 404);
            }
            // Apaga a pasta do projeto
            if (\Storage::exists($codigo->path_arquivo)) {
                \Storage::deleteDirectory($codigo->path_arquivo);
            }
            // Apaga o registro do banco
            $codigo->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Erro ao excluir projeto!'], 500);
        }
    }
} 