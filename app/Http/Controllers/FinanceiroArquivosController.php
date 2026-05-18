<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class FinanceiroArquivosController extends Controller
{
    protected array $allowedSections = ['projecoes', 'capital', 'metas', 'bens'];

    public function tree(string $section)
    {
        $section = $this->ensureSection($section);
        $base = $this->basePath($section);
        Storage::makeDirectory($base);

        return response()->json(['tree' => $this->buildTree($base)]);
    }

    public function getFile(Request $request, string $section)
    {
        $section = $this->ensureSection($section);
        $relative = $this->sanitizePath($request->query('path', ''));
        if ($relative === '') {
            return response('Caminho inválido.', 422);
        }

        $fullPath = $this->basePath($section) . '/' . $relative;
        if (!Storage::exists($fullPath)) {
            return response('Arquivo não encontrado.', 404);
        }

        $mime = Storage::mimeType($fullPath) ?? 'text/plain';
        $content = Storage::get($fullPath);

        if (!str_starts_with($mime, 'text/') && $mime !== 'application/json') {
            return response('Pré-visualização disponível apenas para arquivos de texto.', 415);
        }

        return response($content, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function saveFile(Request $request, string $section)
    {
        $section = $this->ensureSection($section);
        $data = $request->validate([
            'path' => 'required|string',
            'conteudo' => 'nullable|string',
        ]);

        $fullPath = $this->basePath($section) . '/' . $this->sanitizePath($data['path']);
        if (!Storage::exists($fullPath)) {
            return response()->json(['success' => false, 'error' => 'Arquivo não encontrado.'], 404);
        }

        Storage::put($fullPath, $data['conteudo'] ?? '');
        return response()->json(['success' => true]);
    }

    public function createFile(Request $request, string $section)
    {
        $section = $this->ensureSection($section);
        $data = $request->validate([
            'path' => 'nullable|string',
            'nome' => 'required|string',
        ]);

        $relativeDir = trim($this->sanitizePath($data['path'] ?? ''), '/');
        $filename = $this->sanitizeFilename($data['nome']);
        $fullPath = rtrim($this->basePath($section) . ($relativeDir ? '/' . $relativeDir : ''), '/') . '/' . $filename;

        if (Storage::exists($fullPath)) {
            return response()->json(['success' => false, 'error' => 'Arquivo já existe.'], 409);
        }

        Storage::put($fullPath, '');
        return response()->json(['success' => true]);
    }

    public function createFolder(Request $request, string $section)
    {
        $section = $this->ensureSection($section);
        $data = $request->validate([
            'path' => 'nullable|string',
            'nome' => 'required|string',
        ]);

        $relativeDir = trim($this->sanitizePath($data['path'] ?? ''), '/');
        $folder = $this->sanitizeFilename($data['nome']);
        $fullPath = rtrim($this->basePath($section) . ($relativeDir ? '/' . $relativeDir : ''), '/') . '/' . $folder;

        if (Storage::exists($fullPath)) {
            return response()->json(['success' => false, 'error' => 'Pasta já existe.'], 409);
        }

        Storage::makeDirectory($fullPath);
        return response()->json(['success' => true]);
    }

    public function delete(Request $request, string $section)
    {
        $section = $this->ensureSection($section);
        $data = $request->validate([
            'path' => 'required|string',
        ]);

        $relative = $this->sanitizePath($data['path']);
        if ($relative === '') {
            return response()->json(['success' => false, 'error' => 'Caminho inválido.'], 422);
        }

        $fullPath = $this->basePath($section) . '/' . $relative;
        if (Storage::delete($fullPath) || Storage::deleteDirectory($fullPath)) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'error' => 'Arquivo/pasta não encontrado.'], 404);
    }

    public function download(Request $request, string $section)
    {
        $section = $this->ensureSection($section);
        $data = $request->validate([
            'path' => 'required|string',
        ]);

        $relative = $this->sanitizePath($data['path']);
        if ($relative === '') {
            abort(400, 'Caminho inválido.');
        }

        $fullRelative = $this->basePath($section) . '/' . $relative;
        return $this->prepareDownloadResponse($fullRelative);
    }

    protected function buildTree(string $base): array
    {
        $tree = [];
        foreach (Storage::directories($base) as $dir) {
            $tree[] = [
                'name' => basename($dir),
                'type' => 'directory',
                'children' => $this->buildTree($dir),
            ];
        }

        foreach (Storage::files($base) as $file) {
            $tree[] = [
                'name' => basename($file),
                'type' => 'file',
            ];
        }

        return $tree;
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

        $zipPath = $tmpDir . '/' . uniqid('financeiro_zip_', true) . '.zip';
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

    protected function ensureSection(string $section): string
    {
        $normalized = strtolower($section);
        if (!in_array($normalized, $this->allowedSections, true)) {
            abort(404);
        }

        return $normalized;
    }

    protected function basePath(string $section): string
    {
        return 'financeiro/' . $section;
    }

    protected function sanitizePath(string $path): string
    {
        $path = str_replace("\0", '', $path);
        $path = str_replace('\\', '/', $path);
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    protected function sanitizeFilename(string $name): string
    {
        $name = trim($name);
        $name = str_replace(['/', '\\'], '_', $name);

        return preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', $name) ?: 'arquivo.txt';
    }
}

