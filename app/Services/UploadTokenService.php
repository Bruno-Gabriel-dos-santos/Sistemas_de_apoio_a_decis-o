<?php

namespace App\Services;

use App\Models\UploadToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UploadTokenService
{
    /**
     * Emite um token para o usuário autenticado.
     */
    public function issueForUser(User $user, array $options = []): UploadToken
    {
        $maxUses = $options['max_uses'] ?? config('upload.token_max_uses', 8);
        $ttlSeconds = $options['ttl'] ?? config('upload.token_ttl_seconds', 600);
        $expiresAt = Carbon::now()->addSeconds($ttlSeconds);

        return UploadToken::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'max_uses' => $maxUses,
            'expires_at' => $expiresAt,
            'ip_address' => $options['ip'] ?? null,
            'metadata' => $options['metadata'] ?? [],
        ]);
    }

    /**
     * Valida e consome (incrementa uso) de um token.
     */
    public function validateAndConsume(string $token): UploadToken
    {
        return DB::transaction(function () use ($token) {
            $record = UploadToken::where('token', $token)->lockForUpdate()->first();

            if (!$record) {
                throw new \RuntimeException('Token inválido');
            }

            if ($record->isExpired()) {
                throw new \RuntimeException('Token expirado');
            }

            if ($record->remainingUses() <= 0) {
                throw new \RuntimeException('Token já foi utilizado no limite permitido');
            }

            $record->increment('used_count');

            return $record->fresh();
        });
    }
}

