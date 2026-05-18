<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArquivosCategoria extends Model
{
    use HasFactory;

    protected $table = 'arquivos_categoria';

    protected $fillable = [
        'categoria',
        'id_categoria',
        'capa',
    ];
}
