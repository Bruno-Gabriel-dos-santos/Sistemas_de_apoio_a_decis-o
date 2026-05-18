<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'livros/validate',
        'livros/upload/*',
        'livros/complete/*'
    ];

    /**
     * Determine if the token has expired.
     *
     * @param  string  $token
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $sessionToken = $request->session()->token();
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (!$token && $header = $request->header('X-XSRF-TOKEN')) {
            $token = $this->encrypter->decrypt($header, static::serialized());
        }

        if (!is_string($sessionToken) || !is_string($token)) {
            return false;
        }

        // Verifica se o token expirou
        $lifetime = config('session.csrf_token_lifetime', 10) * 60; // converte minutos para segundos
        $tokenTime = $request->session()->get('_token_time');

        if ($tokenTime && time() - $tokenTime > $lifetime) {
            // Token expirou, gera um novo
            $request->session()->regenerateToken();
            return false;
        }

        // Atualiza o timestamp do token
        $request->session()->put('_token_time', time());

        return hash_equals($sessionToken, $token);
    }
}
