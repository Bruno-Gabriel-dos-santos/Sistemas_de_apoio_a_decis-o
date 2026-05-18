<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    protected $table = 'backup';
    public $timestamps = true;
    protected $fillable = [
        'descricao',
        'data_backup',
        'data',
    ];
} 