<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeituraAtual extends Model
{
    protected $table = 'leituras_atuais';
    
    protected $fillable = [
        'titulo_livro',
        'pagina_atual',
        'total_paginas',
        'meta_conclusao',
        'notas_leitura'
    ];

    protected $casts = [
        'meta_conclusao' => 'date'
    ];
} 