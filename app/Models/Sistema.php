<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sistema extends Model
{
    use HasFactory;

    protected $table = 'sistemas';

    protected $fillable = [
        'nome',
        'titulo',
        'descricao',
        'comandos',
        'documentacao',
        'rota',
        'data_inicio',
        'imagem_capa',
        'ativo',
        'ordem',
        'autor_id',
        'tags',
        'categoria',
        'db_name',
        'db_username',
        'db_password',
        'db_host',
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
    ];

    public function apiSistemas()
    {
        return $this->hasMany(ApiSistema::class, 'sistema_id');
    }
} 