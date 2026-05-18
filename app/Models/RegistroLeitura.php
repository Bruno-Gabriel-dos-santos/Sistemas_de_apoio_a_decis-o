<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroLeitura extends Model
{
    protected $table = 'registros_leitura';
    
    protected $fillable = [
        'tipo',
        'conteudo'
    ];
} 