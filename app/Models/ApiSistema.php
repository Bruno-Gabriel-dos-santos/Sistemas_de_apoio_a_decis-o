<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiSistema extends Model
{
    use HasFactory;

    protected $table = 'api_sistemas';

    protected $fillable = [
        'sistema_id', 'titulo', 'descricao', 'data', 'conteudo', 'imagens', 'videos', 'musicas', 'graficos', 'assets', 'autor_id', 'tags', 'ordem', 'tipo', 'publicado', 'slug', 'destaque'
    ];

    protected $casts = [
        'imagens' => 'array',
        'videos' => 'array',
        'musicas' => 'array',
        'graficos' => 'array',
        'assets' => 'array',
        'publicado' => 'boolean',
        'destaque' => 'boolean',
        'data' => 'datetime',
    ];

    public function sistema()
    {
        return $this->belongsTo(Sistema::class, 'sistema_id');
    }
} 