<?php

namespace App\Services\Streaming;

use App\Services\UploadTokenService;
use Illuminate\Http\Request;

class StreamingConfigService
{
    public function __construct(private UploadTokenService $tokenService)
    {
    }

    public function build(Request $request): array
    {
        $uploadToken = $this->tokenService->issueForUser($request->user(), [
            'ip' => $request->ip(),
            'max_uses' => config('upload.token_max_uses', 8),
            'ttl' => config('upload.token_ttl_seconds', 600),
        ]);

        return [
            'websocket_urls' => $this->buildWebsocketUrls($request),
            'upload_token' => $uploadToken->token,
            'token_expires_at' => optional($uploadToken->expires_at)->toIso8601String(),
        ];
    }

    protected function buildWebsocketUrls(Request $request): array
    {
        $host = env('WORKERMAN_WS_HOST');
        if (empty($host)) {
            $host = $request->getHost() ?: '127.0.0.1';
        }

        $scheme = env('WORKERMAN_WS_SCHEME');
        if (empty($scheme)) {
            $scheme = $request->isSecure() ? 'wss' : 'ws';
        }

        $portsEnv = env('WORKERMAN_WS_PORTS', '20001,20010,20020,20040');
        $ports = array_filter(array_map('trim', explode(',', $portsEnv)));
        if (empty($ports)) {
            $ports = ['20001'];
        }

        return array_map(
            fn ($port) => sprintf('%s://%s:%s/upload', $scheme, $host, $port),
            $ports
        );
    }
}

