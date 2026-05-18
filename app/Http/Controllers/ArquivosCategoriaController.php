<?php

namespace App\Http\Controllers;

use App\Models\ArquivosCategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ArquivosCategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $categorias = ArquivosCategoria::when($search, function($query, $search) {
            return $query->where('categoria', 'like', "%{$search}%");
        })->orderBy('categoria')->paginate(8);
        return response()->json($categorias);
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
        $validated = $request->validate([
            'categoria' => 'required|string|max:255|unique:arquivos_categoria,categoria',
            'id_categoria' => 'nullable|exists:arquivos_categoria,id',
            'capa' => 'required_without:capa_stream_path|nullable|image',
            'capa_stream_path' => 'required_without:capa|nullable|string',
        ]);
        $data = [
            'categoria' => $validated['categoria'],
            'id_categoria' => $validated['id_categoria'] ?? null,
        ];
        if (!empty($validated['capa_stream_path'])) {
            $streamPath = ltrim($validated['capa_stream_path'], '/');
            if (!Storage::disk('public')->exists($streamPath)) {
                throw ValidationException::withMessages([
                    'capa_stream_path' => 'Arquivo da capa não foi encontrado no armazenamento público.',
                ]);
            }
            $data['capa'] = $streamPath;
        } elseif ($request->hasFile('capa')) {
            $data['capa'] = $request->file('capa')->store('capas_categorias', 'public');
        } else {
            throw ValidationException::withMessages([
                'capa' => 'Informe a imagem da capa.',
            ]);
        }
        $categoria = ArquivosCategoria::create($data);
        return response()->json($categoria, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ArquivosCategoria $arquivosCategoria)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ArquivosCategoria $arquivosCategoria)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ArquivosCategoria $arquivosCategoria)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $categoria = ArquivosCategoria::findOrFail($id);
        $categoria->delete();
        // Opcional: remover a pasta do storage
        // Storage::deleteDirectory('arquivos/' . $categoria->categoria);
        return response()->json(['success' => true]);
    }
}
