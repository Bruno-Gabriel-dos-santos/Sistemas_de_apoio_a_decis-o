<?php

namespace App\Http\Controllers;

use App\Models\Livro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Usuario;
use App\Models\Dados;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Services\Streaming\StreamingConfigService;

class LivroController extends Controller
{
    public function __construct(private StreamingConfigService $streamingConfig)
    {
    }

    /**
     * Lista todos os livros
     */
    public function index(Request $request)
    {
        $livros = Livro::all();
        $config = $this->streamingConfig->build($request);
        return view('livros.index', compact('livros') + $config);
    }

    /**
     * Valida dados (sem arquivo) e cria registro inicial.
     */
    public function validateData(Request $request)
    {
        DB::beginTransaction();
        try {
            // Verificar se recebeu o token CSRF
            $csrfToken = $request->header('X-CSRF-TOKEN');
            Log::info('Token CSRF recebido em validateData: ' . ($csrfToken ? 'Sim' : 'Não'));
            
            // Logar TODOS os dados recebidos detalhadamente
            Log::info('Campos recebidos em validateData:');
            Log::info('titulo: ' . $request->input('titulo', 'AUSENTE'));
            Log::info('autor: ' . $request->input('autor', 'AUSENTE'));
            Log::info('categoria: ' . $request->input('categoria', 'AUSENTE'));
            Log::info('genero: ' . $request->input('genero', 'AUSENTE'));
            Log::info('materia: ' . $request->input('materia', 'AUSENTE'));
            Log::info('descricao: ' . $request->input('descricao', 'AUSENTE'));
            Log::info('data_publicacao: ' . $request->input('data_publicacao', 'AUSENTE'));
            Log::info('original_filename: ' . $request->input('original_filename', 'AUSENTE'));
            
            // Validar os dados
            $validated = $request->validate([
                'titulo' => 'required|string|max:255',
                'autor' => 'required|string|max:255',
                'categoria' => 'required|string|max:255',
                'genero' => 'required|string|max:255',
                'materia' => 'nullable|string|max:255',
                'descricao' => 'nullable|string|max:1000', 
                'data_publicacao' => 'required|date',
                'original_filename' => 'required|string|max:255'
            ]);
            
            // Log dos dados validados
            Log::info('Dados validados com sucesso:', $validated);

            // Gerar hash único
            $hash = Str::random(40);
            
            // Criar o registro do livro
            $livro = Livro::create([
                'titulo' => $validated['titulo'],
                'autor' => $validated['autor'],
                'categoria' => $validated['categoria'],
                'genero' => $validated['genero'],
                'materia' => $validated['materia'] ?? null,
                'descricao' => $validated['descricao'] ?? null,
                'data_publicacao' => $validated['data_publicacao'],
                'original_filename' => $validated['original_filename'],
                'hash' => $hash,
                'status' => 'validado'
            ]);

            // Confirmar a criação do livro
            Log::info('Livro criado com ID ' . $livro->id . ' e hash ' . $hash);
            
            DB::commit();
            
            // Responder com sucesso
            return response()->json([
                'status' => 'success', 
                'hash' => $hash, 
                'livro_id' => $livro->id
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Erro de validação detalhado:');
            foreach ($e->errors() as $field => $messages) {
                Log::error("Campo '{$field}': " . implode(', ', $messages));
            }
            return response()->json([
                'status' => 'error', 
                'message' => 'Erro de validação', 
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao validar dados do livro: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'status' => 'error', 
                'message' => 'Erro interno no servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recebe e monta os chunks do arquivo.
     */
    public function uploadChunk(Request $request)
    {
        try {
            // Verificar se recebeu o token CSRF
            $csrfToken = $request->header('X-CSRF-TOKEN');
            Log::info('Token CSRF recebido em uploadChunk: ' . ($csrfToken ? 'Sim' : 'Não'));
            Log::info('Dados recebidos em uploadChunk: ', $request->except(['arquivo']));
            
            $hash = $request->input('hash');
            if (!$hash) {
                throw new \Exception('Hash não fornecido na requisição');
            }

            $livro = Livro::where('hash', $hash)->whereIn('status', ['validado', 'uploading'])->first();
            if (!$livro) {
                throw new \Exception('Livro não encontrado ou status inválido. Hash: ' . $hash);
            }
            
            $arquivoChunk = $request->file('arquivo');
            $chunkIndex = (int) $request->input('chunkIndex', 0);
            $totalChunks = (int) $request->input('totalChunks', 1);

            if (!$arquivoChunk || !$arquivoChunk->isValid()) {
                throw new \Exception('Chunk inválido ou não enviado.');
            }

            // Define o caminho temporário baseado no hash
            $tempDir = storage_path('app/public/temp_livros');
            $tempFilePath = $tempDir . '/' . $hash . '.part';

            // Cria diretório temporário se não existir
            if (!File::isDirectory($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            // Abre o arquivo temporário para escrita (append)
            $fileResource = fopen($tempFilePath, 'ab'); // 'ab' para append binário
            if (!$fileResource) {
                 throw new \Exception('Não foi possível abrir o arquivo temporário para escrita.');
            }
            
            // Escreve o conteúdo do chunk no arquivo temporário
            fwrite($fileResource, $arquivoChunk->get());
            fclose($fileResource);

            // Atualiza o status do livro para uploading
            if ($livro->status === 'validado') {
                $livro->update(['status' => 'uploading']);
            }
            
            // Verificar o progresso do upload
            Log::info("Chunk {$chunkIndex} de {$totalChunks} recebido para o livro #{$livro->id} ({$hash})");
            
            return response()->json(['status' => 'success', 'message' => "Chunk {$chunkIndex} recebido."]);

        } catch (\Exception $e) {
            Log::error("Erro no upload do chunk: " . $e->getMessage());
            
            // Tenta limpar o arquivo temporário em caso de erro
            if (!empty($tempFilePath) && File::exists($tempFilePath)) {
                File::delete($tempFilePath);
            }
            // Pode ser necessário reverter o status do livro ou deletá-lo
            if(isset($livro)){
                $livro->update(['status' => 'erro']);
            }
            
            return response()->json(['status' => 'error', 'message' => 'Erro ao processar o chunk: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Finaliza o upload, movendo o arquivo e atualizando o livro.
     */
    public function completeUpload(Request $request)
    {
        DB::beginTransaction();
        try {
            // Verificar se recebeu o token CSRF
            $csrfToken = $request->header('X-CSRF-TOKEN');
            Log::info('Token CSRF recebido em completeUpload: ' . ($csrfToken ? 'Sim' : 'Não'));
            Log::info('Dados recebidos em completeUpload: ', $request->all());
            
            $hash = $request->input('hash');
            if (!$hash) {
                throw new \Exception('Hash não fornecido na requisição de finalização');
            }
            
            $totalChunks = (int) $request->input('totalChunks', 0); // Necessário para garantir que todos os chunks foram enviados
            
            $livro = Livro::where('hash', $hash)->where('status', 'uploading')->first();
            if (!$livro) {
                throw new \Exception('Livro não encontrado ou status inválido para finalização. Hash: ' . $hash);
            }

            $tempFilePath = storage_path('app/public/temp_livros/' . $hash . '.part');
            if (!File::exists($tempFilePath)) {
                throw new \Exception('Arquivo temporário não encontrado: ' . $tempFilePath);
            }
            
            // Lógica para verificar se o arquivo está completo
            Log::info("Verificando arquivo temporário: {$tempFilePath}");
            Log::info("Tamanho do arquivo: " . File::size($tempFilePath) . " bytes");

            $finalDir = storage_path('app/public/livros/pdfs');
            $finalFileName = $hash . '.pdf'; // Nome final usa o hash
            $finalPathRelative = 'livros/pdfs/' . $finalFileName;
            $finalPathAbsolute = $finalDir . '/' . $finalFileName;

            // Cria diretório final se não existir
            if (!File::isDirectory($finalDir)) {
                File::makeDirectory($finalDir, 0755, true);
            }

            // Move o arquivo temporário para o local final
            if (!File::move($tempFilePath, $finalPathAbsolute)) {
                 throw new \Exception('Falha ao mover o arquivo para o destino final.');
            }

            // Atualiza o registro do livro
            $livro->update([
                'arquivo_path' => $finalPathRelative,
                'status' => 'completo'
            ]);

            DB::commit();
            Log::info("Upload completo para o livro #{$livro->id} ({$hash})");
            return response()->json(['status' => 'success', 'message' => 'Livro enviado com sucesso!']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao completar upload: " . $e->getMessage());
             // Tenta limpar o arquivo temporário ou final em caso de erro
            if (!empty($tempFilePath) && File::exists($tempFilePath)) {
                File::delete($tempFilePath);
            }
             if (!empty($finalPathAbsolute) && File::exists($finalPathAbsolute)) {
                File::delete($finalPathAbsolute);
            }
            if(isset($livro)){
                 $livro->update(['status' => 'erro']);
            }
            return response()->json(['status' => 'error', 'message' => 'Erro ao finalizar o upload: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Faz o download do PDF usando o hash.
     */
    public function downloadByHash($hash)
    {
        try {
            $livro = Livro::where('hash', $hash)->where('status', 'completo')->firstOrFail();
            
            if (!$livro->arquivo_path || !Storage::disk('public')->exists($livro->arquivo_path)) {
                return redirect()->route('livros.index')->with('error', 'Arquivo não encontrado.');
            }

            // Usa o nome original para o download
            return Storage::disk('public')->download($livro->arquivo_path, $livro->original_filename);
        } catch (\Exception $e) {
            Log::error("Erro ao fazer download do hash {$hash}: " . $e->getMessage());
            return redirect()->route('livros.index')->with('error', 'Erro ao fazer download do arquivo.');
        }
    }

    /**
     * Visualiza o PDF usando o hash.
     */
    public function viewPDF($hash)
    {
        try {
            $livro = Livro::where('hash', $hash)->where('status', 'completo')->firstOrFail();
            
            if (!$livro->arquivo_path || !Storage::disk('public')->exists($livro->arquivo_path)) {
                return response()->json(['error' => 'Arquivo não encontrado.'], 404);
            }

            // Mostra o arquivo PDF diretamente no navegador com o nome original
            return response()->file(
                Storage::disk('public')->path($livro->arquivo_path), 
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $livro->original_filename . '"'
                ]
            );
        } catch (\Exception $e) {
            Log::error("Erro ao visualizar PDF com hash {$hash}: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao visualizar o arquivo.'], 500);
        }
    }

    /**
     * Exibe o PDF do livro
     */
    public function read(Livro $livro)
    {
        try {
            $path = Storage::path('public/livros/pdfs/' . $livro->arquivo_path);
            
            if (!file_exists($path)) {
                return back()->with('error', 'Arquivo não encontrado');
            }

            return response()->file($path);
        } catch (\Exception $e) {
            Log::error('Erro ao ler livro: ' . $e->getMessage());
            return back()->with('error', 'Erro ao abrir o arquivo');
        }
    }

    /**
     * Faz o download do PDF
     */
    public function download($id)
    {
        try {
            $livro = Livro::findOrFail($id);
            
            if (!$livro->arquivo_path || !Storage::disk('public')->exists($livro->arquivo_path)) {
                return redirect()->back()->with('error', 'Arquivo não encontrado.');
            }

            return Storage::disk('public')->download($livro->arquivo_path);
        } catch (\Exception $e) {
            Log::error('Erro ao fazer download: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao fazer download do arquivo.');
        }
    }

    /**
     * Remove o livro e seu arquivo
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $livro = Livro::findOrFail($id);
            $filePath = $livro->arquivo_path;

            // Remove o arquivo se existir
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            
            // Remove arquivo temporário se existir (caso de erro anterior)
            $tempFilePath = storage_path('app/public/temp_livros/' . $livro->hash . '.part');
             if ($livro->hash && File::exists($tempFilePath)) {
                File::delete($tempFilePath);
            }

            $livro->delete();
            
            DB::commit();
            return redirect()->route('livros.index')->with('success', 'Livro excluído com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao excluir livro ID {$id}: " . $e->getMessage());
            return redirect()->route('livros.index')->with('error', 'Erro ao excluir o livro.');
        }
    }

    public function iniciarLeitura(Request $request, Livro $livro)
    {
        $progresso = LeituraProgresso::firstOrCreate(
            [
                'livro_id' => $livro->id,
                'user_id' => Auth::id(),
            ],
            [
                'status' => 'lendo',
                'pagina_atual' => 1,
                'total_paginas' => $request->total_paginas,
                'data_inicio' => now(),
            ]
        );

        return response()->json(['message' => 'Leitura iniciada com sucesso', 'progresso' => $progresso]);
    }

    public function atualizarProgresso(Request $request, LeituraProgresso $progresso)
    {
        if ($progresso->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $request->validate([
            'pagina_atual' => 'required|integer|min:1|max:' . $progresso->total_paginas,
        ]);

        $progresso->update([
            'pagina_atual' => $request->pagina_atual,
        ]);

        return response()->json(['message' => 'Progresso atualizado com sucesso', 'progresso' => $progresso]);
    }

    public function concluirLeitura(LeituraProgresso $progresso)
    {
        if ($progresso->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $progresso->update([
            'status' => 'lido',
            'pagina_atual' => $progresso->total_paginas,
            'data_conclusao' => now(),
        ]);

        return response()->json(['message' => 'Leitura concluída com sucesso', 'progresso' => $progresso]);
    }

    public function salvarMetas(Request $request)
    {
        $request->validate([
            'meta_estudo' => 'required|string|max:1000',
        ]);

        $progresso = LeituraProgresso::where('user_id', Auth::id())
            ->where('status', 'lendo')
            ->get();

        foreach ($progresso as $p) {
            $p->update([
                'meta_estudo' => $request->meta_estudo,
            ]);
        }

        return response()->json(['message' => 'Metas salvas com sucesso']);
    }

    /**
     * Pesquisa livros por título, autor, categoria ou gênero
     */
    public function pesquisar(Request $request)
    {
        try {
            $termo = $request->input('termo');
            
            if (empty($termo)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Termo de pesquisa não fornecido'
                ], 400);
            }

            $livros = Livro::where('titulo', 'like', "%{$termo}%")
                          ->orWhere('autor', 'like', "%{$termo}%")
                          ->orWhere('categoria', 'like', "%{$termo}%")
                          ->orWhere('genero', 'like', "%{$termo}%")
                          ->get();

            if ($livros->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Nenhum livro encontrado',
                    'data' => []
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $livros
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao pesquisar livros: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao realizar a pesquisa'
            ], 500);
        }
    }
} 