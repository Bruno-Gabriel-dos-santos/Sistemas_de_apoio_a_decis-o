<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArquivosCategoria;
use App\Models\Arquivo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use ZipArchive;
use App\Services\UploadTokenService;
use App\Services\Streaming\StreamingConfigService;

class ArquivoController extends Controller
{
    public function __construct(
        private StreamingConfigService $streamingConfig,
        private UploadTokenService $tokenService
    ) {
    }

    public function index(Request $request)
    {
        $config = $this->streamingConfig->build($request);
        return view('arquivos.index', $config);
    }

    public function show(Request $request, $id_categoria)
    {
        $categoria = \App\Models\ArquivosCategoria::findOrFail($id_categoria);

        $config = $this->streamingConfig->build($request);

        return view('arquivos.show', [
            'categoria' => $categoria,
        ] + $config);
    }

    public function listar(Request $request)
    {
        $request->validate([
            'categoria' => 'required|integer|exists:arquivos_categoria,id',
            'filtro' => 'nullable|string',
        ]);
        $query = Arquivo::where('categoria', $request->categoria);
        if ($request->filled('filtro')) {
            $query->where('nome', 'like', '%' . $request->filtro . '%');
        }
        $arquivos = $query->orderByDesc('data')->get();
        return response()->json($arquivos);
    }

    public function visualizar(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:arquivos,id']);
        $arquivo = Arquivo::findOrFail($request->id);
        $path = 'arquivos/' . $arquivo->categoria . '/' . $arquivo->nome;
        $url = Storage::url($path);
        $tipo = Storage::mimeType($path);
        return response()->json([
            'url' => $url,
            'tipo' => $tipo,
            'nome' => $arquivo->nome,
        ]);
    }

    public function excluir(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:arquivos,id']);
        $arquivo = Arquivo::findOrFail($request->id);
        $path = 'arquivos/' . $arquivo->categoria . '/' . $arquivo->path;
        if (Storage::exists($path)) {
            Storage::delete($path);
        }
        $arquivo->delete();
        return response()->json(['success' => true]);
    }

    public function excluirPasta(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:arquivos,id']);
        $pasta = \App\Models\Arquivo::findOrFail($request->id);
        if ($pasta->tipo !== 'pasta') {
            return response()->json(['error' => 'Não é uma pasta'], 400);
        }
        $categoria = $pasta->categoria;
        $path = $pasta->path;
        // Buscar todos os arquivos e subpastas dentro da pasta
        $arquivos = \App\Models\Arquivo::where('categoria', $categoria)
            ->where(function($q) use ($path) {
                $q->where('path', $path)
                  ->orWhere('path', 'like', $path . '/%');
            })->get();
        foreach ($arquivos as $arq) {
            if ($arq->tipo === 'arquivo') {
                $destino = 'arquivos/' . $categoria . '/' . $arq->path;
                if (Storage::exists($destino)) {
                    Storage::delete($destino);
                }
            }
            $arq->delete();
        }
        // Exclui a própria pasta
        $pasta->delete();
        // Exclui o diretório físico
        Storage::deleteDirectory('arquivos/' . $categoria . '/' . $path);
        return response()->json(['success' => true]);
    }

    public function download(Request $request, $id)
    {
        $arquivo = Arquivo::findOrFail($id);
        $absolutePath = storage_path('app/public/arquivos/' . $arquivo->categoria . '/' . ltrim($arquivo->path, '/'));

        if (!file_exists($absolutePath)) {
            abort(404);
        }

        $forceZip = $request->boolean('zip');

        if ($arquivo->tipo === 'arquivo' && !$forceZip) {
            $downloadName = $arquivo->nome ?: basename($absolutePath);
            return response()->download($absolutePath, $downloadName);
        }

        $rootName = $arquivo->nome ?: basename($absolutePath);
        $zipPath = $this->createZipFromPath($absolutePath, $rootName);

        return response()->download($zipPath, $rootName . '.zip')->deleteFileAfterSend(true);
    }

    public function criarPasta(Request $request)
    {
        $request->validate([
            'categoria' => 'required|integer|exists:arquivos_categoria,id',
            'path' => 'required|string',
            'nome' => 'required|string',
        ]);
        $categoria = $request->categoria;
        $path = $request->path;
        $nome = $request->nome;
        $existe = \App\Models\Arquivo::where('categoria', $categoria)
            ->where('path', $path)
            ->where('tipo', 'pasta')
            ->exists();
        if (!$existe) {
            \App\Models\Arquivo::create([
                'categoria' => $categoria,
                'path' => $path,
                'nome' => $nome,
                'descricao' => null,
                'data' => now(),
                'tamanho_arquivo' => null,
                'tipo' => 'pasta',
            ]);
            // Cria o diretório físico no storage
            Storage::makeDirectory('arquivos/' . $categoria . '/' . $path);
        }
        return response()->json(['success' => true]);
    }

    protected function createZipFromPath(string $absolutePath, string $rootName): string
    {
        $tmpDir = storage_path('app/tmp');
        File::ensureDirectoryExists($tmpDir);

        $zipPath = $tmpDir . '/' . uniqid('arquivos_zip_', true) . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Não foi possível preparar o arquivo para download.');
        }

        if (is_file($absolutePath)) {
            $zip->addFile($absolutePath, $rootName);
        } else {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absolutePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            $hasEntries = false;

            foreach ($iterator as $fileInfo) {
                $hasEntries = true;
                $subPath = substr($fileInfo->getRealPath(), strlen($absolutePath));
                $subPath = ltrim($subPath, "\\/");
                $relativeName = $rootName . '/' . $subPath;
                if ($fileInfo->isDir()) {
                    $zip->addEmptyDir($relativeName);
                } else {
                    $zip->addFile($fileInfo->getRealPath(), $relativeName);
                }
            }

            if (!$hasEntries) {
                $zip->addEmptyDir($rootName);
            }
        }

        $zip->close();

        return $zipPath;
    }

    public function visualizador($id)
    {
        $arquivo = \App\Models\Arquivo::findOrFail($id);
        $url = \Storage::url('arquivos/' . $arquivo->categoria . '/' . $arquivo->path);
        $tipo = \Storage::mimeType('arquivos/' . $arquivo->categoria . '/' . $arquivo->path);
        return view('arquivos.visualizador', compact('arquivo', 'url', 'tipo'));
    }

    public function preview($id)
    {
        $arquivo = \App\Models\Arquivo::findOrFail($id);
        $path = storage_path('app/public/arquivos/' . $arquivo->categoria . '/' . $arquivo->path);
        if (!file_exists($path)) abort(404);
        $mime = mime_content_type($path);
        // Para arquivos de texto, forçar visualização inline
        $disposition = in_array($mime, ['text/plain', 'application/pdf', 'text/html', 'text/csv']) ? 'inline' : 'inline';
        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition . '; filename="' . $arquivo->nome . '"'
        ]);
    }

    public function streamingToken(Request $request)
    {
        $token = $this->tokenService->issueForUser($request->user(), [
            'ip' => $request->ip(),
            'max_uses' => config('upload.token_max_uses', 8),
            'ttl' => config('upload.token_ttl_seconds', 600),
        ]);

        return response()->json([
            'token' => $token->token,
            'expires_at' => optional($token->expires_at)->toIso8601String(),
        ]);
    }
} 