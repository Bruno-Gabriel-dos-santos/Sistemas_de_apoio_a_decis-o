<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'max_uses',
        'used_count',
        'expires_at',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof Carbon
            ? $this->expires_at->isPast()
            : true;
    }

    public function remainingUses(): int
    {
        return max(0, $this->max_uses - $this->used_count);
    }
}

