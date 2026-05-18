<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'descricao',
        'linguagem',
        'status',
        'codigo',
        'output',
        'user_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getLanguageIcon()
    {
        return match($this->linguagem) {
            'php' => 'devicon-php-plain',
            'python' => 'devicon-python-plain',
            'cpp' => 'devicon-cplusplus-plain',
            default => 'fas fa-code'
        };
    }

    public function getStatusColor()
    {
        return match($this->status) {
            'active' => 'green',
            'inactive' => 'gray',
            'error' => 'red',
            default => 'gray'
        };
    }
}
