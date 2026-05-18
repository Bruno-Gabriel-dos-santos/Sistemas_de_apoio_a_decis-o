<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RelatorioSituacional;
use App\Services\Streaming\StreamingConfigService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RelatoriosSituacionaisController extends Controller
{
    public function __construct(private StreamingConfigService $streamingConfig)
    {
    }

    public function index(Request $request)
    {
        if ($request->ajax() || $request->has('ajax')) {
            $search = $request->input('search');

            $posts = RelatorioSituacional::when($search, function ($query, $search) {
                    return $query->where('titulo', 'like', "%{$search}%")
                        ->orWhere('descricao', 'like', "%{$search}%")
                        ->orWhere('autor', 'like', "%{$search}%")
                        ->orWhere('tag', 'like', "%{$search}%")
                        ->orWhere('conteudo', 'like', "%{$search}%");
                })
                ->orderBy('data', 'desc')
                ->paginate(9);

            $grouped = collect($posts->items())
                ->groupBy(function ($item) {
                    $tag = trim($item->tag ?? '');
                    return $tag !== '' ? $tag : 'Sem tag';
                })
                ->map(function ($items, $tag) {
                    return [
                        'tag' => $tag,
                        'total' => $items->count(),
                        'relatorios' => $items->values(),
                    ];
                })
                ->values();

            return response()->json([
                'data' => $posts->items(),
                'grouped' => $grouped,
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'next_page_url' => $posts->nextPageUrl(),
                'prev_page_url' => $posts->previousPageUrl(),
                'total' => $posts->total(),
            ]);
        }
        
        $posts = \App\Models\RelatorioSituacional::orderBy('data', 'desc')->paginate(6);
        $config = $this->streamingConfig->build($request);
        return view('relatorios_situacionais.index', compact('posts') + $config);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string|max:255',
            'autor' => 'required|string|max:255',
            'data' => 'required|date',
            'tag' => 'nullable|string|max:255',
            'conteudo' => 'required|string',
            'capa' => 'required_without:capa_stream_path|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'capa_stream_path' => 'required_without:capa|nullable|string',
        ]);

        if (!empty($validated['capa_stream_path'])) {
            $streamPath = ltrim($validated['capa_stream_path'], '/');
            if (!Storage::disk('public')->exists($streamPath)) {
                throw ValidationException::withMessages([
                    'capa_stream_path' => 'Arquivo da capa não foi encontrado no armazenamento público.',
                ]);
            }
            $validated['capa'] = $streamPath;
        } elseif ($request->hasFile('capa')) {
            $validated['capa'] = $request->file('capa')->store('capas', 'public');
        } else {
            throw ValidationException::withMessages([
                'capa' => 'Informe a imagem da capa.',
            ]);
        }

        unset($validated['capa_stream_path']);

        $validated['user_id'] = auth()->id();
        RelatorioSituacional::create($validated);
        return redirect()->route('relatorios-situacionais.index')->with('success', 'Relatório criado com sucesso!');
    }

    public function show($id)
    {
        $post = \App\Models\RelatorioSituacional::findOrFail($id);
        return view('relatorios_situacionais.show', compact('post'));
    }

    public function edit($id)
    {
        $post = \App\Models\RelatorioSituacional::findOrFail($id);
        return view('relatorios_situacionais.edit', compact('post'));
    }

    public function update(Request $request, $id)
    {
        $post = \App\Models\RelatorioSituacional::findOrFail($id);
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'conteudo' => 'required',
        ]);
        $post->update($data);
        return redirect()->route('relatorios-situacionais.show', $post->id)->with('success', 'Relatório atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $post = \App\Models\RelatorioSituacional::findOrFail($id);
        $post->delete();
        return redirect()->route('relatorios-situacionais.index')->with('success', 'Relatório deletado com sucesso!');
    }
} 