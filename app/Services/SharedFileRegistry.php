<?php

namespace App\Services;

/**
 * Registra metadados de uploads compartilhados em disco para que
 * múltiplos workers possam recuperar sessões existentes.
 */
class SharedFileRegistry
{
    private string $registryDir;

    public function __construct()
    {
        $this->registryDir = storage_path('app/streaming/state');

        if (!is_dir($this->registryDir)) {
            mkdir($this->registryDir, 0755, true);
        }
    }

    /**
     * Salva ou sobrescreve metadados de um arquivo.
     */
    public function put(string $fileId, array $payload): void
    {
        $this->writeFile($this->getFilePath($fileId), $payload);
    }

    /**
     * Obtém metadados armazenados.
     */
    public function get(string $fileId): ?array
    {
        $path = $this->getFilePath($fileId);

        if (!file_exists($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            return null;
        }

        $data = json_decode($contents, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Remove metadados do arquivo.
     */
    public function forget(string $fileId): void
    {
        $path = $this->getFilePath($fileId);
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    private function getFilePath(string $fileId): string
    {
        return $this->registryDir . '/' . md5($fileId) . '.json';
    }

    private function writeFile(string $path, array $payload): void
    {
        file_put_contents($path, json_encode($payload), LOCK_EX);
    }
}

