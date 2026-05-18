<?php

namespace App\Services\Streaming;

use App\Models\Livro;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Services\Streaming\Contracts\StreamingContextHandlerInterface;

class LivroStreamingService implements StreamingContextHandlerInterface
{
    public function resolveStoragePath(int $userId, string $relativePath, array $payload): string
    {
        $hash = $this->getHash($payload);
        $fileName = $this->sanitizeFilename($relativePath ?: ($payload['original_name'] ?? 'upload.pdf'));

        return 'livros/' . $hash . '/stream/' . uniqid('', true) . '_' . $fileName;
    }

    public function onUploadStarted(array $payload): void
    {
        $livro = $this->findLivro($payload);
        if ($livro->status === 'validado') {
            $livro->update(['status' => 'uploading']);
        }
    }

    public function finalize(array $metadata, string $tempAbsolutePath, int $fileSize): array
    {
        $payload = $metadata['context_payload'] ?? [];
        $hash = $this->getHash($payload);
        $livro = $this->findLivro($payload);

        if (!file_exists($tempAbsolutePath)) {
            throw new \RuntimeException('Arquivo temporário não encontrado para upload de livro.');
        }

        $finalRelativePath = 'livros/pdfs/' . $hash . '.pdf';
        $publicDisk = Storage::disk('public');
        $finalAbsolutePath = $publicDisk->path($finalRelativePath);

        File::ensureDirectoryExists(dirname($finalAbsolutePath));

        if (!File::move($tempAbsolutePath, $finalAbsolutePath)) {
            throw new \RuntimeException('Falha ao mover o livro para o destino final.');
        }

        if (!$fileSize && $publicDisk->exists($finalRelativePath)) {
            $fileSize = $publicDisk->size($finalRelativePath);
        }

        $livro->update([
            'arquivo_path' => $finalRelativePath,
            'status' => 'completo',
        ]);

        return [
            'final_relative_path' => $finalRelativePath,
            'livro_id' => $livro->id,
            'hash' => $hash,
            'file_size' => $fileSize,
        ];
    }

    protected function findLivro(array $payload): Livro
    {
        $livroId = $payload['livro_id'] ?? null;
        $hash = $payload['hash'] ?? null;

        $query = Livro::query();
        if ($livroId) {
            $query->where('id', $livroId);
        }

        if ($hash) {
            $query->where('hash', $hash);
        }

        $livro = $query->first();
        if (!$livro) {
            throw new \RuntimeException('Livro não encontrado para finalizar upload.');
        }

        return $livro;
    }

    protected function getHash(array $payload): string
    {
        $hash = $payload['hash'] ?? null;
        if (!$hash) {
            throw new \RuntimeException('Hash do livro não informado.');
        }
        return $hash;
    }

    protected function sanitizeFilename(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        $name = basename($name);
        return preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name) ?: 'upload.pdf';
    }
}

