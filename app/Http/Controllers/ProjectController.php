<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Process\Process;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query()->where('user_id', Auth::id());

        if ($request->filled('linguagem')) {
            $query->where('linguagem', $request->linguagem);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('nome', 'like', "%{$request->search}%")
                  ->orWhere('descricao', 'like', "%{$request->search}%");
            });
        }

        $projetos = $query->latest()->paginate(9);

        return view('dashboard.codigos', compact('projetos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|max:255',
            'descricao' => 'nullable|string',
            'linguagem' => 'required|in:php,python,cpp',
            'codigo' => 'nullable|string'
        ]);

        $project = new Project($validated);
        $project->user_id = Auth::id();
        $project->save();

        return redirect()->route('dashboard.codigos')
            ->with('success', 'Projeto criado com sucesso!');
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'nome' => 'required|max:255',
            'descricao' => 'nullable|string',
            'linguagem' => 'required|in:php,python,cpp',
            'codigo' => 'nullable|string'
        ]);

        $project->update($validated);

        return redirect()->route('dashboard.codigos')
            ->with('success', 'Projeto atualizado com sucesso!');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);
        
        $project->delete();

        return redirect()->route('dashboard.codigos')
            ->with('success', 'Projeto excluído com sucesso!');
    }

    public function execute(Project $project)
    {
        $this->authorize('update', $project);

        try {
            $output = match($project->linguagem) {
                'php' => $this->executePhp($project->codigo),
                'python' => $this->executePython($project->codigo),
                'cpp' => $this->executeCpp($project->codigo),
                default => throw new \Exception('Linguagem não suportada')
            };

            $project->output = $output;
            $project->status = 'active';
            $project->save();

            return response()->json([
                'success' => true,
                'output' => $output
            ]);
        } catch (\Exception $e) {
            $project->output = $e->getMessage();
            $project->status = 'error';
            $project->save();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function executePhp($code)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'php_');
        file_put_contents($tempFile, $code);

        $process = new Process(['php', $tempFile]);
        $process->run();

        unlink($tempFile);

        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    private function executePython($code)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'py_');
        file_put_contents($tempFile, $code);

        $process = new Process(['python3', $tempFile]);
        $process->run();

        unlink($tempFile);

        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    private function executeCpp($code)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cpp_');
        $execFile = $tempFile . '.out';
        file_put_contents($tempFile . '.cpp', $code);

        // Compilar
        $compile = new Process(['g++', $tempFile . '.cpp', '-o', $execFile]);
        $compile->run();

        if (!$compile->isSuccessful()) {
            throw new \Exception($compile->getErrorOutput());
        }

        // Executar
        $process = new Process([$execFile]);
        $process->run();

        // Limpar arquivos temporários
        unlink($tempFile . '.cpp');
        unlink($execFile);

        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }

        return $process->getOutput();
    }
} 