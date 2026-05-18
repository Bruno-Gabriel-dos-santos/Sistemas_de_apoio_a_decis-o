<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelatorioSituacional extends Model
{
    use HasFactory;

    protected $fillable = [
        'capa',
        'titulo',
        'descricao',
        'conteudo',
        'data',
        'tag',
        'autor',
        'user_id',
    ];

    protected $table = 'relatorios_situacionais';
}
