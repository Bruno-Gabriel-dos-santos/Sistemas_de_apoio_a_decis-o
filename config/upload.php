<?php

return [
    'token_ttl_seconds' => env('UPLOAD_TOKEN_TTL', 600),
    'token_max_uses' => env('UPLOAD_TOKEN_MAX_CONNECTIONS', 8),
    'max_active_files' => env('UPLOAD_MAX_ACTIVE_FILES', 4),
    'max_bytes_per_user' => env('UPLOAD_MAX_BYTES_PER_USER', 0),
];

