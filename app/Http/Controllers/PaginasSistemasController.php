<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiSistema;
use App\Models\Sistema;
use App\Services\Streaming\StreamingConfigService;

class PaginasSistemasController extends Controller
{
    public function __construct(private StreamingConfigService $streamingConfig)
    {
    }

    public function index($sistema_id)
    {
        $sistema = Sistema::findOrFail($sistema_id);
        $paginas = ApiSistema::where('sistema_id', $sistema_id)->get();
        return view('paginas_sistemas.index', compact('sistema', 'paginas'));
    }

    public function create($sistema_id)
    {
        $sistema = Sistema::findOrFail($sistema_id);
        return view('paginas_sistemas.create', compact('sistema'));
    }

    public function store(Request $request, $sistema_id)
    {
        $request->validate([
            'titulo' => 'required',
            'conteudo' => 'required',
        ]);

        $dados = $request->all();
        $dados['sistema_id'] = $sistema_id;
        $dados['descricao'] = $request->input('descricao', '');
        $dados['data'] = $request->input('data', now());
        $dados['imagens'] = $request->input('imagens', json_encode([]));
        $dados['videos'] = $request->input('videos', json_encode([]));
        $dados['musicas'] = $request->input('musicas', json_encode([]));
        $dados['graficos'] = $request->input('graficos', json_encode([]));
        $dados['assets'] = $request->input('assets', json_encode([]));
        $dados['autor_id'] = $request->input('autor_id', null);
        $dados['tags'] = $request->input('tags', null);
        $dados['ordem'] = $request->input('ordem', null);
        $dados['tipo'] = $request->input('tipo', 'post');
        $dados['publicado'] = $request->input('publicado', true);
        $dados['slug'] = $request->input('slug', null);
        $dados['destaque'] = $request->input('destaque', false);

        $pagina = ApiSistema::create($dados);

        // Criação das pastas para uploads futuros
        $basePath = storage_path('app/paginaSistemas/' . $pagina->id);
        $subPastas = ['video', 'audio', 'texto', 'imagem', 'arquivos', 'graficos', 'asset'];
        foreach ($subPastas as $pasta) {
            if (!is_dir($basePath . '/' . $pasta)) {
                mkdir($basePath . '/' . $pasta, 0777, true);
            }
        }

        $sistema = Sistema::findOrFail($sistema_id);
        return redirect()->route('sistemas.show', $sistema->nome)->with('success', 'Página criada com sucesso!');
    }

    public function show($sistema_id, $id)
    {
        $sistema = Sistema::findOrFail($sistema_id);
        $pagina = ApiSistema::findOrFail($id);
        return view('paginas_sistemas.show', compact('sistema', 'pagina'));
    }

    public function edit($sistema_id, $id)
    {
        $sistema = Sistema::findOrFail($sistema_id);
        $pagina = ApiSistema::findOrFail($id);
        return view('paginas_sistemas.edit', compact('sistema', 'pagina'));
    }

    public function update(Request $request, $sistema_id, $id)
    {
        $request->validate([
            'titulo' => 'required',
            'conteudo' => 'required',
        ]);
        $pagina = ApiSistema::findOrFail($id);
        $pagina->update($request->all());
        $sistema = Sistema::findOrFail($sistema_id);
        return redirect()->route('sistemas.show', $sistema->nome)->with('success', 'Página atualizada com sucesso!');
    }

    public function destroy($sistema_id, $id)
    {
        // Exclui a pasta de arquivos da página
        \Illuminate\Support\Facades\Storage::deleteDirectory("paginaSistemas/{$id}");
        $pagina = ApiSistema::findOrFail($id);
        $pagina->delete();
        $sistema = Sistema::findOrFail($sistema_id);
        return redirect()->route('sistemas.show', $sistema->nome)->with('success', 'Página excluída com sucesso!');
    }

    public function upload(Request $request, $sistema_id, $pagina_id)
    {
        $sistema = Sistema::findOrFail($sistema_id);
        $pagina = ApiSistema::findOrFail($pagina_id);
        $config = $this->streamingConfig->build($request);
        return view('paginas_sistemas.upload', compact('sistema', 'pagina') + $config);
    }

    // Verifica se arquivos já existem na subpasta da página
    public function checkArquivosExistentes(Request $request)
    {
        $request->validate([
            'pagina_id' => 'required|integer',
            'categoria' => 'required|string',
            'nomes' => 'required|array',
        ]);
        $paginaId = $request->input('pagina_id');
        $categoria = $request->input('categoria');
        $nomes = $request->input('nomes');
        $existem = [];
        foreach ($nomes as $nome) {
            $path = "paginaSistemas/{$paginaId}/{$categoria}/{$nome}";
            if (\Storage::exists($path)) {
                $existem[] = $nome;
            }
        }
        return response()->json(['existem' => $existem]);
    }

    // Upload múltiplo de arquivos para subpasta da página
    public function uploadArquivos(Request $request)
    {
        $request->validate([
            'pagina_id' => 'required|integer',
            'categoria' => 'required|string',
            'arquivos' => 'required|array',
            
        ]);
        $paginaId = $request->input('pagina_id');
        $categoria = $request->input('categoria');
        $arquivos = $request->file('arquivos');
        $salvos = [];
        $existentes = [];
        foreach ($arquivos as $arquivo) {
            $nome = $arquivo->getClientOriginalName();
            $path = "paginaSistemas/{$paginaId}/{$categoria}/{$nome}";
            if (\Storage::exists($path)) {
                $existentes[] = $nome;
                continue;
            }
            $arquivo->storeAs("paginaSistemas/{$paginaId}/{$categoria}", $nome);
            $salvos[] = $nome;
        }
        return response()->json([
            'salvos' => $salvos,
            'existentes' => $existentes,
        ]);
    }
} 