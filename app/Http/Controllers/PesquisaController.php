<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesquisa;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PesquisaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pesquisas = Pesquisa::orderBy('data', 'desc')->get();
        $estudos = \App\Models\Estudo::orderBy('data', 'desc')->get();
        return view('estudos.index', compact('estudos', 'pesquisas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'capa' => 'required_without:capa_stream_path|nullable|image',
            'capa_stream_path' => 'required_without:capa|nullable|string',
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string|max:255',
            'conteudo' => 'required',
            'data' => 'required|date',
            'tag' => 'nullable|string|max:255',
            'autor' => 'required|string|max:255',
        ]);

        if (!empty($data['capa_stream_path'])) {
            $streamPath = ltrim($data['capa_stream_path'], '/');
            if (!Storage::disk('public')->exists($streamPath)) {
                throw ValidationException::withMessages([
                    'capa_stream_path' => 'Arquivo da capa não foi encontrado no armazenamento público.',
                ]);
            }
            $data['capa'] = $streamPath;
        } elseif ($request->hasFile('capa')) {
            $data['capa'] = $request->file('capa')->store('capas', 'public');
        } else {
            throw ValidationException::withMessages([
                'capa' => 'Informe a imagem da capa.',
            ]);
        }

        unset($data['capa_stream_path']);

        Pesquisa::create($data);

        return redirect()->route('estudos.index')->with('success', 'Pesquisa criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $pesquisa = Pesquisa::findOrFail($id);
        return view('pesquisas.show', compact('pesquisa'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $pesquisa = \App\Models\Pesquisa::findOrFail($id);
        return view('pesquisas.edit', compact('pesquisa'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\Illuminate\Http\Request $request, $id)
    {
        $pesquisa = \App\Models\Pesquisa::findOrFail($id);
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'conteudo' => 'required',
        ]);
        $pesquisa->update($data);
        return redirect()->route('pesquisas.show', $pesquisa->id)->with('success', 'Pesquisa atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $pesquisa = Pesquisa::findOrFail($id);
        // \Storage::disk('public')->delete($pesquisa->capa); // Descomente se quiser deletar a imagem
        $pesquisa->delete();
        return redirect()->route('estudos.index')->with('success', 'Pesquisa deletada com sucesso!');
    }

    public function ajaxList(\Illuminate\Http\Request $request)
    {
        $pesquisas = \App\Models\Pesquisa::orderBy('data', 'desc')->paginate(6);
        return view('pesquisas._cards', compact('pesquisas'))->render();
    }

    public function ajaxListJson(\Illuminate\Http\Request $request)
    {
        $search = $request->input('search');
        
        $pesquisas = \App\Models\Pesquisa::when($search, function($query, $search) {
            return $query->where('titulo', 'like', "%{$search}%")
                        ->orWhere('descricao', 'like', "%{$search}%")
                        ->orWhere('autor', 'like', "%{$search}%")
                        ->orWhere('tag', 'like', "%{$search}%")
                        ->orWhere('conteudo', 'like', "%{$search}%");
        })->orderBy('data', 'desc')->paginate(6);
        
        return response()->json([
            'data' => $pesquisas->items(),
            'current_page' => $pesquisas->currentPage(),
            'last_page' => $pesquisas->lastPage(),
            'next_page_url' => $pesquisas->nextPageUrl(),
            'prev_page_url' => $pesquisas->previousPageUrl(),
            'total' => $pesquisas->total(),
        ]);
    }
}
