<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Livro extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'autor',
        'categoria',
        'genero',
        'materia',
        'descricao',
        'data_publicacao',
        'hash',
        'arquivo_path',
        'original_filename',
        'status',
        'total_paginas'
    ];

    protected $casts = [
        'data_publicacao' => 'date',
        'total_paginas' => 'integer'
    ];

    // Relacionamento com os progressos de leitura
    public function progressos()
    {
        return $this->hasMany(LeituraProgresso::class);
    }

    // Obtém o progresso do usuário atual
    public function progressoAtual()
    {
        return $this->hasOne(LeituraProgresso::class)
            ->where('user_id', Auth::id());
    }

    // Verifica se o usuário atual está lendo este livro
    public function getEstaLendoAttribute()
    {
        return $this->progressoAtual()
            ->where('status', 'lendo')
            ->exists();
    }

    // Verifica se o usuário atual já leu este livro
    public function getJaLidoAttribute()
    {
        return $this->progressoAtual()
            ->where('status', 'lido')
            ->exists();
    }

    // Obtém a porcentagem de leitura do usuário atual
    public function getProgressoPercentualAttribute()
    {
        $progresso = $this->progressoAtual()->first();
        if ($progresso && $progresso->total_paginas > 0) {
            return round(($progresso->pagina_atual / $progresso->total_paginas) * 100, 2);
        }
        return 0;
    }

    // Obtém estatísticas de leitura deste livro
    public function getEstatisticasAttribute()
    {
        $progressos = $this->progressos;
        return [
            'total_leitores' => $progressos->count(),
            'leitores_ativos' => $progressos->where('status', 'lendo')->count(),
            'leitores_concluidos' => $progressos->where('status', 'lido')->count(),
            'media_progresso' => $progressos->where('status', 'lendo')->avg('pagina_atual'),
            'tempo_medio_leitura' => $progressos->where('status', 'lido')
                ->avg(function ($progresso) {
                    return $progresso->data_inicio->diffInDays($progresso->data_conclusao);
                })
        ];
    }
} 