<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Codigo extends Model
{
    protected $table = 'codigos';

    protected $fillable = [
        'nome_projeto',
        'tipo_linguagem',
        'categoria',
        'link_github',
        'link_gitlab',
        'hash_identidade',
        'descricao',
        'data_publicacao',
        'data_inicio',
        'path_arquivo'
    ];

    protected $casts = [
        'data_publicacao' => 'date',
        'data_inicio' => 'date'
    ];
} 