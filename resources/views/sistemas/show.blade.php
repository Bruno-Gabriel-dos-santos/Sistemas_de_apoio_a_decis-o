@extends('layouts.app')

<style>
    /* Minimal styling to guarantee buttons/panels render correctly even if a push/stack is not used */
    .sistema-tab-button { cursor: pointer; }
    .sistema-tab-button.active { box-shadow: 0 6px 18px rgba(99,102,241,0.18); }
    @keyframes fadeInTabs { from { opacity: 0; transform: translateY(8px);} to { opacity: 1; transform: translateY(0);} }
</style>

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $sistema->nome }}</h1>
        <span class="text-lg text-gray-500">Categoria: <span class="font-bold text-indigo-700">{{ $sistema->categoria }}</span></span>
    </div>

    <div class="space-y-4">
        <div class="sistema-tab-buttons flex flex-wrap gap-3">
            <button type="button" class="sistema-tab-button px-4 py-2 rounded-md bg-indigo-600 text-white font-semibold" data-tab-target="paginas">
                <i class="fa fa-book-open mr-2"></i>Páginas do Sistema
            </button>
            <button type="button" class="sistema-tab-button px-4 py-2 rounded-md bg-gray-200 text-gray-700 font-semibold" data-tab-target="arquivos">
                <i class="fa fa-folder mr-2"></i>Arquivos
            </button>
            <button type="button" class="sistema-tab-button px-4 py-2 rounded-md bg-gray-200 text-gray-700 font-semibold" data-tab-target="db">
                <i class="fa fa-database mr-2"></i>Banco de Dados
            </button>
        </div>

        <div class="space-y-10">
            <section id="quadro-paginas" data-panel="paginas" class="sistema-tab-panel bg-white rounded-xl shadow-lg p-6 w-full">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                    <div class="flex items-center gap-4">
                        <h2 class="text-lg font-bold text-indigo-700">Páginas do Sistema</h2>
                        <div class="flex items-center gap-2">
                            <button type="button" id="btn-anterior" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1 rounded">&larr;</button>
                            <span class="text-xs text-gray-500">Total: <span id="pagina-atual" class="font-bold">1</span> de <span id="total-paginas" class="font-bold">{{ $posts->count() }}</span></span>
                            <button type="button" id="btn-proxima" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1 rounded">&rarr;</button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('paginas_sistemas.create', $sistema->id) }}" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded shadow text-sm">Nova Página</a>
                        <a id="btn-editar" href="#" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded shadow text-sm hidden">Editar</a>
                        <form id="form-excluir" action="#" method="POST" class="inline hidden">
                            @csrf
                            @method('DELETE')
                            <input type="password" name="senha" placeholder="Senha" class="border rounded px-2 py-1 text-xs w-20" required>
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded shadow text-sm ml-1">Excluir</button>
                        </form>
                        <a id="btn-upload" href="#" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded shadow text-sm">Upload</a>
                    </div>
                </div>
                @if($posts->isEmpty())
                    <div class="text-gray-500 text-center py-8">Nenhuma página de blog criada ainda.</div>
                @else
                    @php $pagina = $posts[0]; @endphp
                    <div id="visualizacao-pagina-blog" class="border rounded p-6 bg-gray-50">
                        <h1 class="text-2xl font-bold text-indigo-800 mb-2">{{ $pagina->titulo }}</h1>
                        <div class="text-xs text-gray-500 mb-2">{{ $pagina->data ? substr($pagina->data, 0, 10) : '' }}</div>
                        <div class="text-base text-gray-700 mb-4">{{ $pagina->descricao }}</div>
                        <hr class="mb-4">
                        {!! $pagina->conteudo !!}
                    </div>
                @endif
            </section>

            <section id="quadro-arquivos" data-panel="arquivos" class="sistema-tab-panel hidden bg-white rounded-xl shadow-lg p-6 w-full">
                @include('components.gerenciador-arquivos', ['sistema' => $sistema])
            </section>

            <section id="quadro-db" data-panel="db" class="sistema-tab-panel hidden bg-white rounded-xl shadow-lg p-6 w-full space-y-4">
                <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-indigo-700">Banco: {{ $sistema->db_name ?? 'não provisionado' }}</h3>
                            <p class="text-sm text-gray-500">Usuário: <code>{{ $sistema->db_username ?? '-' }}</code> &bull; Host: <code>{{ $sistema->db_host ?? 'localhost' }}</code></p>
                        </div>
                        <div class="flex gap-2">
                            <button id="btn-recarregar-tabelas" class="px-3 py-1 text-sm bg-gray-200 rounded hover:bg-gray-300">Recarregar tabelas</button>
                            <button id="btn-create-table-toggle" class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700">Criar Tabela</button>
                            <button id="btn-drop-table-toggle" class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">Excluir Tabela</button>
                            <button id="btn-modify-table-toggle" class="px-3 py-1 text-sm bg-yellow-600 text-white rounded hover:bg-yellow-700">Modificar Tabela</button>
                            <button id="btn-modify-data-toggle" class="px-3 py-1 text-sm bg-purple-600 text-white rounded hover:bg-purple-700">Modificar Dados</button>
                            <button id="btn-insert-row-toggle" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Inserir Dados</button>
                        </div>
                    </div>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 border rounded-lg p-3 max-h-80 overflow-y-auto md:col-span-1">
                            <h4 class="font-semibold text-sm text-gray-700 mb-2">Tabelas</h4>
                            <ul id="db-table-list" class="space-y-1 text-sm"></ul>
                        </div>
                        <div class="md:col-span-3 border rounded-lg">
                            <div class="flex items-center justify-between px-4 py-2 border-b bg-gray-50">
                                <div>
                                    <h4 id="db-active-table" class="font-semibold text-gray-800">Selecione uma tabela</h4>
                                    <p class="text-xs text-gray-500">Visualização limitada aos primeiros 50 registros.</p>
                                </div>
                                <button id="btn-atualizar-tabela" class="hidden px-3 py-1 text-xs bg-indigo-100 text-indigo-700 rounded">Atualizar</button>
                            </div>
                            <div id="db-table-data" class="p-0 max-h-96 overflow-auto text-sm"></div>
                        </div>
                    </div>
                </div>
                <div id="db-manage-panels" class="space-y-4">
                    <!-- Create Table: form-based (like phpMyAdmin) -->
                    <div id="create-table-panel" class="hidden bg-white rounded-xl shadow-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold">Criar Tabela</h4>
                            <div class="text-xs text-gray-500">Use o formulário abaixo para montar a tabela. Você pode gerar o SQL automaticamente.</div>
                        </div>
                        <div class="grid grid-cols-3 gap-3 mb-3">
                            <input id="create-table-name" class="col-span-2 border rounded px-2 py-1" placeholder="Nome da tabela" />
                            <select id="create-table-engine" class="border rounded px-2 py-1">
                                <option value="InnoDB">InnoDB</option>
                                <option value="MyISAM">MyISAM</option>
                            </select>
                        </div>
                        <div class="overflow-auto border rounded mb-2">
                            <table class="min-w-full text-sm" id="create-columns-table">
                                <thead class="bg-gray-100"><tr>
                                    <th class="px-2 py-1">Coluna</th>
                                    <th class="px-2 py-1">Tipo</th>
                                    <th class="px-2 py-1">Tamanho</th>
                                    <th class="px-2 py-1">Nulo</th>
                                    <th class="px-2 py-1">Padrão</th>
                                    <th class="px-2 py-1">PK</th>
                                    <th class="px-2 py-1">AI</th>
                                    <th class="px-2 py-1">Ações</th>
                                </tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="flex gap-2 mb-2">
                            <button id="add-column-btn" class="px-3 py-1 bg-gray-200 rounded">Adicionar Coluna</button>
                            <button id="generate-create-sql" class="px-3 py-1 bg-indigo-600 text-white rounded">Gerar SQL</button>
                        </div>
                        <textarea id="create-table-sql" rows="4" class="w-full border rounded px-3 py-2 text-sm mb-2" placeholder="CREATE TABLE ..."></textarea>
                        <div class="flex justify-end gap-2">
                            <button id="btn-create-table" class="px-3 py-1 bg-green-600 text-white rounded">Criar Tabela</button>
                        </div>
                        <div id="create-table-result" class="text-sm mt-2"></div>
                    </div>

                    <!-- Drop Table -->
                    <div id="drop-table-panel" class="hidden bg-white rounded-xl shadow-lg p-4">
                        <h4 class="font-semibold mb-2">Excluir Tabela</h4>
                        <div class="flex gap-2 items-center mb-2">
                            <select id="drop-table-select" class="border rounded px-2 py-1 text-sm w-full"></select>
                            <button id="btn-drop-table" class="px-3 py-1 bg-red-600 text-white rounded">Excluir</button>
                        </div>
                        <label class="text-xs"><input type="checkbox" id="drop-confirm" class="mr-2"> Confirmo que desejo excluir a tabela selecionada</label>
                        <div id="drop-table-result" class="text-sm mt-2"></div>
                    </div>

                    <!-- Modify Table -->
                    <div id="modify-table-panel" class="hidden bg-white rounded-xl shadow-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold">Modificar Tabela</h4>
                            <div class="text-xs text-gray-500">Edite colunas existentes, adicione novas ou remova. Gere o ALTER TABLE e execute.</div>
                        </div>
                        <div class="overflow-auto border rounded mb-2">
                            <table class="min-w-full text-sm" id="modify-columns-table">
                                <thead class="bg-gray-100"><tr>
                                    <th class="px-2 py-1">Coluna (Atual)</th>
                                    <th class="px-2 py-1">Novo Nome</th>
                                    <th class="px-2 py-1">Tipo</th>
                                    <th class="px-2 py-1">Tamanho</th>
                                    <th class="px-2 py-1">Nulo</th>
                                    <th class="px-2 py-1">Padrão</th>
                                    <th class="px-2 py-1">AI</th>
                                    <th class="px-2 py-1">Ações</th>
                                </tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="flex gap-2 mb-2">
                            <button id="add-new-column-btn" class="px-3 py-1 bg-gray-200 rounded">Adicionar Nova Coluna</button>
                            <button id="generate-alter-sql" class="px-3 py-1 bg-indigo-600 text-white rounded">Gerar ALTER SQL</button>
                        </div>
                        <textarea id="modify-table-sql" rows="4" class="w-full border rounded px-3 py-2 text-sm mb-2" placeholder="ALTER TABLE ..."></textarea>
                        <div class="flex justify-end gap-2">
                            <button id="btn-execute-alter" class="px-3 py-1 bg-yellow-600 text-white rounded">Executar ALTER</button>
                        </div>
                        <div id="modify-table-result" class="text-sm mt-2"></div>
                    </div>

                    <!-- Insert Row: dynamic form generated from table columns -->
                    <div id="insert-row-panel" class="hidden bg-white rounded-xl shadow-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold">Inserir Dados</h4>
                            <div class="text-xs text-gray-500">Selecione a tabela na lista à esquerda para preencher o formulário automaticamente.</div>
                        </div>
                        <div id="insert-form-container" class="space-y-2">
                            <div class="text-sm text-gray-500">Nenhuma tabela selecionada.</div>
                        </div>
                        <div class="flex justify-end mt-2">
                            <button id="btn-insert-row" class="px-3 py-1 bg-blue-600 text-white rounded">Inserir</button>
                        </div>
                        <div id="insert-row-result" class="text-sm mt-2"></div>
                    </div>
                    <!-- Modify Data -->
                    <div id="modify-data-panel" class="hidden bg-white rounded-xl shadow-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold">Modificar Dados</h4>
                            <div class="text-xs text-gray-500">Edite ou exclua registros da tabela selecionada. Selecione a tabela na lista à esquerda.</div>
                        </div>
                        <div id="modify-data-container" class="overflow-auto max-h-64 border rounded p-2 text-sm"></div>
                        <div id="modify-data-editor" class="hidden mt-3 bg-gray-50 border rounded p-3">
                            <h5 class="font-medium text-sm mb-2">Editar Registro</h5>
                            <div id="modify-data-form" class="grid grid-cols-1 md:grid-cols-2 gap-2"></div>
                            <div class="flex gap-2 justify-end mt-2">
                                <button id="btn-save-row" class="px-3 py-1 bg-green-600 text-white rounded">Salvar</button>
                                <button id="btn-cancel-edit" class="px-3 py-1 bg-gray-200 rounded">Cancelar</button>
                                <button id="btn-delete-row" class="px-3 py-1 bg-red-600 text-white rounded">Excluir</button>
                            </div>
                            <div id="modify-data-result" class="text-sm mt-2"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-semibold text-gray-800">SQL rápido</h4>
                        <button id="btn-sql-template" class="text-xs text-indigo-600 hover:text-indigo-800">Inserir template SELECT</button>
                    </div>
                    <textarea id="db-sql-input" rows="4" class="w-full border rounded px-3 py-2 text-sm" placeholder="SELECT * FROM tabela LIMIT 10;"></textarea>
                    <div class="flex justify-end">
                        <button id="btn-executar-sql" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">Executar</button>
                    </div>
                    <pre id="db-sql-result" class="bg-gray-900 text-green-200 text-xs p-3 rounded max-h-72 overflow-auto"></pre>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        console.log('sistemas.show script init');
        const tabButtons = document.querySelectorAll('.sistema-tab-button');
    const tabPanels = document.querySelectorAll('.sistema-tab-panel');
    const painelArquivos = document.getElementById('quadro-arquivos');

    function desativarTodos() {
        tabButtons.forEach(btn => {
            btn.classList.remove('bg-indigo-600', 'text-white');
            btn.classList.add('bg-gray-200', 'text-gray-700');
            btn.classList.remove('active');
        });
        tabPanels.forEach(panel => panel.classList.add('hidden'));
    }

    function ativarAba(target) {
        desativarTodos();
        const btn = document.querySelector(`.sistema-tab-button[data-tab-target="${target}"]`);
        if (btn) {
            btn.classList.remove('bg-gray-200', 'text-gray-700');
            btn.classList.add('bg-indigo-600', 'text-white', 'active');
        }
        const panel = document.querySelector(`.sistema-tab-panel[data-panel="${target}"]`);
        if (panel) {
            panel.classList.remove('hidden');
            panel.style.animation = 'fadeInTabs 0.25s ease';
        }
        if (target === 'arquivos' && painelArquivos) {
            setTimeout(() => painelArquivos.scrollIntoView({ behavior: 'smooth', block: 'start' }), 120);
        }
        if (target === 'db' && typeof carregarTabelas === 'function') {
            carregarTabelas();
        }
    }

    tabButtons.forEach(btn => btn.addEventListener('click', () => ativarAba(btn.dataset.tabTarget || 'paginas')));
    ativarAba('paginas');

    // Navegação entre páginas do sistema
    const posts = @json($posts->values());
    let idxAtual = 0;
    const visualizacao = document.getElementById('visualizacao-pagina-blog');
    const paginaAtualSpan = document.getElementById('pagina-atual');
    const btnAnterior = document.getElementById('btn-anterior');
    const btnProxima = document.getElementById('btn-proxima');
    const btnEditar = document.getElementById('btn-editar');
    const btnUpload = document.getElementById('btn-upload');
    const formExcluir = document.getElementById('form-excluir');

    function renderizarPagina(idx) {
        if(!posts[idx]) return;
        const post = posts[idx];
        paginaAtualSpan.textContent = (idx+1);
        visualizacao.innerHTML = `
            <h1 class='text-2xl font-bold text-indigo-800 mb-2'>${post.titulo}</h1>
            <div class='text-xs text-gray-500 mb-2'>${post.data ? post.data.substring(0, 10) : ''}</div>
            <div class='text-base text-gray-700 mb-4'>${post.descricao}</div>
            <hr class='mb-4'>
            ${post.conteudo}
        `;
        if (btnEditar) {
            btnEditar.classList.remove('hidden');
            btnEditar.href = `/sistemas/${post.sistema_id}/paginas_sistemas/${post.id}/edit`;
        }
        if (formExcluir) {
            formExcluir.classList.remove('hidden');
            formExcluir.action = `/sistemas/${post.sistema_id}/paginas_sistemas/${post.id}`;
            const senhaInput = formExcluir.querySelector('input[name="senha"]');
            if (senhaInput) senhaInput.value = '';
        }
    }
    if(posts.length > 0) renderizarPagina(idxAtual);
    if(btnAnterior) btnAnterior.onclick = function() { if(idxAtual > 0) { idxAtual--; renderizarPagina(idxAtual); } };
    if(btnProxima) btnProxima.onclick = function() { if(idxAtual < posts.length-1) { idxAtual++; renderizarPagina(idxAtual); } };
    if(formExcluir) formExcluir.addEventListener('submit', function(e) {
        const senha = formExcluir.querySelector('input[name="senha"]').value;
        if(senha !== '123') { alert('Senha incorreta!'); e.preventDefault(); }
    });

    function irParaUpload() {
        if(!posts[idxAtual]) return;
        const paginaId = posts[idxAtual].id;
        const sistemaId = posts[idxAtual].sistema_id;
        window.location.href = `/sistemas/${sistemaId}/paginas_sistemas/${paginaId}/upload`;
    }
    if (btnUpload) btnUpload.addEventListener('click', function(event) { event.preventDefault(); irParaUpload(); });

    // Banco de dados
    const sistemaId = {{ $sistema->id }};
    const dbTableList = document.getElementById('db-table-list');
    const dbTableData = document.getElementById('db-table-data');
    const dbActiveTable = document.getElementById('db-active-table');
    const btnAtualizarTabela = document.getElementById('btn-atualizar-tabela');
    const btnRecarregarTabelas = document.getElementById('btn-recarregar-tabelas');
    const btnOpenAdminer = document.getElementById('btn-open-adminer');
    const adminerFrame = document.getElementById('adminerFrame');
    const sqlInput = document.getElementById('db-sql-input');
    const sqlResult = document.getElementById('db-sql-result');
    let tabelaSelecionada = null;
    const hasDbProvisioned = {{ $sistema->db_name ? 'true' : 'false' }};

    function carregarTabelas() {
        if (!dbTableList) return;
        if (!hasDbProvisioned) {
            dbTableList.innerHTML = '<li class="text-sm text-gray-500">Banco não provisionado para este sistema.</li>';
            if (dbTableData) dbTableData.innerHTML = '<div class="p-4 text-sm text-gray-500">Nenhum banco dedicado configurado.</div>';
            return;
        }
        fetch(`/sistemas/${sistemaId}/db/tables`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(resp => resp.json())
            .then(data => {
                dbTableList.innerHTML = '';
                (data.tables || []).forEach(table => {
                    const li = document.createElement('li');
                    li.className = 'px-2 py-1 rounded hover:bg-indigo-100 cursor-pointer flex items-center justify-between';
                    li.dataset.table = table.Name;
                    li.innerHTML = `<span>${table.Name}</span><span class="text-xs text-gray-400">${table.Rows}</span>`;
                    li.onclick = function() { 
                        // mark active
                        dbTableList.querySelectorAll('li').forEach(n=>n.classList.remove('bg-indigo-100','font-semibold','active'));
                        this.classList.add('bg-indigo-100','font-semibold','active');
                        selecionarTabela(table.Name);
                    };
                    dbTableList.appendChild(li);
                });
                // auto-select first table if none selected
                if (!tabelaSelecionada) {
                    const first = dbTableList.querySelector('li');
                    if (first) { first.classList.add('bg-indigo-100','font-semibold','active'); selecionarTabela(first.dataset.table); }
                }
                // cache and populate drop-table-select
                window.lastTables = data.tables || [];
                const dropSelect = document.getElementById('drop-table-select');
                if (dropSelect) {
                    dropSelect.innerHTML = '';
                    (window.lastTables || []).forEach(t=>{ const opt=document.createElement('option'); opt.value = t.Name; opt.textContent = t.Name; dropSelect.appendChild(opt); });
                    // try to keep selection in sync with tabelaSelecionada
                    try {
                        if (tabelaSelecionada) dropSelect.value = tabelaSelecionada;
                        else if (dropSelect.options && dropSelect.options.length) dropSelect.value = dropSelect.options[0].value;
                    } catch(e){}
                }
            })
            .catch(() => dbTableList.innerHTML = '<li class="text-sm text-red-500">Erro ao carregar tabelas.</li>');
    }

    function selecionarTabela(nome) {
        tabelaSelecionada = nome;
        if (dbActiveTable) dbActiveTable.textContent = nome;
        if (btnAtualizarTabela) { btnAtualizarTabela.classList.remove('hidden'); btnAtualizarTabela.onclick = () => selecionarTabela(nome); }
        // sync drop select
        const dropSelect = document.getElementById('drop-table-select');
        if (dropSelect) { try { dropSelect.value = nome; } catch(e){} }
        fetch(`/sistemas/${sistemaId}/db/tables/${nome}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(resp => resp.json())
            .then(data => renderizarTabela(data))
            .catch(err => { if (dbTableData) dbTableData.innerHTML = `<div class="p-4 text-red-600 text-sm">${err.message || 'Erro ao carregar dados.'}</div>`; });
    }

    function renderizarTabela(data) {
        const rows = data.rows || [];
        const columns = (data.columns || []).map(col => col.Field);
        if (!columns.length) { if (dbTableData) dbTableData.innerHTML = '<div class="p-4 text-sm text-gray-500">Tabela vazia.</div>'; return; }
        let html = '<div class="overflow-auto"><table class="min-w-full text-left text-xs">';
        html += '<thead><tr>';
        columns.forEach(col => { html += `<th class="px-3 py-2 border-b bg-gray-100">${col}</th>`; });
        html += '</tr></thead><tbody>';
        if (!rows.length) {
            html += `<tr><td colspan="${columns.length}" class="px-3 py-2 text-center text-gray-400">Sem registros</td></tr>`;
        } else {
            rows.forEach(row => { html += '<tr>'; columns.forEach(col => { const value = row[col] ?? ''; html += `<td class="px-3 py-2 border-b">${value}</td>`; }); html += '</tr>'; });
        }
        html += '</tbody></table></div>';
        if (dbTableData) dbTableData.innerHTML = html;
        // Populate drop table select
        const dropSelect = document.getElementById('drop-table-select');
        if (dropSelect) {
            dropSelect.innerHTML = '';
            (data.__tablesList || []).forEach(t => { const opt = document.createElement('option'); opt.value = t; opt.textContent = t; dropSelect.appendChild(opt); });
        }
        // Store current columns for insert form generation
        window.currentTableColumns = data.columns || [];
        // detect primary key column
        window.currentTablePK = null;
        if (data.columns && data.columns.length) {
            for (let i=0;i<data.columns.length;i++) {
                const c = data.columns[i];
                if (c.Key === 'PRI' || c.Key === 'pri') { window.currentTablePK = c.Field; break; }
            }
            if (!window.currentTablePK) window.currentTablePK = data.columns[0].Field;
        }
        if (typeof populateInsertForm === 'function') populateInsertForm(window.currentTableColumns);
        if (typeof populateModifyPanel === 'function') populateModifyPanel(window.currentTableColumns);
        // also populate modify data list if panel open
        if (typeof populateModifyData === 'function') populateModifyData(window.currentTableColumns, rows);
    }

    function executarSql() {
        if (!sqlInput) return;
        const sql = sqlInput.value.trim();
        if (!sql) { if (sqlResult) sqlResult.textContent = 'Informe um comando SQL.'; return; }
        fetch(`/sistemas/${sistemaId}/db/sql`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ sql })
        })
            .then(resp => resp.json())
            .then(data => {
                if (sqlResult) sqlResult.textContent = JSON.stringify(data, null, 2);
                if (tabelaSelecionada) selecionarTabela(tabelaSelecionada); else carregarTabelas();
            })
            .catch(err => { if (sqlResult) sqlResult.textContent = err.message || 'Erro ao executar SQL'; });
    }

    function abrirAdminer() {
        // kept for compatibility
    }
    

    if (btnOpenAdminer) btnOpenAdminer.addEventListener('click', function(e) { e.preventDefault(); abrirAdminer(); });

    // Toggle panels
    const btnCreateToggle = document.getElementById('btn-create-table-toggle');
    const btnDropToggle = document.getElementById('btn-drop-table-toggle');
    const btnModifyToggle = document.getElementById('btn-modify-table-toggle');
    const btnModifyDataToggle = document.getElementById('btn-modify-data-toggle');
    const btnInsertToggle = document.getElementById('btn-insert-row-toggle');
    const panelCreate = document.getElementById('create-table-panel');
    const panelDrop = document.getElementById('drop-table-panel');
    const panelModify = document.getElementById('modify-table-panel');
    const panelModifyData = document.getElementById('modify-data-panel');
    const panelInsert = document.getElementById('insert-row-panel');
    function hideAllPanels(){ [panelCreate, panelDrop, panelModify, panelModifyData, panelInsert].forEach(p=>p && p.classList.add('hidden')); }
    if (btnCreateToggle) btnCreateToggle.addEventListener('click', ()=>{ hideAllPanels(); panelCreate.classList.toggle('hidden'); });
    if (btnDropToggle) btnDropToggle.addEventListener('click', ()=>{ hideAllPanels(); panelDrop.classList.toggle('hidden'); if (!panelDrop.classList.contains('hidden')) { // always refresh list when opening drop panel to keep it in sync
        carregarTabelas(); const dropSelect = document.getElementById('drop-table-select'); if (dropSelect) try { dropSelect.focus(); } catch(e){} } });
    if (btnModifyToggle) btnModifyToggle.addEventListener('click', ()=>{ hideAllPanels(); panelModify.classList.toggle('hidden'); if (typeof populateModifyPanel === 'function') populateModifyPanel(window.currentTableColumns || []); });
    if (btnModifyDataToggle) btnModifyDataToggle.addEventListener('click', ()=>{ hideAllPanels(); panelModifyData.classList.toggle('hidden'); if (!panelModifyData.classList.contains('hidden') && tabelaSelecionada) loadModifyData(tabelaSelecionada); });
    if (btnInsertToggle) btnInsertToggle.addEventListener('click', ()=>{ hideAllPanels(); panelInsert.classList.toggle('hidden'); });

        // When user changes drop select, jump to that table in the list
        const dropSelectElem = document.getElementById('drop-table-select');
        if (dropSelectElem) dropSelectElem.addEventListener('change', function(){ const val=this.value; if (val) { // mark in left list
            const li = dbTableList ? dbTableList.querySelector(`li[data-table="${val}"]`) : null; if (li) { dbTableList.querySelectorAll('li').forEach(n=>n.classList.remove('bg-indigo-100','font-semibold','active')); li.classList.add('bg-indigo-100','font-semibold','active'); } selecionarTabela(val); } });

    // Create table
    const btnCreateTable = document.getElementById('btn-create-table');
    if (btnCreateTable) btnCreateTable.addEventListener('click', function(){
        const sql = document.getElementById('create-table-sql').value.trim();
        const resultEl = document.getElementById('create-table-result');
        resultEl.textContent = 'Executando...';
        fetch(`/sistemas/${sistemaId}/db/table/create`, { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({ sql }) })
            .then(r=>r.json()).then(j=>{ if (j.ok) { resultEl.textContent = 'Tabela criada com sucesso.'; carregarTabelas(); } else { resultEl.textContent = j.error || JSON.stringify(j); } })
            .catch(e=> resultEl.textContent = e.message);
    });

    // Create table UI helpers
    const addColBtn = document.getElementById('add-column-btn');
    const colsTableBody = document.querySelector('#create-columns-table tbody');
    function addColumnRow(col={name:'', type:'VARCHAR', size:'255', nullable:false, default:'', pk:false, ai:false}){
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-2 py-1"><input class="border rounded px-1 py-1 w-full" name="col_name" value="${col.name}"></td>
            <td class="px-2 py-1">
                <select name="col_type" class="border rounded px-1 py-1">
                    <option>VARCHAR</option><option>INT</option><option>TEXT</option><option>DATETIME</option><option>DATE</option><option>FLOAT</option><option>DECIMAL</option><option>BOOLEAN</option>
                </select>
            </td>
            <td class="px-2 py-1"><input name="col_size" class="border rounded px-1 py-1 w-24" value="${col.size}"></td>
            <td class="px-2 py-1 text-center"><input type="checkbox" name="col_null" ${col.nullable ? 'checked' : ''}></td>
            <td class="px-2 py-1"><input name="col_default" class="border rounded px-1 py-1 w-full" value="${col.default}"></td>
            <td class="px-2 py-1 text-center"><input type="radio" name="pk_radio" ${col.pk ? 'checked' : ''}></td>
            <td class="px-2 py-1 text-center"><input type="checkbox" name="col_ai" ${col.ai ? 'checked' : ''}></td>
            <td class="px-2 py-1 text-center"><button class="remove-col-btn px-2 py-1 text-sm bg-red-100 rounded">Remover</button></td>
        `;
        // set selected type
        const sel = tr.querySelector('select[name="col_type"]'); sel.value = col.type || 'VARCHAR';
        // provide sensible default sizes per type when user changes type
        function defaultSizeForType(t) {
            const tp = (t||'').toString().toUpperCase();
            if (tp === 'VARCHAR' || tp === 'CHAR') return '255';
            if (tp === 'INT' || tp === 'BIGINT' || tp === 'SMALLINT' || tp === 'TINYINT') return '11';
            if (tp === 'DECIMAL') return '10,2';
            if (tp === 'FLOAT' || tp === 'DOUBLE') return '';
            if (tp === 'BOOLEAN' || tp === 'BOOL') return '';
            return '';
        }
        const sizeInput = tr.querySelector('input[name="col_size"]');
        sel.addEventListener('change', ()=>{
            try {
                const def = defaultSizeForType(sel.value);
                if (sizeInput && (sizeInput.value === '' || sizeInput.value === null)) {
                    sizeInput.value = def;
                } else if (sizeInput && def !== '' && !sizeInput.value) {
                    sizeInput.value = def;
                }
            } catch(e){}
        });
        // initialize size if not provided
        try { if (sizeInput && (!sizeInput.value || sizeInput.value === '')) sizeInput.value = defaultSizeForType(sel.value); } catch(e){}
        colsTableBody.appendChild(tr);
        tr.querySelector('.remove-col-btn').addEventListener('click', (e)=>{ e.preventDefault(); tr.remove(); });
        // clicking PK radio must unset others
        tr.querySelector('input[name="pk_radio"]').addEventListener('change', ()=>{ colsTableBody.querySelectorAll('input[name="pk_radio"]').forEach(r=>{ if (r !== tr.querySelector('input[name="pk_radio"]')) r.checked = false; }); });
    }
    if (addColBtn) addColBtn.addEventListener('click', function(e){ e.preventDefault(); addColumnRow(); });

    // Generate CREATE TABLE SQL from columns
    const genBtn = document.getElementById('generate-create-sql');
    if (genBtn) genBtn.addEventListener('click', function(e){
        e.preventDefault();
        const tname = document.getElementById('create-table-name').value.trim();
        if (!tname) { document.getElementById('create-table-result').textContent = 'Informe o nome da tabela.'; return; }
        const engine = document.getElementById('create-table-engine').value || 'InnoDB';
        const rows = Array.from(colsTableBody.querySelectorAll('tr'));
        if (!rows.length) { document.getElementById('create-table-result').textContent = 'Adicione ao menos uma coluna.'; return; }
        const defs = [];
        let pk = null;
        rows.forEach(r=>{
            const name = r.querySelector('input[name="col_name"]').value.trim();
            const type = r.querySelector('select[name="col_type"]').value;
            const size = r.querySelector('input[name="col_size"]').value.trim();
            const nullable = r.querySelector('input[name="col_null"]').checked;
            const def = r.querySelector('input[name="col_default"]').value;
            const ai = r.querySelector('input[name="col_ai"]').checked;
            const pkRadio = r.querySelector('input[name="pk_radio"]').checked;
            if (!name) return;
            let typeDef = type;
            if (['VARCHAR','CHAR'].includes(type.toUpperCase()) && size) typeDef += `(${size})`;
            if (type.toUpperCase() === 'DECIMAL' && size) typeDef += `(${size})`;
            if (type.toUpperCase() === 'INT' && size) typeDef += `(${size})`;
            let colDef = `\`${name}\` ${typeDef}`;
            if (ai) colDef += ' AUTO_INCREMENT';
            colDef += nullable ? ' NULL' : ' NOT NULL';
            if (def !== '') colDef += ` DEFAULT '${def}'`;
            defs.push(colDef);
            if (pkRadio) pk = name;
        });
        if (pk) defs.push(`PRIMARY KEY (\`${pk}\`)`);
        const sql = `CREATE TABLE \`${tname}\` (\n  ${defs.join(',\n  ')}\n) ENGINE=${engine} DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;`;
        document.getElementById('create-table-sql').value = sql;
    });

    // Drop table
    const btnDropTable = document.getElementById('btn-drop-table');
    if (btnDropTable) btnDropTable.addEventListener('click', function(){
        const sel = document.getElementById('drop-table-select');
        const table = sel ? sel.value : tabelaSelecionada;
        const resultEl = document.getElementById('drop-table-result');
        if (!table) { resultEl.textContent = 'Selecione uma tabela.'; return; }
        if (!confirm(`Confirma exclusão da tabela "${table}"? Esta operação é irreversível.`)) return;
        resultEl.textContent = 'Executando...';
        fetch(`/sistemas/${sistemaId}/db/table/drop`, { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({ table: table }) })
            .then(r=>r.json()).then(j=>{ if (j.ok) { resultEl.textContent = 'Tabela removida.'; carregarTabelas(); } else { resultEl.textContent = j.error || JSON.stringify(j); } })
            .catch(e=> resultEl.textContent = e.message);
    });

    // Insert row
    const btnInsertRow = document.getElementById('btn-insert-row');
    // Build insert form from current table columns
    function populateInsertForm(columns) {
        const container = document.getElementById('insert-form-container');
        if (!container) return;
        if (!columns || !columns.length) {
            container.innerHTML = '<div class="text-sm text-gray-500">Nenhuma coluna disponível para esta tabela.</div>';
            return;
        }
        container.innerHTML = '';
        const form = document.createElement('div');
        form.className = 'grid grid-cols-1 md:grid-cols-2 gap-2';
        columns.forEach(col => {
            const name = col.Field;
            const rawType = col.Type || '';
            const type = rawType.toLowerCase();
            const nullable = (col.Null && col.Null.toUpperCase() === 'YES');
            const extra = (col.Extra || '').toLowerCase();
            // Skip auto increment primary keys from inputs
            if (extra.includes('auto_increment')) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'flex flex-col';
            const label = document.createElement('label'); label.className = 'text-xs text-gray-700 flex items-baseline justify-between';
            const left = document.createElement('span'); left.textContent = name + (nullable ? ' • nullable' : '');
            const right = document.createElement('span'); right.className = 'text-xs text-gray-400 ml-2'; right.textContent = rawType;
            label.appendChild(left); label.appendChild(right);

            // Detect ENUM options
            let enumMatch = rawType.match(/^enum\((.*)\)$/i);
            let input;
            if (enumMatch) {
                const opts = enumMatch[1].split(/,(?=(?:[^']*'[^']*')*[^']*$)/).map(s => s.trim().replace(/^'|'$/g, ''));
                input = document.createElement('select');
                opts.forEach(o => { const op = document.createElement('option'); op.value = o; op.textContent = o; input.appendChild(op); });
            } else if (type.startsWith('tinyint(1)') || /^tinyint\(1\)/.test(type) || type === 'boolean' || type === 'bool') {
                input = document.createElement('input'); input.type = 'checkbox';
            } else if (type.startsWith('int') || type.startsWith('bigint') || type.startsWith('smallint')) {
                input = document.createElement('input'); input.type = 'number'; input.step = '1';
            } else if (type.startsWith('decimal') || type.startsWith('float') || type.startsWith('double')) {
                input = document.createElement('input'); input.type = 'number'; input.step = 'any';
            } else if (type.startsWith('date') && !type.startsWith('datetime')) {
                input = document.createElement('input'); input.type = 'date';
            } else if (type.startsWith('datetime') || type.startsWith('timestamp')) {
                input = document.createElement('input'); input.type = 'datetime-local';
            } else if (type.startsWith('time')) {
                input = document.createElement('input'); input.type = 'time';
            } else if (type.includes('text') || type.includes('char')) {
                input = document.createElement('input'); input.type = 'text';
            } else if (type.includes('json')) {
                input = document.createElement('textarea'); input.rows = 3;
            } else {
                input = document.createElement('input'); input.type = 'text';
            }

            input.className = 'border rounded px-2 py-1 text-sm';
            input.setAttribute('data-col-name', name);
            // placeholder / default
            if (col.Default !== null && col.Default !== undefined) {
                if (input.tagName.toLowerCase() === 'input' || input.tagName.toLowerCase() === 'textarea') input.placeholder = String(col.Default);
                else if (input.tagName.toLowerCase() === 'select') {
                    try { input.value = String(col.Default); } catch(e){}
                }
            }
            // required when NOT NULL and no default
            if (!nullable && (col.Default === null || col.Default === undefined || col.Default === '')) {
                if (input.type !== 'checkbox' && input.tagName.toLowerCase() !== 'select') input.required = true;
            }

            wrapper.appendChild(label);
            wrapper.appendChild(input);
            // help text for default
            if (col.Default !== null && col.Default !== undefined && String(col.Default) !== '') {
                const help = document.createElement('div'); help.className = 'text-xs text-gray-400'; help.textContent = 'Default: ' + String(col.Default); wrapper.appendChild(help);
            }

            form.appendChild(wrapper);
        });
        container.appendChild(form);
    }

    if (btnInsertRow) btnInsertRow.addEventListener('click', function(){
        const selLi = document.querySelector('#db-table-list li.active');
        const table = selLi ? selLi.dataset.table : null;
        const resultEl = document.getElementById('insert-row-result');
        if (!table) { resultEl.textContent = 'Selecione uma tabela à esquerda.'; return; }
        // collect form values with type-aware conversions
        const container = document.getElementById('insert-form-container');
        const inputs = container ? container.querySelectorAll('[data-col-name]') : [];
        const data = {};
        inputs.forEach(inp => {
            const col = inp.getAttribute('data-col-name');
            if (!col) return;
            if (inp.type === 'checkbox') {
                data[col] = inp.checked ? 1 : 0;
            } else if (inp.tagName.toLowerCase() === 'select') {
                data[col] = inp.value === '' ? null : inp.value;
            } else if (inp.type === 'number') {
                const v = inp.value;
                data[col] = (v === '' ? null : (inp.step && inp.step !== '1' ? parseFloat(v) : parseInt(v, 10)));
            } else if (inp.type === 'datetime-local') {
                if (inp.value === '') data[col] = null; else { data[col] = inp.value.replace('T', ' ') + (inp.value.length === 16 ? ':00' : ''); }
            } else if (inp.tagName.toLowerCase() === 'textarea') {
                data[col] = inp.value === '' ? null : inp.value;
            } else {
                data[col] = inp.value === '' ? null : inp.value;
            }
        });
        resultEl.textContent = 'Executando...';
        fetch(`/sistemas/${sistemaId}/db/table/${table}/insert`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ data })
        })
            .then(async r => {
                const text = await r.text();
                try { return JSON.parse(text); } catch (err) { return { ok: false, raw: text, error: text }; }
            })
            .then(j => {
                if (j.ok) {
                    resultEl.textContent = 'Inserido. ID: ' + (j.id || 'n/a');
                    if (typeof selecionarTabela === 'function') selecionarTabela(table);
                } else {
                    resultEl.textContent = j.error || j.raw || JSON.stringify(j);
                }
            })
            .catch(e => resultEl.textContent = e.message);
    });

    // Modify table helpers
    const addNewColBtn = document.getElementById('add-new-column-btn');
    const modifyColsBody = document.querySelector('#modify-columns-table tbody');
    function addModifyRow(col){
        // col may be undefined for new columns
        const oldName = col ? (col.Field || '') : '';
        const typeRaw = col ? (col.Type || '') : 'VARCHAR(255)';
        // split type and size
        let t = typeRaw; let size = '';
        const m = typeRaw.match(/([a-zA-Z]+)\(([^)]+)\)/);
        if (m) { t = m[1]; size = m[2]; }
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-2 py-1"><span class="text-xs text-gray-600">${oldName}</span></td>
            <td class="px-2 py-1"><input class="border rounded px-1 py-1 w-full" name="new_name" value="${oldName}"></td>
            <td class="px-2 py-1"><select name="mod_type" class="border rounded px-1 py-1"><option>VARCHAR</option><option>CHAR</option><option>INT</option><option>BIGINT</option><option>TEXT</option><option>DATETIME</option><option>DATE</option><option>FLOAT</option><option>DECIMAL</option><option>BOOLEAN</option></select></td>
            <td class="px-2 py-1"><input name="mod_size" class="border rounded px-1 py-1 w-24" value="${size}"></td>
            <td class="px-2 py-1 text-center"><input type="checkbox" name="mod_null" ${col && col.Null === 'YES' ? 'checked' : ''}></td>
            <td class="px-2 py-1"><input name="mod_default" class="border rounded px-1 py-1 w-full" value="${col && col.Default !== null ? col.Default : ''}"></td>
            <td class="px-2 py-1 text-center"><input type="checkbox" name="mod_ai" ${col && (col.Extra || '').toLowerCase().includes('auto_increment') ? 'checked' : ''}></td>
            <td class="px-2 py-1 text-center"><button class="remove-modify-row px-2 py-1 text-sm bg-red-100 rounded">Remover</button></td>
        `;
        const sel = tr.querySelector('select[name="mod_type"]'); sel.value = (t||'VARCHAR').toUpperCase();
        modifyColsBody.appendChild(tr);
        tr.querySelector('.remove-modify-row').addEventListener('click', (e)=>{ e.preventDefault(); tr.remove(); });
    }
    if (addNewColBtn) addNewColBtn.addEventListener('click', function(e){ e.preventDefault(); addModifyRow(); });

    function populateModifyPanel(columns) {
        if (!modifyColsBody) return;
        modifyColsBody.innerHTML = '';
        if (!columns || !columns.length) { modifyColsBody.innerHTML = '<tr><td class="px-2 py-2 text-sm text-gray-500" colspan="8">Nenhuma coluna encontrada.</td></tr>'; return; }
        columns.forEach(c => addModifyRow(c));
    }

    const genAlterBtn = document.getElementById('generate-alter-sql');
    if (genAlterBtn) genAlterBtn.addEventListener('click', function(e){
        e.preventDefault();
        const table = tabelaSelecionada;
        if (!table) { document.getElementById('modify-table-result').textContent = 'Selecione uma tabela.'; return; }
        const rows = Array.from(modifyColsBody.querySelectorAll('tr'));
        const parts = [];
        rows.forEach(r => {
            const current = r.querySelector('td span') ? r.querySelector('td span').textContent : '';
            const newName = r.querySelector('input[name="new_name"]').value.trim();
            const type = r.querySelector('select[name="mod_type"]').value;
            const size = r.querySelector('input[name="mod_size"]').value.trim();
            const nullable = r.querySelector('input[name="mod_null"]').checked;
            const def = r.querySelector('input[name="mod_default"]').value;
            const ai = r.querySelector('input[name="mod_ai"]').checked;
            // skip rows that are empty (no new name and no current)
            if (!current && !newName) return;
            // build typeDef
            let typeDef = type;
            if (size && ['VARCHAR','CHAR','INT','DECIMAL'].includes(type.toUpperCase())) typeDef += `(${size})`;
            if (ai) typeDef += ' AUTO_INCREMENT';
            typeDef += nullable ? ' NULL' : ' NOT NULL';
            if (def !== '') typeDef += ` DEFAULT '${def}'`;
            if (!current) {
                // add new column
                parts.push(`ADD COLUMN \`${newName}\` ${typeDef}`);
            } else {
                if (newName !== current) {
                    // CHANGE old new
                    parts.push(`CHANGE \`${current}\` \`${newName}\` ${typeDef}`);
                } else {
                    // MODIFY
                    parts.push(`MODIFY \`${current}\` ${typeDef}`);
                }
            }
        });
        if (!parts.length) { document.getElementById('modify-table-result').textContent = 'Nenhuma alteração detectada.'; return; }
        const sql = `ALTER TABLE \`${table}\`\n  ${parts.join(',\n  ')};`;
        document.getElementById('modify-table-sql').value = sql;
    });

    const execAlterBtn = document.getElementById('btn-execute-alter');
    if (execAlterBtn) execAlterBtn.addEventListener('click', function(e){
        e.preventDefault();
        const table = tabelaSelecionada;
        const sql = document.getElementById('modify-table-sql').value.trim();
        const resultEl = document.getElementById('modify-table-result');
        if (!table) { resultEl.textContent = 'Selecione uma tabela.'; return; }
        if (!sql) { resultEl.textContent = 'Gere o SQL de ALTER primeiro.'; return; }
        resultEl.textContent = 'Executando...';
        fetch(`/sistemas/${sistemaId}/db/table/${table}/alter`, { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({ sql }) })
            .then(r=>r.json()).then(j=>{ if (j.ok) { resultEl.textContent = 'ALTER executado com sucesso.'; carregarTabelas(); if (typeof selecionarTabela === 'function') selecionarTabela(table); } else { resultEl.textContent = j.error || JSON.stringify(j); } })
            .catch(e=> resultEl.textContent = e.message);
    });

    // Modify Data: load and render rows with edit/delete
    function loadModifyData(table) {
        if (!table) return;
        const container = document.getElementById('modify-data-container');
        const editor = document.getElementById('modify-data-editor');
        const resultEl = document.getElementById('modify-data-result');
        if (container) container.innerHTML = 'Carregando...';
        if (editor) editor.classList.add('hidden');
        fetch(`/sistemas/${sistemaId}/db/tables/${table}`, { headers: {'X-Requested-With':'XMLHttpRequest'} })
            .then(r=>r.json())
            .then(data => {
                const rows = data.rows || [];
                const cols = data.columns || [];
                if (!container) return;
                if (!rows.length) { container.innerHTML = '<div class="text-sm text-gray-500">Sem registros</div>'; return; }
                // build simple list with edit buttons
                container.innerHTML = '';
                rows.forEach(row => {
                    const pk = window.currentTablePK || Object.keys(row)[0];
                    const id = row[pk] !== undefined ? row[pk] : (row[Object.keys(row)[0]] || null);
                    const rowDiv = document.createElement('div'); rowDiv.className = 'flex items-center justify-between gap-2 p-2 border-b';
                    const left = document.createElement('div'); left.className = 'text-xs text-gray-700';
                    // show a summarized view
                    const parts = Object.keys(row).slice(0,4).map(k=>`${k}: ${row[k]}`);
                    left.textContent = parts.join(' | ');
                    const actions = document.createElement('div'); actions.className = 'flex gap-2';
                    const editBtn = document.createElement('button'); editBtn.className = 'px-2 py-1 text-xs bg-indigo-100 text-indigo-700 rounded'; editBtn.textContent = 'Editar';
                    const delBtn = document.createElement('button'); delBtn.className = 'px-2 py-1 text-xs bg-red-100 text-red-700 rounded'; delBtn.textContent = 'Excluir';
                    actions.appendChild(editBtn); actions.appendChild(delBtn);
                    rowDiv.appendChild(left); rowDiv.appendChild(actions);
                    container.appendChild(rowDiv);

                    editBtn.addEventListener('click', ()=> openRowEditor(id, row, cols));
                    delBtn.addEventListener('click', ()=> confirmDeleteRow(table, id));
                });
            })
            .catch(err => { if (container) container.innerHTML = `<div class="text-sm text-red-500">Erro: ${err.message || err}</div>`; });
    }

    let editingRow = null;
    function openRowEditor(id, row, cols) {
        editingRow = { id, row, cols };
        const editor = document.getElementById('modify-data-editor');
        const form = document.getElementById('modify-data-form');
        const resultEl = document.getElementById('modify-data-result');
        form.innerHTML = '';
        resultEl.textContent = '';
        cols.forEach(col => {
            const name = col.Field;
            const rawType = col.Type || '';
            const val = row[name] === null ? '' : String(row[name]);
            if ((col.Extra || '').toLowerCase().includes('auto_increment')) return; // skip AI
            const wrapper = document.createElement('div'); wrapper.className = 'flex flex-col';
            const label = document.createElement('label'); label.className = 'text-xs text-gray-700'; label.textContent = `${name} (${rawType})`;
            let input;
            if (/^enum\(/i.test(rawType)) {
                const match = rawType.match(/^enum\((.*)\)$/i); const opts = match ? match[1].split(/,(?=(?:[^']*'[^']*')*[^']*$)/).map(s=>s.trim().replace(/^'|'$/g,'')) : [];
                input = document.createElement('select'); opts.forEach(o=>{ const op=document.createElement('option'); op.value=o; op.text=o; input.appendChild(op); }); input.value = val;
            } else if (rawType.toLowerCase().startsWith('int')) { input = document.createElement('input'); input.type = 'number'; input.value = val; }
            else if (/^(decimal|float|double)/i.test(rawType)) { input = document.createElement('input'); input.type='number'; input.step='any'; input.value=val; }
            else if (/^(datetime|timestamp)/i.test(rawType)) { input = document.createElement('input'); input.type='datetime-local'; input.value = val ? val.replace(' ', 'T').slice(0,19) : ''; }
            else if (/^date/i.test(rawType)) { input = document.createElement('input'); input.type='date'; input.value = val ? val.slice(0,10) : ''; }
            else if (/text|json/i.test(rawType)) { input = document.createElement('textarea'); input.rows=3; input.value=val; }
            else { input = document.createElement('input'); input.type='text'; input.value = val; }
            input.className = 'border rounded px-2 py-1 text-sm'; input.setAttribute('data-col-name', name);
            wrapper.appendChild(label); wrapper.appendChild(input); form.appendChild(wrapper);
        });
        // show editor
        if (editor) editor.classList.remove('hidden');
    }

    function confirmDeleteRow(table, id) {
        if (!confirm('Confirma exclusão deste registro?')) return;
        const resultEl = document.getElementById('modify-data-result'); resultEl.textContent = 'Excluindo...';
        fetch(`/sistemas/${sistemaId}/db/table/${table}/delete/${encodeURIComponent(id)}`, { method: 'DELETE', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content} })
            .then(r=>r.json()).then(j=>{ if (j.ok) { resultEl.textContent = 'Registro excluído.'; loadModifyData(table); if (typeof selecionarTabela === 'function') selecionarTabela(table); } else { resultEl.textContent = j.error || JSON.stringify(j); } })
            .catch(e=> resultEl.textContent = e.message);
    }

    // save edited row
    const btnSaveRow = document.getElementById('btn-save-row');
    const btnCancelEdit = document.getElementById('btn-cancel-edit');
    const btnDeleteRow = document.getElementById('btn-delete-row');
    if (btnCancelEdit) btnCancelEdit.addEventListener('click', function(){ document.getElementById('modify-data-editor').classList.add('hidden'); editingRow = null; });
    if (btnSaveRow) btnSaveRow.addEventListener('click', function(){
        if (!editingRow) return; const table = tabelaSelecionada; const id = editingRow.id; const form = document.getElementById('modify-data-form'); const inputs = form.querySelectorAll('[data-col-name]'); const data = {};
        inputs.forEach(inp => { const col = inp.getAttribute('data-col-name'); if (!col) return; if (inp.type === 'number') { data[col] = inp.value === '' ? null : (inp.step && inp.step !== '1' ? parseFloat(inp.value) : parseInt(inp.value,10)); } else if (inp.tagName.toLowerCase()==='textarea') data[col]= inp.value === '' ? null : inp.value; else if (inp.type==='datetime-local') data[col] = inp.value === '' ? null : inp.value.replace('T',' ') + (inp.value.length===16?':00':''); else data[col] = inp.type==='checkbox' ? (inp.checked?1:0) : (inp.value === '' ? null : inp.value); });
        const resultEl = document.getElementById('modify-data-result'); resultEl.textContent = 'Salvando...';
        fetch(`/sistemas/${sistemaId}/db/table/${table}/update/${encodeURIComponent(id)}`, { method: 'PUT', headers: {'Content-Type':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({ data }) })
            .then(r=>r.json()).then(j=>{ if (j.ok) { resultEl.textContent = 'Registro atualizado.'; document.getElementById('modify-data-editor').classList.add('hidden'); editingRow=null; loadModifyData(table); if (typeof selecionarTabela === 'function') selecionarTabela(table); } else { resultEl.textContent = j.error || JSON.stringify(j); } })
            .catch(e=> resultEl.textContent = e.message);
    });
    if (btnDeleteRow) btnDeleteRow.addEventListener('click', function(){ if (!editingRow) return; const table = tabelaSelecionada; confirmDeleteRow(table, editingRow.id); });

    if (document.getElementById('btn-executar-sql')) document.getElementById('btn-executar-sql').onclick = executarSql;
    if (btnRecarregarTabelas) btnRecarregarTabelas.onclick = carregarTabelas;
    if (document.getElementById('btn-sql-template')) document.getElementById('btn-sql-template').onclick = () => {
        if (sqlInput) sqlInput.value = tabelaSelecionada ? `SELECT * FROM \`${tabelaSelecionada}\` LIMIT 10;` : 'SHOW TABLES;';
    };
    }
    catch (e) {
        console.error('Erro no script sistemas.show:', e);
    }

});
</script>
@endsection 