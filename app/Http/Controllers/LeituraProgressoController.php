<?php

namespace App\Http\Controllers;

use App\Models\LeituraAtual;
use App\Models\RegistroLeitura;
use Illuminate\Http\Request;

class LeituraProgressoController extends Controller
{
    public function index()
    {
        $leiturasAtuais = LeituraAtual::orderBy('created_at', 'desc')->get();
        $metas = RegistroLeitura::where('tipo', 'metas')->first();
        $historico = RegistroLeitura::where('tipo', 'historico')->first();

        return view('livros.progresso.index', compact('leiturasAtuais', 'metas', 'historico'));
    }

    public function storeLeituraAtual(Request $request)
    {
        $validated = $request->validate([
            'titulo_livro' => 'required|string|max:255',
            'pagina_atual' => 'required|integer|min:0',
            'total_paginas' => 'nullable|integer|min:0',
            'meta_conclusao' => 'nullable|date',
            'notas_leitura' => 'nullable|string'
        ]);

        $leitura = LeituraAtual::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Leitura adicionada com sucesso',
            'data' => $leitura
        ]);
    }

    public function updateLeituraAtual(Request $request, LeituraAtual $leitura)
    {
        $validated = $request->validate([
            'pagina_atual' => 'required|integer|min:0',
            'meta_conclusao' => 'nullable|date',
            'notas_leitura' => 'nullable|string'
        ]);

        $leitura->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Leitura atualizada com sucesso',
            'data' => $leitura
        ]);
    }

    public function deleteLeituraAtual(LeituraAtual $leitura)
    {
        $leitura->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Leitura removida com sucesso'
        ]);
    }

    public function updateRegistro(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:metas,historico',
            'conteudo' => 'required|string'
        ]);

        $registro = RegistroLeitura::updateOrCreate(
            ['tipo' => $validated['tipo']],
            ['conteudo' => $validated['conteudo']]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Registro atualizado com sucesso',
            'data' => $registro
        ]);
    }
} 