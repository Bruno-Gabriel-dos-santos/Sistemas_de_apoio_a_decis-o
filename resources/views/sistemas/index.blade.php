@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div class="w-full md:w-auto flex justify-start">
            <h1 class="text-2xl font-bold text-gray-800 mb-0">Hub de Sistemas</h1>
        </div>
        <div class="w-full md:w-auto flex justify-center">
            <input type="text" id="search-sistemas" placeholder="Pesquisar sistemas..." class="border rounded px-3 py-2 w-full max-w-md text-center" />
        </div>
        <div class="w-full md:w-auto flex justify-end">
            <button onclick="document.getElementById('modal-add-sistema').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow flex items-center gap-2">
                <i class="fa fa-plus"></i> Novo Sistema
            </button>
        </div>
    </div>

    @if(session('success'))
        <div id="success-alert" class="mb-4 p-4 bg-green-100 text-green-800 rounded shadow">{{ session('success') }}</div>
        <script>
            setTimeout(function() {
                var alert = document.getElementById('success-alert');
                if(alert) alert.style.display = 'none';
            }, 5000);
        </script>
        @php session()->forget('success'); @endphp
    @endif

    <div id="sistemas-cards-container">
        @include('sistemas.partials.cards', ['sistemas' => $sistemas])
    </div>
</div>

<!-- Modal de cadastro -->
<div id="modal-add-sistema" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-lg relative">
        <button onclick="document.getElementById('modal-add-sistema').classList.add('hidden')" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
        <h2 class="text-xl font-bold mb-4">Cadastrar Novo Sistema</h2>
        <form method="POST" action="{{ route('sistemas.store') }}" id="form-criar-sistema">
            @csrf
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Nome</label>
                <input type="text" name="nome" id="campo-nome-sistema" class="w-full border rounded px-3 py-2" required maxlength="255">
                <p class="text-xs text-gray-500 mt-1">URL: <code id="preview-url">{{ url('/sistemas') }}/</code></p>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Título</label>
                <input type="text" name="titulo" class="w-full border rounded px-3 py-2" required maxlength="255">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea name="descricao" class="w-full border rounded px-3 py-2" required></textarea>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Tipo de API</label>
                <select name="template" id="template-api" class="w-full border rounded px-3 py-2">
                    <option value="tunnel">Rota de Túnel</option>
                    <option value="data">API de Dados</option>
                    <option value="processing">API de Processamento</option>
                    <option value="memory">API de Memória</option>
                    <option value="routing">API de Roteamento</option>
                </select>
            </div>

            <details class="mb-3 bg-gray-50 rounded border p-3">
                <summary class="cursor-pointer text-sm font-semibold text-gray-700">Opções avançadas</summary>
                <div class="mt-3 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Comandos/Instruções</label>
                        <textarea name="comandos" id="campo-comandos" class="w-full border rounded px-3 py-2"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Documentação</label>
                        <textarea name="documentacao" id="campo-documentacao" class="w-full border rounded px-3 py-2"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rota (opcional)</label>
                        <input type="text" name="rota" id="campo-rota" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Imagem de capa (URL, opcional)</label>
                        <input type="text" name="imagem_capa" id="campo-imagem" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tags (opcional)</label>
                        <input type="text" name="tags" id="campo-tags" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Categoria</label>
                        <input type="text" name="categoria" id="campo-categoria" class="w-full border rounded px-3 py-2" maxlength="255">
                    </div>
                </div>
            </details>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="document.getElementById('modal-add-sistema').classList.add('hidden')" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded">Cancelar</button>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded font-bold">Salvar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Busca e paginação AJAX com JS puro
(function() {
    var searchInput = document.getElementById('search-sistemas');
    var container = document.getElementById('sistemas-cards-container');

    function buscarSistemas(page) {
        var query = searchInput.value.trim();
        var url = '/sistemas';
        if (query) {
            url = '/sistemas/busca?query=' + encodeURIComponent(query) + '&page=' + page;
        } else {
            url = '/sistemas?page=' + page;
        }
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                container.innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    }

    // Busca instantânea
    searchInput.addEventListener('input', function() {
        buscarSistemas(1);
    });

    // Delegação para paginação customizada e links
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-paginacao');
        if (btn) {
            e.preventDefault();
            var page = btn.getAttribute('data-page');
            buscarSistemas(page);
            return;
        }
        var link = e.target.closest('.pagination a');
        if (link) {
            e.preventDefault();
            var url = new URL(link.href, window.location.origin);
            var page = url.searchParams.get('page') || 1;
            buscarSistemas(page);
        }
    });
})();

(function() {
    const nomeInput = document.getElementById('campo-nome-sistema');
    const previewUrl = document.getElementById('preview-url');
    const templateSelect = document.getElementById('template-api');

    const campoComandos = document.getElementById('campo-comandos');
    const campoDocumentacao = document.getElementById('campo-documentacao');
    const campoRota = document.getElementById('campo-rota');
    const campoImagem = document.getElementById('campo-imagem');
    const campoTags = document.getElementById('campo-tags');
    const campoCategoria = document.getElementById('campo-categoria');

    const templates = {
        tunnel: {
            comandos: "POST /api/dinamic-api\\n{\\n  \\\"id\\\": @{{ID}},\\n  \\\"payload\\\": {}\\n}",
            documentacao: "Esta API atua como túnel para outros serviços internos.",
            rota: "/api/tunnel/@{{slug}}",
            categoria: "Túnel",
            tags: "tunnel,proxy",
        },
        data: {
            comandos: "POST /api/dinamic-api\\n{\\n  \\\"id\\\": @{{ID}},\\n  \\\"query\\\": \\\"...\\\"\\n}",
            documentacao: "Retorna dados estruturados (CRUD, relatórios, etc).",
            rota: "/api/data/@{{slug}}",
            categoria: "Dados",
            tags: "data,read",
        },
        processing: {
            comandos: "POST /api/dinamic-api\\n{\\n  \\\"id\\\": @{{ID}},\\n  \\\"job\\\": \\\"process\\\"\\n}",
            documentacao: "Executa rotinas de processamento ou ETL.",
            rota: "/api/processing/@{{slug}}",
            categoria: "Processamento",
            tags: "processing,etl",
        },
        memory: {
            comandos: "POST /api/dinamic-api\\n{\\n  \\\"id\\\": @{{ID}},\\n  \\\"action\\\": \\\"store\\\"\\n}",
            documentacao: "Gerencia caches ou informações temporárias.",
            rota: "/api/memory/@{{slug}}",
            categoria: "Memória",
            tags: "memory,cache",
        },
        routing: {
            comandos: "POST /api/dinamic-api\\n{\\n  \\\"id\\\": @{{ID}},\\n  \\\"route\\\": \\\"...\\\"\\n}",
            documentacao: "Centraliza regras de roteamento e integrações externas.",
            rota: "/api/router/@{{slug}}",
            categoria: "Roteamento",
            tags: "routing,orchestrator",
        }
    };

    function slugify(value) {
        return value
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-zA-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .toLowerCase();
    }

    function updatePreview() {
        if (!nomeInput || !previewUrl) return;
        const slug = slugify(nomeInput.value || '');
        previewUrl.textContent = slug ? `{{ url('/sistemas') }}/${slug}` : `{{ url('/sistemas') }}/`;
    }

    function applyTemplate() {
        const selected = templates[templateSelect.value];
        if (!selected) return;
        const slug = slugify(nomeInput.value || 'api');
        const replaceVars = (text) => text.replace('@{{ID}}', '...').replace('@{{slug}}', slug || 'api');
        campoComandos.value = replaceVars(selected.comandos);
        campoDocumentacao.value = selected.documentacao;
        campoRota.value = replaceVars(selected.rota);
        campoCategoria.value = selected.categoria;
        campoTags.value = selected.tags;
        campoImagem.value = campoImagem.value || '';
    }

    if (nomeInput) {
        nomeInput.addEventListener('input', function() {
            updatePreview();
            applyTemplate();
        });
    }
    if (templateSelect) {
        templateSelect.addEventListener('change', applyTemplate);
    }

    updatePreview();
    applyTemplate();
})();
</script>
@endpush 