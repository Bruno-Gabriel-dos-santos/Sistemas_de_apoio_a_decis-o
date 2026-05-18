<?php

namespace App\Http\Controllers;

use App\Models\Codigo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

class UploadController extends Controller
{
    private $tempPath = 'temp/chunks';
    
    /**
     * Valida o hash e retorna o caminho base do projeto (corrigido para retornar apenas o hash)
     */
    private function getBasePath($codigoId)
    {
        $codigo = Codigo::findOrFail($codigoId);
        return [$codigo, $codigo->hash];
    }
    
    /**
     * Retorna a estrutura em árvore dos arquivos
     */
    public function getFileTree(Request $request, $hash)
    {
        $path = $request->input('path', $hash);
        return response()->json($this->buildTree($path, $hash));
    }
    
    /**
     * Lista arquivos de um diretório específico (com paginação)
     */
    public function listFiles(Request $request, $hash)
    {
        $path = $request->input('path', $hash);
        $files = [];
        foreach (Storage::disk('codigos')->directories($path) as $d) {
            $files[] = [
                'name' => basename($d),
                'path' => $d,
                'type' => 'directory',
                'size' => 0,
                'modified' => Storage::disk('codigos')->lastModified($d)
            ];
        }
        foreach (Storage::disk('codigos')->files($path) as $f) {
            $files[] = [
                'name' => basename($f),
                'path' => $f,
                'type' => 'file',
                'size' => Storage::disk('codigos')->size($f),
                'modified' => Storage::disk('codigos')->lastModified($f)
            ];
        }
        return response()->json(['files' => $files]);
    }
    
    /**
     * Recebe um chunk de arquivo
     */
    public function uploadChunk(Request $request)
    {
        try {
            $chunk = $request->file('file');
            $fileId = $request->input('fileId');
            $chunkIndex = (int)$request->input('chunkIndex');
            $chunkPath = "$this->tempPath/{$fileId}_{$chunkIndex}";
            if (!Storage::disk('codigos')->exists($this->tempPath)) {
                Storage::disk('codigos')->makeDirectory($this->tempPath);
            }
            Storage::disk('codigos')->put($chunkPath, file_get_contents($chunk));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Finaliza o upload combinando os chunks (corrigido para criar subpastas do relativePath)
     */
    public function completeUpload(Request $request)
    {
        try {
            $fileId = $request->input('fileId');
            $totalChunks = (int)$request->input('totalChunks');
            $fileName = $request->input('fileName');
            $relativePath = trim($request->input('relativePath', ''), '/');
            $currentPath = trim($request->input('currentPath', ''), '/');
            $targetPath = $currentPath;
            if ($relativePath) {
                $dirParts = explode('/', $relativePath);
                array_pop($dirParts);
                if (count($dirParts)) {
                    $targetPath .= ($targetPath ? '/' : '') . implode('/', $dirParts);
                }
            }
            if (!Storage::disk('codigos')->exists($targetPath)) {
                Storage::disk('codigos')->makeDirectory($targetPath);
            }
            $finalPath = ($targetPath ? $targetPath . '/' : '') . $fileName;
            $this->mergeChunks($fileId, $totalChunks, $finalPath);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Cria uma nova pasta (corrigido para sempre criar dentro do hash)
     */
    public function createFolder(Request $request)
    {
        try {
            $folderName = $request->input('folderName');
            $currentPath = trim($request->input('currentPath', ''), '/');
            $path = $currentPath ? "$currentPath/$folderName" : $folderName;
            if (!Storage::disk('codigos')->exists($path)) {
                Storage::disk('codigos')->makeDirectory($path);
            } else {
                return response()->json(['success' => false, 'message' => 'Pasta já existe'], 400);
            }
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Cria um novo arquivo vazio (corrigido para sempre retornar JSON)
     */
    public function createFile(Request $request, $codigoId)
    {
        try {
            [$codigo, $basePath] = $this->getBasePath($codigoId);
            $fileName = $request->input('fileName');
            $currentPath = trim($request->input('currentPath', ''), '/');
            if (!preg_match('/^[^\\\/]+$/', $fileName)) {
                return response()->json(['success' => false, 'message' => 'Nome de arquivo inválido'], 400);
            }
            $path = $basePath;
            if ($currentPath) {
                if (!Str::startsWith($currentPath, $basePath)) {
                    $currentPath = '';
                }
                $path .= '/' . ltrim(Str::after($currentPath, $basePath), '/');
            }
            $filePath = $path . '/' . $fileName;
            if (!Str::startsWith($filePath, $basePath)) {
                return response()->json(['success' => false, 'message' => 'Caminho inválido'], 400);
            }
            if (Storage::disk('codigos')->exists($filePath)) {
                return response()->json(['success' => false, 'message' => 'Arquivo já existe'], 400);
            }
            Storage::disk('codigos')->put($filePath, '');
            return response()->json(['success' => true, 'message' => 'Arquivo criado com sucesso']);
        } catch (\Throwable $e) {
            \Log::error('Erro ao criar arquivo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao criar arquivo: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Exclui um arquivo ou pasta
     */
    public function deleteItem(Request $request, $codigoId)
    {
        try {
            [$codigo, $basePath] = $this->getBasePath($codigoId);
            $path = $request->input('path');
            
            if (!Str::startsWith($path, $basePath)) {
                throw new \Exception('Caminho inválido');
            }
            
            if (!Storage::disk('codigos')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado'
                ], 404);
            }
            
            if (Storage::disk('codigos')->directoryExists($path)) {
                Storage::disk('codigos')->deleteDirectory($path);
            } else {
                Storage::disk('codigos')->delete($path);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Item excluído com sucesso'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir item: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Combina os chunks em um arquivo final
     */
    private function mergeChunks($fileId, $totalChunks, $finalPath)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'merge');
        $tempHandle = fopen($tempFile, 'wb');
        
        // Juntar todos os chunks em ordem
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "$this->tempPath/{$fileId}_{$i}";
            if (Storage::disk('codigos')->exists($chunkPath)) {
                fwrite($tempHandle, Storage::disk('codigos')->get($chunkPath));
                Storage::disk('codigos')->delete($chunkPath);
            }
        }
        
        fclose($tempHandle);
        
        // Mover arquivo final para o destino
        Storage::disk('codigos')->put($finalPath, file_get_contents($tempFile));
        unlink($tempFile);
    }
    
    /**
     * Constrói a estrutura em árvore dos arquivos (formato simples para JS puro)
     */
    private function buildFileTree($basePath)
    {
        $tree = [];
        if (!Storage::disk('codigos')->exists($basePath)) {
            return $tree;
        }
        $allFiles = Storage::disk('codigos')->allFiles($basePath);
        $allDirs = Storage::disk('codigos')->allDirectories($basePath);
        foreach ($allDirs as $dir) {
            $relativePath = Str::after($dir, $basePath . '/');
            $this->addToTree($tree, $relativePath, true);
        }
        foreach ($allFiles as $file) {
            $relativePath = Str::after($file, $basePath . '/');
            $this->addToTree($tree, $relativePath, false);
        }
        return $tree;
    }

    /**
     * Adiciona um item à estrutura em árvore
     */
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

    /**
     * Pesquisa arquivos no projeto
     */
    public function searchFiles(Request $request, $codigoId)
    {
        try {
            [$codigo, $basePath] = $this->getBasePath($codigoId);
            $term = $request->query('term');
            $allFiles = Storage::disk('codigos')->allFiles($basePath);
            $allDirs = Storage::disk('codigos')->allDirectories($basePath);
            
            $files = collect($allFiles)
                ->filter(fn($path) => Str::contains(strtolower($path), strtolower($term)))
                ->map(fn($path) => [
                    'path' => $path,
                    'name' => basename($path),
                    'type' => 'file',
                    'size' => Storage::disk('codigos')->size($path),
                    'modified' => Storage::disk('codigos')->lastModified($path)
                ]);
                
            $directories = collect($allDirs)
                ->filter(fn($path) => Str::contains(strtolower($path), strtolower($term)))
                ->map(fn($path) => [
                    'path' => $path,
                    'name' => basename($path),
                    'type' => 'directory',
                    'size' => 0,
                    'modified' => Storage::disk('codigos')->lastModified($path)
                ]);
                
            return response()->json([
                'files' => $directories->concat($files)->values()
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Visualiza um arquivo
     */
    public function viewFile(Request $request, $codigoId)
    {
        try {
            [$codigo, $basePath] = $this->getBasePath($codigoId);
            $path = $request->query('path');
            
            if (!Str::startsWith($path, $basePath)) {
                throw new \Exception('Caminho inválido');
            }
            
            if (!Storage::disk('codigos')->exists($path)) {
                return response()->json(['error' => 'Arquivo não encontrado'], 404);
            }
            
            $content = Storage::disk('codigos')->get($path);
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            
            return response()->json([
                'content' => $content,
                'type' => $extension
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Faz download de um arquivo
     */
    public function downloadFile(Request $request, $codigoId)
    {
        try {
            [$codigo, $basePath] = $this->getBasePath($codigoId);
            $path = $request->query('path');
            
            if (!Str::startsWith($path, $basePath)) {
                throw new \Exception('Caminho inválido');
            }
            
            if (!Storage::disk('codigos')->exists($path)) {
                return response()->json(['error' => 'Arquivo não encontrado'], 404);
            }
            
            return Storage::disk('codigos')->download($path, basename($path));
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Faz download do projeto completo
     */
    public function downloadProject($codigoId)
    {
        try {
            [$codigo, $basePath] = $this->getBasePath($codigoId);
            $zipName = tempnam(sys_get_temp_dir(), 'project_');
            $zip = new ZipArchive();
            $zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            
            // Adicionar todos os arquivos ao ZIP
            $files = Storage::disk('codigos')->allFiles($basePath);
            foreach ($files as $file) {
                $relativePath = Str::after($file, $basePath . '/');
                $zip->addFromString($relativePath, Storage::disk('codigos')->get($file));
            }
            
            $zip->close();
            
            // Enviar arquivo ZIP
            return response()->download($zipName, $codigo->nome . '.zip')->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Compacta arquivos e pastas selecionados
     */
    public function compressSelection(Request $request, $codigoId)
    {
        try {
            [$codigo, $basePath] = $this->getBasePath($codigoId);
            $items = $request->input('items', []);
            if (empty($items)) {
                return response()->json(['error' => 'Nenhum item selecionado'], 400);
            }
            $zipName = tempnam(sys_get_temp_dir(), 'selection_');
            $zip = new ZipArchive();
            $zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            foreach ($items as $item) {
                $itemPath = $item;
                if (!Str::startsWith($itemPath, $basePath)) {
                    continue;
                }
                if (Storage::disk('codigos')->exists($itemPath)) {
                    if (Storage::disk('codigos')->directoryExists($itemPath)) {
                        $this->addDirectoryToZip($zip, $itemPath, $basePath);
                    } else {
                        $relativePath = Str::after($itemPath, $basePath . '/');
                        $zip->addFromString($relativePath, Storage::disk('codigos')->get($itemPath));
                    }
                }
            }
            $zip->close();
            return response()->download($zipName, 'selecionados.zip')->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Adiciona diretório recursivamente ao ZIP
     */
    private function addDirectoryToZip(ZipArchive $zip, $dir, $basePath)
    {
        $files = Storage::disk('codigos')->allFiles($dir);
        foreach ($files as $file) {
            $relativePath = Str::after($file, $basePath . '/');
            $zip->addFromString($relativePath, Storage::disk('codigos')->get($file));
        }
    }

    /**
     * Renomeia arquivo ou pasta
     */
    public function renameItem(Request $request, $codigoId)
    {
        try {
            [$codigo, $basePath] = $this->getBasePath($codigoId);
            $path = $request->input('path');
            $newName = $request->input('newName');
            $currentPath = trim($request->input('currentPath', ''), '/');
            if (!Str::startsWith($path, $basePath)) {
                throw new \Exception('Caminho inválido');
            }
            if (!preg_match('/^[^\\\/]+$/', $newName)) {
                return response()->json(['success' => false, 'message' => 'Nome inválido'], 400);
            }
            if (!Storage::disk('codigos')->exists($path)) {
                return response()->json(['success' => false, 'message' => 'Item não encontrado'], 404);
            }
            $parentDir = dirname($path);
            $newPath = $parentDir . '/' . $newName;
            if (Storage::disk('codigos')->exists($newPath)) {
                return response()->json(['success' => false, 'message' => 'Já existe um item com esse nome'], 400);
            }
            if (Storage::disk('codigos')->directoryExists($path)) {
                Storage::disk('codigos')->move($path, $newPath);
            } else {
                Storage::disk('codigos')->move($path, $newPath);
            }
            return response()->json(['success' => true, 'message' => 'Renomeado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao renomear: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Move arquivo ou pasta para outro diretório
     */
    public function moveItem(Request $request, $codigoId)
    {
        try {
            [$codigo, $basePath] = $this->getBasePath($codigoId);
            $sourcePath = $request->input('sourcePath');
            $targetDir = $request->input('targetDir');
            if (!Str::startsWith($sourcePath, $basePath) || !Str::startsWith($targetDir, $basePath)) {
                throw new \Exception('Caminho inválido');
            }
            if (!Storage::disk('codigos')->exists($sourcePath) || !Storage::disk('codigos')->directoryExists($targetDir)) {
                return response()->json(['success' => false, 'message' => 'Origem ou destino não encontrado'], 404);
            }
            $itemName = basename($sourcePath);
            $newPath = rtrim($targetDir, '/') . '/' . $itemName;
            if (Storage::disk('codigos')->exists($newPath)) {
                return response()->json(['success' => false, 'message' => 'Já existe um item com esse nome no destino'], 400);
            }
            Storage::disk('codigos')->move($sourcePath, $newPath);
            return response()->json(['success' => true, 'message' => 'Movido com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao mover: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload simples de arquivo para o sistema
     */
    public function uploadArquivo(Request $request, $sistema)
    {
        $request->validate([
            'arquivo' => 'required|file|max:10240', // 10MB
        ]);

        $arquivo = $request->file('arquivo');
        $path = $arquivo->store("sistemas/{$sistema}/arquivos");

        // Aqui você pode salvar o caminho no banco, se desejar

        return back()->with('success', 'Arquivo enviado com sucesso!');
    }

    /**
     * Upload de arquivo para uma página do sistema
     */
    public function uploadArquivoPagina(Request $request, $sistema_id, $pagina_id)
    {
        $request->validate([
            'arquivo' => 'required|file|max:10240', // 10MB
        ]);

        $arquivo = $request->file('arquivo');
        $path = $arquivo->store("paginaSistemas/{$pagina_id}/arquivos");

        // Aqui você pode salvar o caminho no banco, se desejar

        return back()->with('success', 'Arquivo enviado com sucesso!');
    }
} 