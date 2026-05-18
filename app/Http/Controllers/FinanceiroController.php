<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\Streaming\StreamingConfigService;

class FinanceiroController extends Controller
{
    protected array $sections = [
        'irpf' => [
            'title' => 'IRPF',
            'slug' => 'irpf',
            'description' => 'Centralize comprovantes, recibos e pendências das declarações anuais. Acompanhe o status de entrega, pendências da malha fina e organize anexos por exercício.',
            'highlights' => [
                'Declaração 2025 em andamento',
                'Próxima revisão de documentos em 10/01',
                'Alertas automáticos para recibos faltantes',
            ],
            'actions' => [
                ['label' => 'Checklist de documentos', 'badge' => 'Atualizado'],
                ['label' => 'Pendências Receita', 'badge' => '2 itens'],
                ['label' => 'Arquivos enviados', 'badge' => '38'],
            ],
        ],
        'projecoes' => [
            'title' => 'Projeções e Tabelas',
            'slug' => 'projecoes',
            'description' => 'Painel para acompanhar curvas de receita/despesa, simulações de fluxo de caixa e tabelas parametrizadas (IPCA, CDI, SELIC).',
            'highlights' => [
                'Fluxo projetado positivo para próximos 6 meses',
                'Última atualização de índices: hoje',
                '3 cenários configurados (base, otimista, ajuste)',
            ],
            'actions' => [
                ['label' => 'Atualizar índices', 'badge' => 'Necessário'],
                ['label' => 'Nova projeção', 'badge' => '+'],
                ['label' => 'Exportar tabela', 'badge' => 'CSV'],
            ],
        ],
        'capital' => [
            'title' => 'Capital Geral e Atualizado',
            'slug' => 'capital',
            'description' => 'Consolide saldos de contas bancárias, investimentos, caixa físico e valores provisionados. Visualize evolução mensal e composição por categoria.',
            'highlights' => [
                'Saldo consolidado atualizado às 08:15',
                '4 contas bancárias conciliadas',
                'R$ 12.500 pendentes de conciliação',
            ],
            'actions' => [
                ['label' => 'Conciliar movimentações', 'badge' => '8 novos'],
                ['label' => 'Importar extrato', 'badge' => 'OFX'],
                ['label' => 'Gerar relatório', 'badge' => 'PDF'],
            ],
        ],
        'metas' => [
            'title' => 'Metas e Planejamentos',
            'slug' => 'metas',
            'description' => 'Cadastre objetivos financeiros (curto, médio e longo prazo), acompanhe percentuais atingidos e defina aportes recorrentes.',
            'highlights' => [
                'Meta “Reserva emergencial” 72% concluída',
                'Planejamento anual revisado há 5 dias',
                'Alertas de aporte configurados para dia 5',
            ],
            'actions' => [
                ['label' => 'Nova meta', 'badge' => '+'],
                ['label' => 'Revisar aportes', 'badge' => 'Mensal'],
                ['label' => 'Dashboard metas', 'badge' => '3 em andamento'],
            ],
        ],
        'bens' => [
            'title' => 'Bens e Recursos',
            'slug' => 'bens',
            'description' => 'Inventário detalhado de bens móveis, imóveis, veículos e recursos estratégicos. Registre notas fiscais, localização e situação documental.',
            'highlights' => [
                '12 bens ativos catalogados',
                'Última vistoria registrada há 30 dias',
                '2 documentos vencendo em breve',
            ],
            'actions' => [
                ['label' => 'Adicionar bem', 'badge' => '+'],
                ['label' => 'Check-list documental', 'badge' => 'Urgente'],
                ['label' => 'Exportar inventário', 'badge' => 'Planilha'],
            ],
        ],
    ];

    protected array $irpfBoards = [
        'previstos' => [
            'title' => 'Imposto previsto',
            'description' => 'Arquivos em preparação para o envio oficial. Revise os PDFs e confirme os cálculos antes de transmitir.',
        ],
        'entregues' => [
            'title' => 'Imposto entregue',
            'description' => 'Declarações já transmitidas e recibos homologados. Faça downloads rápidos ou visualize o PDF.',
        ],
        'pendentes' => [
            'title' => 'Impostos a serem calculados',
            'description' => 'Itens pendentes de cálculo ou composição. Organize comprovantes e abra um novo rascunho.',
        ],
    ];

    public function __construct(private StreamingConfigService $streamingConfig)
    {
    }

    public function index(Request $request)
    {
        return view('financeiro', [
            'sections' => $this->sections,
        ] + $this->streamingConfig->build($request));
    }

    public function show(Request $request, string $section)
    {
        $key = strtolower($section);
        if (!array_key_exists($key, $this->sections)) {
            abort(404);
        }

        $current = $this->sections[$key];

        $irpfDocuments = [];
        if ($key === 'irpf') {
            $irpfDocuments = $this->getIrpfDocuments();
        }

        return view('financeiro.show', [
            'section' => $current,
            'sections' => $this->sections,
            'irpfBoards' => $this->irpfBoards,
            'irpfDocuments' => $irpfDocuments,
        ] + $this->streamingConfig->build($request));
    }

    public function uploadIrpf(Request $request)
    {
        $data = $request->validate([
            'bucket' => ['required', 'string'],
            'arquivo' => ['required', 'file', 'mimetypes:application/pdf,application/octet-stream'],
        ]);

        $bucket = $this->ensureIrpfBucket($data['bucket']);
        $file = $data['arquivo'];

        $filename = time() . '_' . $this->sanitizeFilename($file->getClientOriginalName());
        $relativePath = "financeiro/irpf/{$bucket}";
        Storage::disk('local')->putFileAs($relativePath, $file, $filename);

        return redirect()
            ->back()
            ->with('success', 'Arquivo enviado com sucesso!');
    }

    public function downloadIrpf(string $bucket, string $filename)
    {
        $relative = $this->resolveIrpfFilePath($bucket, $filename);
        if (!Storage::disk('local')->exists($relative)) {
            abort(404);
        }

        return Storage::disk('local')->download($relative);
    }

    public function viewIrpf(string $bucket, string $filename)
    {
        $relative = $this->resolveIrpfFilePath($bucket, $filename);
        $absolute = storage_path('app/' . $relative);
        if (!file_exists($absolute)) {
            abort(404);
        }

        return response()->file($absolute, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    protected function getIrpfDocuments(): array
    {
        $disk = Storage::disk('local');
        $base = 'financeiro/irpf';
        $documents = [];

        foreach ($this->irpfBoards as $bucket => $meta) {
            $dir = "{$base}/{$bucket}";
            if (!$disk->exists($dir)) {
                $disk->makeDirectory($dir);
            }

            $files = collect($disk->files($dir))->sortDesc();
            $documents[$bucket] = $files->map(function ($path) use ($disk, $meta, $bucket) {
                $name = basename($path);
                $lastModified = Carbon::createFromTimestamp($disk->lastModified($path))
                    ->locale('pt_BR')
                    ->translatedFormat('d/m/Y \à\s H:i');

                return [
                    'name' => $name,
                    'status' => $meta['title'],
                    'updated' => $lastModified,
                    'size' => $this->formatBytes($disk->size($path)),
                    'bucket' => $bucket,
                ];
            })->all();
        }

        return $documents;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));

        return number_format($bytes / (1024 ** $power), 1, ',', '.') . ' ' . $units[$power];
    }

    protected function ensureIrpfBucket(string $bucket): string
    {
        $bucket = strtolower($bucket);
        if (!array_key_exists($bucket, $this->irpfBoards)) {
            abort(404);
        }

        return $bucket;
    }

    protected function resolveIrpfFilePath(string $bucket, string $filename): string
    {
        $bucket = $this->ensureIrpfBucket($bucket);
        $safeFilename = $this->sanitizeFilename($filename);

        return "financeiro/irpf/{$bucket}/{$safeFilename}";
    }

    protected function sanitizeFilename(string $filename): string
    {
        $filename = trim($filename);
        $filename = str_replace(['../', './', DIRECTORY_SEPARATOR], '', $filename);

        return preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', $filename) ?: 'arquivo.pdf';
    }
}