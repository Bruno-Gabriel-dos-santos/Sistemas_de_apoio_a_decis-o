<?php
namespace App\Http\Controllers;

use App\Models\ApiSistema;
use App\Models\Sistema;
use App\Services\SystemDatabaseProvisioner;
use App\Services\Streaming\StreamingConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SistemasController extends Controller
{
    public function __construct(private readonly SystemDatabaseProvisioner $databaseProvisioner, private StreamingConfigService $streamingConfig)
    {
    }
    // Exibe todos os sistemas como cards
    public function index(Request $request)
    {
        $sistemas = \App\Models\Sistema::orderBy('ordem')->orderBy('id')->paginate(6);

        if ($request->ajax()) {
            return view('sistemas.partials.cards', compact('sistemas'))->render();
        }

        return view('sistemas.index', compact('sistemas'));
    }

    // Cadastra novo sistema e cria a pasta
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:sistemas,nome',
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string',
            'comandos' => 'nullable|string',
            'documentacao' => 'nullable|string',
            'rota' => 'nullable|string',
            'pasta' => 'nullable|string',
            'imagem_capa' => 'nullable|string',
            'tags' => 'nullable|string',
            'categoria' => 'nullable|string',
        ]);

        $sistema = Sistema::create(array_merge($validated, [
            'data_inicio' => now(),
            'ativo' => true,
        ]));

        // Cria a pasta sistemas/{id}
        $pasta = 'sistemas/' . $sistema->id;
        Storage::makeDirectory($pasta);
        $sistema->pasta = $pasta;
        $sistema->save();

        $dbCredentials = $this->databaseProvisioner->provision($sistema);
        $sistema->update([
            'db_name' => $dbCredentials['database'],
            'db_username' => $dbCredentials['username'],
            'db_password' => $dbCredentials['password'],
            'db_host' => $dbCredentials['host'],
        ]);

        $this->scaffoldApiFiles($sistema, $dbCredentials);
        $this->createDefaultPage($sistema);

        return redirect()->route('sistemas.index')->with('success', 'Sistema criado com sucesso!');
    }

    // Busca AJAX de sistemas com paginação
    public function busca(Request $request)
    {
        $query = $request->input('query');
        $sistemas = \App\Models\Sistema::where('nome', 'like', "%$query%")
            ->orWhere('titulo', 'like', "%$query%")
            ->orWhere('categoria', 'like', "%$query%")
            ->orderBy('ordem')->orderBy('id')
            ->paginate(6);
        return view('sistemas.partials.cards', compact('sistemas'))->render();
    }

    // Exibe a view do sistema pelo slug
    public function show($slug)
    {
        $sistema = Sistema::where('nome', $slug)->firstOrFail();
        $posts = $sistema->apiSistemas()->orderBy('ordem')->orderBy('id')->get();
        // Build streaming config (websocket URLs, upload token) so uploader JS can initialize
        $config = $this->streamingConfig->build(request());
        return view('sistemas.show', compact('sistema', 'posts') + $config);
    }

    // Exclusão completa de sistema, páginas e pastas
    public function destroyCompleto($id)
    {
        $sistema = \App\Models\Sistema::findOrFail($id);
        // Excluir todas as páginas relacionadas
        $paginas = \App\Models\ApiSistema::where('sistema_id', $id)->get();
        foreach ($paginas as $pagina) {
            \Storage::deleteDirectory("paginaSistemas/{$pagina->id}");
            $pagina->delete();
        }
        // Excluir pasta do sistema
        \Storage::deleteDirectory("sistemas/{$id}");
        // Excluir o sistema
        $sistema->delete();
        return redirect()->route('sistemas.index')->with('success', 'Sistema e dados relacionados excluídos com sucesso!');
    }

    protected function scaffoldApiFiles(Sistema $sistema, array $dbCredentials = []): void
    {
        $basePath = "sistemas/{$sistema->id}";
        $endpoint = url('/api/dinamic-api');
        $baseUrl = url('/');
        $createdAt = now()->toDateTimeString();
        $secretExpiryLabel = 'Sem expiração automática (alterar manualmente)';
        $readmeAbsolute = storage_path("app/{$basePath}/README.md");

        $helpersPath = "{$basePath}/helpers.php";
        $apiSecret = null;
        if (Storage::exists($helpersPath)) {
            $existingHelpers = Storage::get($helpersPath);
            if (preg_match("/API_SECRET_KEY', '([^']+)'/", $existingHelpers, $matches)) {
                $apiSecret = $matches[1];
            }
        }
        if (!$apiSecret) {
            $apiSecret = Str::random(40);
        }

        $dbInfo = [
            'database' => $dbCredentials['database'] ?? $sistema->db_name,
            'username' => $dbCredentials['username'] ?? $sistema->db_username,
            'password' => $dbCredentials['password'] ?? $sistema->db_password,
            'host' => $dbCredentials['host'] ?? $sistema->db_host ?? 'localhost',
        ];

        if (!Storage::exists($helpersPath)) {
            $helpersStub = $this->getStub('sistema-helpers.stub', $this->defaultHelpersStub());
            $helpersContent = str_replace(
                ['{{endpoint}}', '{{system_id}}', '{{base_url}}', '{{api_secret}}', '{{api_secret_expires_at}}', '{{db_name}}', '{{db_username}}', '{{db_password}}', '{{db_host}}'],
                [$endpoint, $sistema->id, $baseUrl, $apiSecret, $secretExpiryLabel, $dbInfo['database'], $dbInfo['username'], $dbInfo['password'], $dbInfo['host']],
                $helpersStub
            );
            Storage::put($helpersPath, $helpersContent);
        }

        if (!Storage::exists("{$basePath}/client-example.php")) {
            $clientStub = $this->getStub('sistema-client.stub', $this->defaultClientStub());
            Storage::put("{$basePath}/client-example.php", $clientStub);
        }

        if (!Storage::exists("{$basePath}/chamada.php")) {
            $callStub = $this->getStub('sistema-call.stub', $this->defaultCallStub());
            Storage::put("{$basePath}/chamada.php", $callStub);
        }

        if (!Storage::exists("{$basePath}/start.php")) {
            $startStub = $this->getStub('sistema-start.stub', $this->defaultStartStub());
            $startContent = str_replace(
                ['{{system_name}}', '{{readme_path}}'],
                [$sistema->nome, $readmeAbsolute],
                $startStub
            );
            Storage::put("{$basePath}/start.php", $startContent);
        }

        if (!Storage::exists("{$basePath}/README.md")) {
            $readmeStub = $this->getStub('sistema-readme.stub', $this->defaultReadmeStub());
            $readmeContent = str_replace(
                ['{{system_name}}', '{{system_id}}', '{{endpoint}}', '{{created_at}}', '{{api_secret}}', '{{base_url}}', '{{api_secret_validity}}', '{{db_name}}', '{{db_username}}', '{{db_password}}', '{{db_host}}'],
                [$sistema->nome, $sistema->id, $endpoint, $createdAt, $apiSecret, $baseUrl, $secretExpiryLabel, $dbInfo['database'], $dbInfo['username'], $dbInfo['password'], $dbInfo['host']],
                $readmeStub
            );
            Storage::put("{$basePath}/README.md", $readmeContent);
        }
    }

    protected function createDefaultPage(Sistema $sistema): void
    {
        if ($sistema->apiSistemas()->exists()) {
            return;
        }

        $endpoint = url('/api/dinamic-api');
        $pageStub = $this->getStub('sistema-page.stub', $this->defaultPageStub());
        $pageContent = str_replace(
            ['{{system_name}}', '{{system_id}}', '{{endpoint}}'],
            [$sistema->nome, $sistema->id, $endpoint],
            $pageStub
        );

        ApiSistema::create([
            'sistema_id' => $sistema->id,
            'titulo' => 'Introdução à API',
            'descricao' => 'Exemplo de uso e instruções iniciais.',
            'data' => now(),
            'conteudo' => $pageContent,
            'ordem' => 1,
            'tipo' => 'documentacao',
            'publicado' => true,
            'slug' => Str::slug($sistema->nome) . '-intro',
        ]);
    }

    protected function getStub(string $filename, string $fallback): string
    {
        $path = resource_path("stubs/{$filename}");
        return File::exists($path) ? File::get($path) : $fallback;
    }

    protected function defaultStartStub(): string
    {
        return <<<'PHP'
<?php

if (file_exists(__DIR__ . '/helpers.php')) {
    require_once __DIR__ . '/helpers.php';
}

/** @var array $input */
/** @var int $id */

if (function_exists('api_response')) {
    return api_response([
        'message' => 'Hello World',
        'system_id' => $id,
        'payload_received' => $input,
    ]);
}

return response()->json([
    'message' => 'Hello World',
    'system_id' => $id,
    'payload_received' => $input,
]);
PHP;
    }

    protected function defaultCallStub(): string
    {
        return <<<'PHP'
<?php
require __DIR__.'/helpers.php';
$payload = ['id' => API_SYSTEM_ID];
print_r(api_curl_request('POST', API_ENDPOINT, $payload));
PHP;
    }

    protected function defaultReadmeStub(): string
    {
        return <<<'MD'
# API

- Endpoint: {{endpoint}}
- ID: {{system_id}}
- API KEY: {{api_secret}}

Consuma com:

```bash
curl -X POST {{endpoint}} \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: {{api_secret}}" \
  -d '{"id": {{system_id}}}'
```
MD;
    }

    protected function defaultHelpersStub(): string
    {
        return <<<'PHP'
<?php

define('API_ENDPOINT', '');
define('API_SYSTEM_ID', 0);
define('API_BASE_URL', '');
define('API_SECRET_KEY', '');
define('API_SECRET_EXPIRES_AT', 'Sem expiração automática');
define('API_DB_CONFIG', [
    'host' => '',
    'database' => '',
    'username' => '',
    'password' => '',
    'charset' => 'utf8mb4',
]);

if (!function_exists('api_response')) {
    function api_response(array $data = [], int $status = 200) {
        return response()->json($data, $status);
    }
}

function api_curl_request(string $method, string $url, array $data = []): array
{
    return [];
}

function api_db(): ?PDO
{
    return null;
}
PHP;
    }

    protected function defaultClientStub(): string
    {
        return <<<'PHP'
<?php
require __DIR__.'/helpers.php';
print_r(api_curl_post());
PHP;
    }

    protected function defaultPageStub(): string
    {
        return '<p>Atualize esta página com detalhes da sua API.</p>';
    }
} 