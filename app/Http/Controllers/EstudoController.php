<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Streaming\StreamingConfigService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EstudoController extends Controller
{
    public function __construct(private StreamingConfigService $streamingConfig)
    {
    }

    public function index(Request $request)
    {
        $estudos = \App\Models\Estudo::orderBy('data', 'desc')->paginate(6, ['*'], 'estudos_page');
        $pesquisas = \App\Models\Pesquisa::orderBy('data', 'desc')->paginate(6, ['*'], 'pesquisas_page');
        $config = $this->streamingConfig->build($request);
        return view('estudos.index', compact('estudos', 'pesquisas') + $config);
    }

    public function store(\Illuminate\Http\Request $request)
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

        \App\Models\Estudo::create($data);

        return redirect()->route('estudos.index')->with('success', 'Estudo criado com sucesso!');
    }

    public function show($id)
    {
        $estudo = \App\Models\Estudo::findOrFail($id);
        return view('estudos.show', compact('estudo'));
    }

    public function destroy($id)
    {
        $estudo = \App\Models\Estudo::findOrFail($id);
        // \Storage::disk('public')->delete($estudo->capa); // Descomente se quiser deletar a imagem
        $estudo->delete();
        return redirect()->route('estudos.index')->with('success', 'Estudo deletado com sucesso!');
    }

    public function edit($id)
    {
        $estudo = \App\Models\Estudo::findOrFail($id);
        return view('estudos.edit', compact('estudo'));
    }

    public function update(\Illuminate\Http\Request $request, $id)
    {
        $estudo = \App\Models\Estudo::findOrFail($id);
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'conteudo' => 'required',
        ]);
        $estudo->update($data);
        return redirect()->route('estudos.show', $estudo->id)->with('success', 'Estudo atualizado com sucesso!');
    }

    public function ajaxList(\Illuminate\Http\Request $request)
    {
        $estudos = \App\Models\Estudo::orderBy('data', 'desc')->paginate(6);
        return view('estudos._cards', compact('estudos'))->render();
    }

    public function ajaxListJson(\Illuminate\Http\Request $request)
    {
        $search = $request->input('search');
        
        $estudos = \App\Models\Estudo::when($search, function($query, $search) {
            return $query->where('titulo', 'like', "%{$search}%")
                        ->orWhere('descricao', 'like', "%{$search}%")
                        ->orWhere('autor', 'like', "%{$search}%")
                        ->orWhere('tag', 'like', "%{$search}%")
                        ->orWhere('conteudo', 'like', "%{$search}%");
        })->orderBy('data', 'desc')->paginate(6);
        
        return response()->json([
            'data' => $estudos->items(),
            'current_page' => $estudos->currentPage(),
            'last_page' => $estudos->lastPage(),
            'next_page_url' => $estudos->nextPageUrl(),
            'prev_page_url' => $estudos->previousPageUrl(),
            'total' => $estudos->total(),
        ]);
    }
} 