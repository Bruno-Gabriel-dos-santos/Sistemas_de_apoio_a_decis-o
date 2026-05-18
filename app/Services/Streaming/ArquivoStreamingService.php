<?php

namespace App\Services\Streaming;

use App\Models\Arquivo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Services\Streaming\Contracts\StreamingContextHandlerInterface;

class ArquivoStreamingService implements StreamingContextHandlerInterface
{
    public function resolveStoragePath(int $userId, string $relativePath, array $payload): string
    {
        $categoria = (int) ($payload['categoria'] ?? 0);
        if ($categoria <= 0) {
            throw new \RuntimeException('Categoria inválida para upload de arquivos.');
        }

        $relativePath = trim($relativePath, '/');
        if ($relativePath === '') {
            throw new \RuntimeException('Caminho relativo inválido.');
        }

        return 'arquivos/' . $categoria . '/' . $relativePath;
    }

    public function onUploadStarted(array $payload): void
    {
        // Nenhuma ação necessária no início do upload para este contexto
    }

    /**
     * Finaliza um upload vindo do Workerman atualizando banco e estrutura em disco.
     */
    public function finalize(array $metadata, string $absoluteTempPath, int $fileSize): array
    {
        $contextPayload = $metadata['context_payload'] ?? [];
        $categoria = (int) ($contextPayload['categoria'] ?? 0);
        $relativePath = trim($metadata['relative_path'] ?? '', '/');
        $descricao = $contextPayload['descricao'] ?? null;
        $storageRelativePath = $metadata['storage_relative_path'] ?? null;

        if ($categoria <= 0 || $relativePath === '') {
            throw new \RuntimeException('Dados insuficientes para registrar o arquivo.');
        }

        $segments = explode('/', $relativePath);
        $fileName = array_pop($segments);

        $this->ensureDirectoryRecords($categoria, $segments);

        $alreadyExists = Arquivo::where('categoria', $categoria)
            ->where('path', $relativePath)
            ->where('tipo', 'arquivo')
            ->exists();

        if ($alreadyExists) {
            if ($absoluteTempPath && file_exists($absoluteTempPath)) {
                @unlink($absoluteTempPath);
            }
            if ($storageRelativePath) {
                $this->deleteTempStorage($storageRelativePath);
            }
            throw new \RuntimeException('Arquivo já existe no destino.');
        }

        $destinationRelative = 'arquivos/' . $categoria . '/' . $relativePath;
        $this->moveToFinalPath($absoluteTempPath, $storageRelativePath, $destinationRelative);

        if (!$fileSize && Storage::exists($destinationRelative)) {
            $fileSize = Storage::size($destinationRelative);
        }

        Arquivo::create([
            'categoria' => $categoria,
            'path' => $relativePath,
            'nome' => $fileName,
            'descricao' => $descricao,
            'data' => Carbon::now(),
            'tamanho_arquivo' => $fileSize,
            'tipo' => 'arquivo',
        ]);

        return [
            'final_relative_path' => $destinationRelative,
            'categoria' => $categoria,
            'relative_path' => $relativePath,
        ];
    }

    protected function ensureDirectoryRecords(int $categoria, array $segments): void
    {
        if (empty($segments)) {
            return;
        }

        $pathAccumulated = '';
        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }
            $pathAccumulated = $pathAccumulated ? $pathAccumulated . '/' . $segment : $segment;

            $folder = $this->withDirectoryLock($categoria, $pathAccumulated, function () use ($categoria, $pathAccumulated, $segment) {
                return Arquivo::firstOrCreate(
                    [
                        'categoria' => $categoria,
                        'path' => $pathAccumulated,
                        'tipo' => 'pasta',
                    ],
                    [
                        'nome' => $segment,
                        'descricao' => null,
                        'data' => Carbon::now(),
                        'tamanho_arquivo' => null,
                    ]
                );
            });

            if ($folder) {
                Arquivo::where('categoria', $categoria)
                    ->where('path', $pathAccumulated)
                    ->where('tipo', 'pasta')
                    ->where('id', '!=', $folder->id)
                    ->delete();
            }
        }
    }

    protected function withDirectoryLock(int $categoria, string $path, callable $callback)
    {
        $lockDir = storage_path('app/streaming/state/directories');
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }

        $lockFile = $lockDir . '/' . md5($categoria . '|' . $path) . '.lock';
        $handle = @fopen($lockFile, 'c+');

        if ($handle === false) {
            return $callback();
        }

        try {
            if (flock($handle, LOCK_EX)) {
                $result = $callback();
                fflush($handle);
                flock($handle, LOCK_UN);
                return $result;
            } else {
                return $callback();
            }
        } finally {
            fclose($handle);
        }
    }

    protected function moveToFinalPath(?string $absoluteTempPath, ?string $storageRelativePath, string $destinationRelative): void
    {
        $sourcePath = $absoluteTempPath;

        if (!$sourcePath && $storageRelativePath) {
            $sourcePath = storage_path('app/streaming/upload/' . ltrim($storageRelativePath, '/'));
        }

        if (!$sourcePath || !file_exists($sourcePath)) {
            throw new \RuntimeException('Arquivo temporário não encontrado para concluir o upload.');
        }

        $directory = trim(dirname($destinationRelative), '.');
        if ($directory && !Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        Storage::put($destinationRelative, file_get_contents($sourcePath));
        @unlink($sourcePath);
    }

    protected function deleteTempStorage(string $storageRelativePath): void
    {
        $tempPath = storage_path('app/streaming/upload/' . ltrim($storageRelativePath, '/'));
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }
}

