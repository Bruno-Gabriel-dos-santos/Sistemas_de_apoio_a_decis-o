<?php

namespace App\Services;

/**
 * Proxy simples usado para fornecer estatísticas e interface compatível
 * com AsyncWriter quando a escrita real é feita pelo gerenciador central.
 */
class SharedWriterProxy
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function getStats(): array
    {
        $fileSize = file_exists($this->filePath) ? filesize($this->filePath) : 0;

        return [
            'file_path' => $this->filePath,
            'file_size' => $fileSize,
            'file_size_gb' => round($fileSize / 1024 / 1024 / 1024, 2),
            'total_written' => $fileSize,
            'is_writing' => false,
        ];
    }

    public function finalize(): void
    {
        // No-op: o gerenciador central é responsável pelo flush final.
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}

