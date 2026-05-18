<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IncreaseUploadLimit
{
    public function handle(Request $request, Closure $next)
    {
        $contentLength = $request->server('CONTENT_LENGTH');
        if ($contentLength > 10 * 1024 * 1024) { // 10MB em bytes
            throw new \Illuminate\Http\Exceptions\PostTooLargeException;
        }

        return $next($request);
    }
} 