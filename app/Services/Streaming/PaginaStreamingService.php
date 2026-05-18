<?php

namespace App\Services\Streaming;

use Illuminate\Support\Facades\Storage;
use App\Services\Streaming\Contracts\StreamingContextHandlerInterface;

class PaginaStreamingService implements StreamingContextHandlerInterface
{
    public function resolveStoragePath(int $userId, string $relativePath, array $payload): string
    {
        $paginaId = (int) ($payload['pagina_id'] ?? 0);
        $categoria = trim($payload['categoria'] ?? '', '/');

        if ($paginaId <= 0 || $categoria === '') {
            throw new \RuntimeException('Dados inválidos para upload em páginas de sistemas.');
        }

        $safeRelative = $this->sanitizePath($relativePath);

        if ($safeRelative === '') {
            throw new \RuntimeException('Caminho relativo inválido.');
        }

        return 'paginaSistemas/' . $paginaId . '/' . $categoria . '/' . $safeRelative;
    }

    public function onUploadStarted(array $payload): void
    {
        // Nenhuma ação específica necessária
    }

    public function finalize(array $metadata, string $tempAbsolutePath, int $fileSize): array
    {
        $destinationRelative = $metadata['storage_relative_path'] ?? null;

        if (!$destinationRelative) {
            throw new \RuntimeException('Destino não informado para upload de página de sistema.');
        }

        $this->moveToFinalPath($tempAbsolutePath, $destinationRelative);

        if (!$fileSize && Storage::exists($destinationRelative)) {
            $fileSize = Storage::size($destinationRelative);
        }

        return [
            'final_relative_path' => $destinationRelative,
            'file_size' => $fileSize,
        ];
    }

    protected function moveToFinalPath(string $tempAbsolutePath, string $destinationRelative): void
    {
        if (!file_exists($tempAbsolutePath)) {
            throw new \RuntimeException('Arquivo temporário não encontrado para upload de página de sistema.');
        }

        $directory = trim(dirname($destinationRelative), '.');

        if ($directory && !Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        Storage::put($destinationRelative, file_get_contents($tempAbsolutePath));
        @unlink($tempAbsolutePath);
    }

    protected function sanitizePath(string $path): string
    {
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }
}

