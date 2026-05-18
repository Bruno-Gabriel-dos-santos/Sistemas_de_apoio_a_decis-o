<?php

namespace App\Services\Streaming;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Streaming\Contracts\StreamingContextHandlerInterface;

class PublicDiskStreamingService implements StreamingContextHandlerInterface
{
    public function resolveStoragePath(int $userId, string $relativePath, array $payload): string
    {
        $baseName = $this->sanitizeFilename($relativePath ?: ($payload['original_name'] ?? 'upload.bin'));
        return 'public_stream/' . uniqid('', true) . '_' . $baseName;
    }

    public function onUploadStarted(array $payload): void
    {
        // Sem ações específicas
    }

    public function finalize(array $metadata, string $tempAbsolutePath, int $fileSize): array
    {
        $payload = $metadata['context_payload'] ?? [];
        $targetDirectory = trim($payload['target_directory'] ?? 'uploads', '/');
        $finalName = $payload['final_name'] ?? $this->buildFilename($payload, $metadata);

        $publicDisk = Storage::disk('public');
        $finalRelativePath = $targetDirectory ? $targetDirectory . '/' . $finalName : $finalName;
        $finalAbsolutePath = $publicDisk->path($finalRelativePath);

        File::ensureDirectoryExists(dirname($finalAbsolutePath));

        if (!File::exists($tempAbsolutePath)) {
            throw new \RuntimeException('Arquivo temporário não encontrado para upload público.');
        }

        File::move($tempAbsolutePath, $finalAbsolutePath);

        if (!$fileSize && $publicDisk->exists($finalRelativePath)) {
            $fileSize = $publicDisk->size($finalRelativePath);
        }

        return [
            'final_relative_path' => $finalRelativePath,
            'file_size' => $fileSize,
        ];
    }

    protected function buildFilename(array $payload, array $metadata): string
    {
        $original = $payload['original_name'] ?? $metadata['relative_path'] ?? 'upload.bin';
        $sanitized = $this->sanitizeFilename($original);

        if (!empty($payload['preserve_name'])) {
            return $sanitized;
        }

        $extension = pathinfo($sanitized, PATHINFO_EXTENSION);
        $prefix = Str::uuid()->toString();

        return $extension ? $prefix . '.' . $extension : $prefix;
    }

    protected function sanitizeFilename(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        $name = basename($name);
        return preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name) ?: 'upload.bin';
    }
}

