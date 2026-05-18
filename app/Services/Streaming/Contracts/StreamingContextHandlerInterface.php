<?php

namespace App\Services\Streaming\Contracts;

interface StreamingContextHandlerInterface
{
    /**
     * Define o caminho relativo dentro de storage/app/streaming/upload.
     */
    public function resolveStoragePath(int $userId, string $relativePath, array $payload): string;

    /**
     * Executado imediatamente após o início do upload (opcional).
     */
    public function onUploadStarted(array $payload): void;

    /**
     * Finaliza o upload movendo o arquivo temporário para o destino final.
     *
     * Deve retornar um array com metadados relevantes (ex.: caminho final).
     */
    public function finalize(array $metadata, string $tempAbsolutePath, int $fileSize): array;
}

