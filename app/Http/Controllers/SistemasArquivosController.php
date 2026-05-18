<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Streaming\StreamingConfigService;

class SistemasArquivosController extends Controller
{
    public function __construct(private StreamingConfigService $streamingConfig)
    {
    }

    // Listar arquivos e pastas
    public function index(Request $request, $id, $path = null)
    {
        $base = 'sistemas/' . $id . ($path ? '/' . $path : '');
        $folders = Storage::directories($base);
        $files = Storage::files($base);
        $currentPath = $path ?? '';

        // Se for requisição AJAX ou aceitar JSON, retorna a árvore de arquivos
        if ($request->ajax() || $request->wantsJson()) {
            $tree = $this->buildTree($base);
            return response()->json(['tree' => $tree]);
        }

        $config = $this->streamingConfig->build($request);
        return view('sistemas.arquivos', compact('id', 'folders', 'files', 'currentPath') + $config);
    }

    // Função auxiliar para montar a árvore de arquivos/pastas
    private function buildTree($base)
    {
        $tree = [];
        foreach (Storage::directories($base) as $dir) {
            $tree[] = [
                'name' => basename($dir),
                'type' => 'directory',
                'children' => $this->buildTree($dir)
            ];
        }
        foreach (Storage::files($base) as $file) {
            $tree[] = [
                'name' => basename($file),
                'type' => 'file'
            ];
        }
        return $tree;
    }

    // Upload de arquivo
    public function upload(Request $request, $id, $path = null)
    {
        $base = 'sistemas/' . $id . ($path ? '/' . $path : '');
        // Se for AJAX, aceitar múltiplos arquivos
        if ($request->ajax() || $request->wantsJson()) {
            $request->validate(['files' => 'required|array', 'files.*' => 'file']);
            $uploaded = [];
            foreach ($request->file('files') as $file) {
                $uploaded[] = $file->store($base);
            }
            return response()->json(['success' => true, 'uploaded' => $uploaded]);
        }
        // Tradicional (formulário)
        $request->validate(['arquivo' => 'required|file']);
        $request->file('arquivo')->store($base);
        return back()->with('success', 'Arquivo enviado com sucesso!');
    }

    // Download de arquivo
    public function download($id, $path)
    {
        $relativePath = 'sistemas/' . $id . '/' . ltrim($path, '/');
        return $this->prepareDownloadResponse($relativePath);
    }

    // Excluir arquivo ou pasta
    public function destroy($id, $path)
    {
        $target = 'sistemas/' . $id . '/' . $path;
        $success = false;
        if (Storage::exists($target)) {
            if (Storage::delete($target) || Storage::deleteDirectory($target)) {
                $success = true;
            }
        }
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Arquivo/pasta excluído com sucesso!' : 'Arquivo/pasta não encontrado!'
            ]);
        }
        if ($success) {
            return back()->with('success', 'Arquivo/pasta excluído com sucesso!');
        }
        return back()->with('error', 'Arquivo/pasta não encontrado!');
    }

    // Criar nova pasta
    public function createFolder(Request $request, $id, $path = null)
    {
        $request->validate(['folder_name' => 'required|string']);
        $base = 'sistemas/' . $id . ($path ? '/' . $path : '');
        Storage::makeDirectory($base . '/' . $request->folder_name);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pasta criada com sucesso!']);
        }
        return back()->with('success', 'Pasta criada com sucesso!');
    }

    // API: Retorna a árvore de arquivos/pastas para AJAX
    public function apiTree($id, Request $request)
    {
        $base = 'sistemas/' . $id;
        $tree = $this->buildTree($base);
        return response()->json(['tree' => $tree]);
    }

    // API: Upload de arquivos
    public function apiUpload(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file',
            'currentPath' => 'required|string'
        ]);

        $file = $request->file('file');
        $currentPath = $request->input('currentPath');
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

    // API: Excluir arquivo ou pasta
    public function apiDelete(Request $request, $id)
    {
        $path = $request->input('path');
        $target = 'sistemas/' . $id . '/' . ltrim($path, '/');
        $success = false;
        if (Storage::exists($target)) {
            if (Storage::delete($target) || Storage::deleteDirectory($target)) {
                $success = true;
            }
        }
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Arquivo/pasta excluído com sucesso!' : 'Arquivo/pasta não encontrado!'
        ]);
    }

    // API: Salvar conteúdo de arquivo
    public function apiSalvarArquivo(Request $request)
    {
        $id = $request->input('id');
        $path = $request->input('path');
        $conteudo = $request->input('conteudo');
        $file = 'sistemas/' . $id . '/' . ltrim($path, '/');
        try {
            Storage::put($file, $conteudo);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // API: Criar arquivo vazio
    public function apiCriarArquivo(Request $request)
    {
        $id = $request->input('id');
        $path = $request->input('path');
        $nome = $request->input('nome');
        $file = 'sistemas/' . $id . ($path ? '/' . ltrim($path, '/') : '') . '/' . $nome;
        if (Storage::exists($file)) {
            return response()->json(['success' => false, 'error' => 'Arquivo já existe!']);
        }
        Storage::put($file, '');
        return response()->json(['success' => true]);
    }

    // API: Criar nova pasta
    public function apiCriarPasta(Request $request)
    {
        $id = $request->input('id');
        $path = $request->input('path');
        $nome = $request->input('nome');
        $base = 'sistemas/' . $id . ($path ? '/' . ltrim($path, '/') : '');
        $dir = $base . '/' . $nome;
        if (Storage::exists($dir)) {
            return response()->json(['success' => false, 'error' => 'Pasta já existe!']);
        }
        Storage::makeDirectory($dir);
        return response()->json(['success' => true]);
    }

    // API: Download de arquivo ou pasta (arquivo único)
    public function apiDownload(Request $request)
    {
        $id = $request->input('id');
        $path = $request->input('path', '');
        $relativePath = 'sistemas/' . $id . '/' . ltrim($path, '/');

        return $this->prepareDownloadResponse($relativePath);
    }

    // API: Obter conteúdo de arquivo
    public function apiGetArquivo(Request $request)
    {
        $id = $request->input('id', $request->query('id'));
        $file = $request->input('file', $request->query('file'));
        $fullPath = 'sistemas/' . $id . '/' . ltrim($file, '/');
        if (!Storage::exists($fullPath)) {
            return response('Arquivo não encontrado.', 404);
        }
        return response(Storage::get($fullPath), 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
    protected function prepareDownloadResponse(string $relativePath)
    {
        $relativePath = trim($relativePath, '/');
        $disk = Storage::disk();
        $absolutePath = $disk->path($relativePath);

        if (!file_exists($absolutePath)) {
            abort(404);
        }

        if (is_file($absolutePath)) {
            return response()->download($absolutePath, basename($absolutePath));
        }

        if (is_dir($absolutePath)) {
            $zipPath = $this->createZipFromDirectory($absolutePath);
            $zipName = basename($absolutePath) ?: 'arquivos';

            return response()->download($zipPath, $zipName . '.zip')->deleteFileAfterSend(true);
        }

        abort(400, 'Tipo de caminho inválido.');
    }

    protected function createZipFromDirectory(string $absolutePath): string
    {
        $tmpDir = storage_path('app/tmp');
        File::ensureDirectoryExists($tmpDir);

        $zipPath = $tmpDir . '/' . uniqid('sistemas_zip_', true) . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Não foi possível preparar o download da pasta.');
        }

        $rootFolder = basename($absolutePath) ?: 'arquivos';
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absolutePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $fileInfo) {
            $relativeName = $rootFolder . '/' . ltrim(substr($fileInfo->getRealPath(), strlen($absolutePath)), '/\\');
            if ($fileInfo->isDir()) {
                $zip->addEmptyDir($relativeName);
            } else {
                $zip->addFile($fileInfo->getRealPath(), $relativeName);
            }
        }

        $zip->close();

        return $zipPath;
    }
}