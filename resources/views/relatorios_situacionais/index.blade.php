@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <input id="pesquisa-relatorios" type="text" placeholder="Pesquisar relatórios..." class="border rounded-lg px-4 py-2 w-1/3 focus:ring-2 focus:ring-blue-400" />
            <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700" onclick="abrirModalNovo()">Novo Relatório</button>
        </div>
    </div>
</div>
<!-- Modal de cadastro -->
<div id="modal-novo" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded shadow-lg w-full max-w-lg">
        <form id="form-novo-conteudo" method="POST" action="{{ route('relatorios-situacionais.store') }}" enctype="multipart/form-data">
            @csrf
            <h2 class="text-xl font-bold mb-4">Novo Relatório</h2>
            <input type="text" name="titulo" placeholder="Título" class="w-full mb-2 border p-2 rounded" required>
            <input type="text" name="descricao" placeholder="Descrição" class="w-full mb-2 border p-2 rounded" required>
            <input type="text" name="autor" placeholder="Autor" class="w-full mb-2 border p-2 rounded" required>
            <input type="date" name="data" class="w-full mb-2 border p-2 rounded" required>
            <input type="text" name="tag" placeholder="Tag (opcional)" class="w-full mb-2 border p-2 rounded">
            <input type="hidden" name="capa_stream_path" id="relatorio-capa-stream-path">
            <input type="file" name="capa" class="w-full mb-2 border p-2 rounded" required>
            <p id="relatorio-upload-status" class="text-sm text-gray-600 mb-2 hidden"></p>
            <textarea name="conteudo" placeholder="Conteúdo" class="w-full mb-2 border p-2 rounded" rows="4" required></textarea>
            <div class="flex justify-end">
                <button type="button" onclick="fecharModalNovo()" class="mr-2 px-4 py-2 bg-gray-300 rounded">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Salvar</button>
            </div>
        </form>
    </div>
</div>
<script>
function abrirModalNovo() {
    document.getElementById('modal-novo').classList.remove('hidden');
    resetRelatorioUploadState();
}
function fecharModalNovo() {
    document.getElementById('modal-novo').classList.add('hidden');
}

function resetRelatorioUploadState() {
    const form = document.getElementById('form-novo-conteudo');
    const statusEl = document.getElementById('relatorio-upload-status');
    const hiddenInput = document.getElementById('relatorio-capa-stream-path');
    if (form) {
        delete form.dataset.streamingReady;
    }
    if (statusEl) {
        statusEl.classList.add('hidden');
        statusEl.textContent = '';
    }
    if (hiddenInput) {
        hiddenInput.value = '';
    }
    const fileInput = form?.querySelector('input[name="capa"]');
    if (fileInput) {
        fileInput.value = '';
    }
}
</script>

@if(session('success'))
    <div class="alert alert-success" id="alert-success">{{ session('success') }}</div>
    <script>
        setTimeout(function() {
            var alert = document.getElementById('alert-success');
            if(alert) alert.style.display = 'none';
        }, 5000);
    </script>
@endif

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div id="relatorios-area"></div>
    <div id="pagination-area" class="mt-6 flex justify-center gap-2"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const style = document.createElement('style');
    style.textContent = `
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(99, 102, 241, 0.4);
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
    `;
    document.head.appendChild(style);
});

let paginaAtual = 1;

function carregarRelatorios(page = 1, search = '') {
    let url = '{{ route("relatorios-situacionais.index") }}?ajax=1&page=' + page;
    if (search.trim()) {
        url += '&search=' + encodeURIComponent(search);
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            renderizarRelatoriosAgrupados(data.grouped || []);
            renderizarPaginacaoRelatorios(data, search);
            paginaAtual = data.current_page;
        })
        .catch(error => {
            console.error('Erro ao carregar relatórios:', error);
        });
}

function renderizarRelatoriosAgrupados(grupos) {
    const container = document.getElementById('relatorios-area');
    if (!grupos.length) {
        container.innerHTML = '<div class="text-center text-gray-500 py-8">Nenhum relatório encontrado.</div>';
        return;
    }

    const html = grupos.map(grupo => `
        <div class="border border-gray-200 rounded-2xl mb-8 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-b border-gray-200">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Tag</p>
                    <h2 class="text-2xl font-bold text-gray-900">${grupo.tag}</h2>
                </div>
                <span class="text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-full px-3 py-1">
                    ${grupo.total} ${grupo.total === 1 ? 'relatório' : 'relatórios'}
                </span>
            </div>
            <div class="p-6 bg-white">
                <div class="bg-gray-50 rounded-2xl p-4 overflow-x-auto custom-scrollbar">
                    <div class="flex gap-4 min-h-[230px]">
                        ${grupo.relatorios.map(renderCardRelatorio).join('')}
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    container.innerHTML = html;
}

function renderCardRelatorio(post) {
    const dataFormatada = new Date(post.data).toLocaleDateString('pt-BR');
    const capa = post.capa ? `/storage/${post.capa}` : 'https://via.placeholder.com/400x200?text=Relatório';

    return `
        <a href="/relatorios-situacionais/${post.id}" class="w-72 flex-shrink-0 bg-white rounded-xl shadow hover:shadow-md transition border border-gray-100 overflow-hidden">
            <div class="h-40 bg-gray-100">
                <img src="${capa}" alt="Capa" class="w-full h-full object-cover">
            </div>
            <div class="p-4 space-y-2">
                <div class="flex items-center justify-between text-xs uppercase tracking-wide text-gray-400">
                    <span>${post.tag || 'Sem tag'}</span>
                    <span>${dataFormatada}</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900">${post.titulo}</h3>
                <p class="text-sm text-gray-600 line-clamp-2">${post.descricao}</p>
                <div class="text-sm text-gray-500">Por ${post.autor}</div>
            </div>
        </a>
    `;
}

function renderizarPaginacaoRelatorios(data, search = '') {
    let html = '';

    if (data.prev_page_url) {
        html += `<button class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300" onclick="carregarRelatorios(${data.current_page - 1}, '${search}')">&laquo; Anterior</button>`;
    }

    html += `<span class="px-3 py-1">${data.current_page} / ${data.last_page}</span>`;

    if (data.next_page_url) {
        html += `<button class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300" onclick="carregarRelatorios(${data.current_page + 1}, '${search}')">Próxima &raquo;</button>`;
    }

    document.getElementById('pagination-area').innerHTML = html;
}

// Event listener para busca
document.getElementById('pesquisa-relatorios').addEventListener('input', function() {
    const searchTerm = this.value;
    carregarRelatorios(1, searchTerm);
});

// Carrega relatórios ao abrir a página
carregarRelatorios();

(function() {
    const form = document.getElementById('form-novo-conteudo');
    const fileInput = form?.querySelector('input[name="capa"]');
    const hiddenInput = document.getElementById('relatorio-capa-stream-path');
    const statusEl = document.getElementById('relatorio-upload-status');
    const tituloInput = form?.querySelector('input[name="titulo"]');
    let isUploading = false;

    if (!form || !fileInput || !hiddenInput) {
        return;
    }

    form.addEventListener('submit', async function(event) {
        if (form.dataset.streamingReady === 'true') {
            delete form.dataset.streamingReady;
            return;
        }

        event.preventDefault();

        if (!fileInput.files || !fileInput.files.length) {
            updateRelatorioStatus('Selecione a capa antes de salvar.', 'error');
            return;
        }

        if (!window.StreamingUpload) {
            updateRelatorioStatus('Streaming não configurado no navegador.', 'error');
            return;
        }

        if (isUploading) {
            return;
        }

        isUploading = true;
        try {
            const client = window.StreamingUpload.getDefaultClient();
            const file = fileInput.files[0];
            const finalName = buildRelatorioFileName((tituloInput?.value || 'relatorio'), file.name);

            const [result] = await client.upload([file], {
                buildRequest: () => ({
                    relativePath: finalName,
                    fileName: finalName,
                    context: 'public',
                    contextPayload: {
                        target_directory: 'capas',
                        final_name: finalName,
                        original_name: file.name,
                        preserve_name: true,
                    },
                    fileIndex: 1,
                    totalFiles: 1,
                }),
                onFileProgress: ({ percent }) => {
                    const pct = Number(percent || 0).toFixed(1);
                    updateRelatorioStatus(`Enviando capa... ${pct}%`);
                },
            });

            const contextInfo = result?.context || {};
            const finalPath = contextInfo.final_relative_path || result?.filePath || result?.relativePath;
            if (!finalPath) {
                throw new Error('O servidor não retornou o caminho final da capa.');
            }

            hiddenInput.value = finalPath.replace(/^\/+/, '');
            fileInput.value = '';
            updateRelatorioStatus('Capa enviada com sucesso!', 'success');
            form.dataset.streamingReady = 'true';
            form.submit();
        } catch (error) {
            updateRelatorioStatus(error.message || 'Erro ao enviar a capa.', 'error');
        } finally {
            isUploading = false;
        }
    });

    function buildRelatorioFileName(prefix, originalName) {
        const safePrefix = slugify(prefix || 'relatorio');
        const extension = originalName && originalName.includes('.') ? '.' + originalName.split('.').pop() : '';
        return `${safePrefix}-${Date.now()}${extension}`;
    }

    function slugify(text) {
        return (text || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-zA-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .toLowerCase() || 'relatorio';
    }

    function updateRelatorioStatus(message, type = 'info') {
        if (!statusEl) {
            if (type === 'error' && message) {
                alert(message);
            }
            return;
        }
        if (!message) {
            statusEl.classList.add('hidden');
            statusEl.textContent = '';
            return;
        }
        statusEl.classList.remove('hidden');
        const className = type === 'error' ? 'text-red-600' : type === 'success' ? 'text-green-600' : 'text-gray-600';
        statusEl.className = `text-sm ${className} mb-2`;
        statusEl.textContent = message;
    }
})();
</script>
@endsection 