@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-12">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold mb-4">Biblioteca de Livros</h2>

            <!-- Alerta para mensagens de sucesso/erro -->
            <div id="alertMessages"></div>

            <!-- Seção de Adicionar Livro -->
            <div class="mb-8">
                <button id="toggleAddBook" class="w-full flex justify-between items-center p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-all duration-200">
                    <h3 class="text-lg font-semibold">Adicionar Novo Livro</h3>
                    <svg id="toggleIcon" class="w-6 h-6 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                
                <div id="addBookForm" class="space-y-4 mt-4 hidden">
                    @csrf <!-- Manter para obter o token CSRF -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="titulo" class="block text-sm font-medium text-gray-700">Título</label>
                            <input type="text" name="titulo" id="titulo" required
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md h-10 bg-gray-50 border border-gray-300">
                            <p id="error-titulo" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>
                        <div>
                            <label for="autor" class="block text-sm font-medium text-gray-700">Autor</label>
                            <input type="text" name="autor" id="autor" required
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md h-10 bg-gray-50 border border-gray-300">
                            <p id="error-autor" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="categoria" class="block text-sm font-medium text-gray-700">Categoria</label>
                            <input type="text" name="categoria" id="categoria" required
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md h-10 bg-gray-50 border border-gray-300">
                             <p id="error-categoria" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>
                        <div>
                            <label for="genero" class="block text-sm font-medium text-gray-700">Gênero</label>
                            <input type="text" name="genero" id="genero" required
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md h-10 bg-gray-50 border border-gray-300">
                             <p id="error-genero" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="materia" class="block text-sm font-medium text-gray-700">Matéria</label>
                            <input type="text" name="materia" id="materia"
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md h-10 bg-gray-50 border border-gray-300">
                             <p id="error-materia" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>
                        <div>
                            <label for="data_publicacao" class="block text-sm font-medium text-gray-700">Data de Publicação</label>
                            <input type="date" name="data_publicacao" id="data_publicacao" required
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md h-10 bg-gray-50 border border-gray-300">
                            <p id="error-data_publicacao" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>
                    </div>
                    <div>
                        <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
                        <textarea name="descricao" id="descricao" rows="3"
                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md bg-gray-50 border border-gray-300"></textarea>
                        <p id="error-descricao" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>
                    <div>
                        <label for="arquivo" class="block text-sm font-medium text-gray-700">Arquivo PDF</label>
                        <input type="file" name="arquivo" id="arquivo" accept=".pdf" required
                            class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md h-10 bg-gray-50 border border-gray-300 p-1">
                        <p id="error-arquivo" class="text-red-500 text-xs mt-1 hidden"></p>
                        <p class="text-sm text-gray-500 mt-1">Apenas arquivos PDF são aceitos.</p>
                    </div>
                    
                    <!-- Barra de Progresso -->
                     <div id="uploadProgressContainer" class="mt-4 hidden">
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                            <div id="progressBar" class="bg-indigo-600 h-2.5 rounded-full" style="width: 0%"></div>
                        </div>
                        <p id="progressText" class="text-sm text-center text-gray-600 mt-1">0%</p>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" id="saveLivroButton"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Salvar Livro
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lista de Livros -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Livros Disponiveis</h2>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Botão Progressos de Leitura -->
                        <a href="{{ route('livros.progresso.index') }}" 
                           class="flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition-colors duration-200 shadow-sm">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            Progressos de Leitura
                        </a>
                        
                        <!-- Barra de Pesquisa -->
                        <div class="relative">
                            <input type="text" 
                                   id="searchInput" 
                                   placeholder="Pesquisar livros..." 
                                   class="w-64 px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            <button id="searchButton" 
                                    class="absolute right-0 top-0 h-full px-3 text-gray-500 hover:text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                @if($livros->isEmpty())
                    <p class="text-gray-500">Nenhum livro cadastrado.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[200px]">Nome Original</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[180px]">Título</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[150px]">Autor</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[120px]">Categoria</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[120px]">Gênero</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[120px]">Matéria</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[100px]">Data</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[100px]">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-[80px]">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($livros as $livro)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-[200px] truncate" title="{{ $livro->original_filename }}">{{ $livro->original_filename ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-[180px] truncate" title="{{ $livro->titulo }}">{{ $livro->titulo }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-[150px] truncate" title="{{ $livro->autor }}">{{ $livro->autor }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-[120px] truncate" title="{{ $livro->categoria }}">{{ $livro->categoria }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-[120px] truncate" title="{{ $livro->genero }}">{{ $livro->genero }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-[120px] truncate" title="{{ $livro->materia }}">{{ $livro->materia ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $livro->data_publicacao ? date('d/m/Y', strtotime($livro->data_publicacao)) : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{
                                                    match ($livro->status) {
                                                        'completo' => 'bg-green-100 text-green-800',
                                                        'pendente' => 'bg-gray-100 text-gray-800',
                                                        'validado' => 'bg-blue-100 text-blue-800',
                                                        'uploading' => 'bg-yellow-100 text-yellow-800',
                                                        'erro' => 'bg-red-100 text-red-800',
                                                        default => 'bg-gray-100 text-gray-800'
                                                    }
                                                }}">
                                                {{ ucfirst($livro->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex flex-col space-y-1">
                                                @if($livro->status === 'completo' && $livro->hash)
                                                    <a href="{{ route('livros.downloadByHash', $livro->hash) }}" 
                                                       class="text-indigo-600 hover:text-indigo-900 text-xs bg-indigo-50 px-2 py-1 rounded flex items-center justify-center">
                                                       <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                       </svg>
                                                       Baixar
                                                    </a>
                                                    
                                                    <a href="#" onclick="visualizarPDF('{{ $livro->hash }}')" 
                                                       class="text-green-600 hover:text-green-900 text-xs bg-green-50 px-2 py-1 rounded flex items-center justify-center">
                                                       <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                       </svg>
                                                       Ver
                                                    </a>
                                                @endif
                                                
                                                <form action="{{ route('livros.destroy', $livro->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" onclick="confirmarExclusao({{ $livro->id }})" 
                                                            class="text-red-600 hover:text-red-900 text-xs bg-red-50 px-2 py-1 rounded w-full flex items-center justify-center">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                        Excluir
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal para visualização de PDF -->
<div id="pdfModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white p-4 rounded-lg w-full max-w-6xl h-5/6 flex flex-col">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Visualizar PDF</h3>
            <button id="closePdfModal" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-1 px-3 rounded">
                X
            </button>
        </div>
        <div class="flex-grow">
            <iframe id="pdfViewer" class="w-full h-full border-0" src="" frameborder="0"></iframe>
        </div>
    </div>
</div>

<!-- Modal de Confirmação para Deletar -->
<div id="deleteModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
        <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mt-4">Confirmar Exclusão</h3>
            <p class="text-sm text-gray-500 mt-2">Tem certeza que deseja excluir este livro? Isso removerá o registro e o arquivo associado.</p>
        </div>
        <div class="mt-6 flex justify-center space-x-4">
            <button onclick="toggleDeleteModal(false)" 
                    class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancelar
            </button>
            <button id="confirmDeleteBtn"
                    class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                Sim, excluir
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Adiciona funcionalidade de toggle para o formulário
    const toggleButton = document.getElementById('toggleAddBook');
    const addBookForm = document.getElementById('addBookForm');
    const toggleIcon = document.getElementById('toggleIcon');
    
    toggleButton.addEventListener('click', function() {
        addBookForm.classList.toggle('hidden');
        toggleIcon.classList.toggle('rotate-180');
    });

    // Modal de PDF
    const pdfModal = document.getElementById('pdfModal');
    const pdfViewer = document.getElementById('pdfViewer');
    const closePdfModal = document.getElementById('closePdfModal');
    
    window.visualizarPDF = function(hash) {
        // Construir URL para visualizar o PDF
        const viewerUrl = `{{ url('/livros/view') }}/${hash}`;
        pdfViewer.src = viewerUrl;
        pdfModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Previne rolagem no fundo
    };
    
    closePdfModal.addEventListener('click', function() {
        pdfModal.classList.add('hidden');
        pdfViewer.src = '';
        document.body.style.overflow = 'auto'; // Restaura rolagem
    });
    
    // Fechar modal ao clicar fora
    pdfModal.addEventListener('click', function(event) {
        if (event.target === pdfModal) {
            pdfModal.classList.add('hidden');
            pdfViewer.src = '';
            document.body.style.overflow = 'auto';
        }
    });
    
    const saveButton = document.getElementById('saveLivroButton');
    const tituloInput = document.getElementById('titulo');
    const autorInput = document.getElementById('autor');
    const categoriaInput = document.getElementById('categoria');
    const generoInput = document.getElementById('genero');
    const materiaInput = document.getElementById('materia');
    const dataPublicacaoInput = document.getElementById('data_publicacao');
    const descricaoInput = document.getElementById('descricao');
    const arquivoInput = document.getElementById('arquivo');
    const csrfToken = document.querySelector('input[name="_token"]').value;
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const progressContainer = document.getElementById('uploadProgressContainer');
    const alertMessagesDiv = document.getElementById('alertMessages');
    // Associa o evento de clique ao botão apenas quando o DOM estiver carregado
    saveButton.addEventListener('click', handleSaveClick);

    function showAlert(message, type = 'success') {
        const alertClass = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
        alertMessagesDiv.innerHTML = `
            <div class="${alertClass} border px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">${message}</span>
            </div>
        `;
        setTimeout(() => { alertMessagesDiv.innerHTML = ''; }, 5000); // Limpa após 5 segundos
    }
    
    function clearErrors() {
        document.querySelectorAll('p[id^="error-"]').forEach(el => {
             el.classList.add('hidden');
             el.textContent = '';
        });
        document.querySelectorAll('input.border-red-500').forEach(el => {
            el.classList.remove('border-red-500');
        });
    }
    
    function displayErrors(errors) {
         clearErrors();
         for (const field in errors) {
            const errorElement = document.getElementById(`error-${field}`);
            const inputElement = document.getElementById(field);
            if (errorElement) {
                errorElement.textContent = errors[field][0]; // Mostra o primeiro erro
                errorElement.classList.remove('hidden');
            }
             if (inputElement) {
                inputElement.classList.add('border-red-500');
            }
        }
    }

    // Função que é chamada SOMENTE quando o botão é clicado
    async function handleSaveClick() {
        // Limpa erros anteriores
        clearErrors();
        
        // Desabilita o botão para evitar cliques múltiplos
        saveButton.disabled = true;
        
        // Esconde a barra de progresso inicialmente
        progressContainer.classList.add('hidden');
        progressBar.style.width = '0%';
        progressText.textContent = '0%';

        // Captura os valores dos inputs DEPOIS do clique no botão
        const titulo = tituloInput.value.trim();
        const autor = autorInput.value.trim();
        const categoria = categoriaInput.value.trim();
        const genero = generoInput.value.trim();
        const materia = materiaInput.value.trim();
        const dataPublicacao = dataPublicacaoInput.value;
        const descricao = descricaoInput.value.trim();
        const arquivo = arquivoInput.files[0];

        console.log('Dados capturados após clique:', {
            titulo,
            autor,
            categoria,
            genero,
            materia,
            dataPublicacao,
            descricao,
            arquivo: arquivo ? arquivo.name : 'Nenhum arquivo selecionado'
        });

        // Validação básica no frontend
        let hasError = false;
        if (!titulo) { document.getElementById('error-titulo').textContent = 'Título é obrigatório.'; document.getElementById('error-titulo').classList.remove('hidden'); hasError = true;}
        if (!autor) { document.getElementById('error-autor').textContent = 'Autor é obrigatório.'; document.getElementById('error-autor').classList.remove('hidden'); hasError = true; }
        if (!categoria) { document.getElementById('error-categoria').textContent = 'Categoria é obrigatória.'; document.getElementById('error-categoria').classList.remove('hidden'); hasError = true;}
        if (!genero) { document.getElementById('error-genero').textContent = 'Gênero é obrigatório.'; document.getElementById('error-genero').classList.remove('hidden'); hasError = true;}
        // Materia é opcional, não precisa de validação
        if (!dataPublicacao) { document.getElementById('error-data_publicacao').textContent = 'Data é obrigatória.'; document.getElementById('error-data_publicacao').classList.remove('hidden'); hasError = true;}
        // Descricao é opcional, não precisa de validação
        if (!arquivo) { document.getElementById('error-arquivo').textContent = 'Arquivo é obrigatório.'; document.getElementById('error-arquivo').classList.remove('hidden'); hasError = true;}
        
        if (hasError) {
            saveButton.disabled = false;
            return;
        }

        // 1. Validar dados no backend
        const formData = new FormData();
        formData.append('titulo', titulo);
        formData.append('autor', autor);
        formData.append('categoria', categoria);
        formData.append('genero', genero);
        formData.append('materia', materia);
        formData.append('data_publicacao', dataPublicacao);
        formData.append('descricao', descricao);
        formData.append('original_filename', arquivo.name);
        
        // Mostrar os dados que serão enviados
        console.log('FormData a ser enviado:');
        for (const pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        try {
            // Primeira requisição: validar dados
            console.log('Enviando validação com token CSRF:', csrfToken);
            const validateResponse = await fetch('{{ route("livros.validateData") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken // Adiciona o token APENAS no cabeçalho
                }
            });

            // Aqui vamos verificar o código de status HTTP
            console.log('Código de status HTTP:', validateResponse.status);
            
            // Se não for 2xx, registrar o texto da resposta para diagnóstico
            if (!validateResponse.ok) {
                const responseText = await validateResponse.text();
                console.error('Resposta de erro completa:', responseText);
                try {
                    // Tentar analisar como JSON de qualquer forma
                    const validateResult = JSON.parse(responseText);
                    console.log('Resposta analisada:', validateResult);
                    
                    if (validateResult.errors) {
                        console.error('Erros de validação:', validateResult.errors);
                        displayErrors(validateResult.errors);
                    } else {
                        showAlert(validateResult.message || 'Erro na validação dos dados.', 'error');
                    }
                } catch (e) {
                    console.error('Não foi possível analisar a resposta como JSON:', e);
                    showAlert('Erro na comunicação com o servidor.', 'error');
                }
                throw new Error('Falha na validação');
            }

            const validateResult = await validateResponse.json();
            console.log('Resposta da validação:', validateResult);

            const { hash, livro_id } = validateResult;
            if (!hash) {
                 throw new Error('Hash não recebido do servidor.');
            }
            
            showAlert('Dados validados. Iniciando upload...', 'success');
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '0%';
            progressText.textContent = '0%';

            await uploadLivroViaStreaming(arquivo, {
                hash,
                livroId: livro_id,
                originalName: arquivo.name,
            }, (percent) => {
                const pct = Number(percent).toFixed(1);
                progressBar.style.width = `${pct}%`;
                progressText.textContent = `${pct}%`;
            });

            showAlert('Livro enviado com sucesso!', 'success');
            progressBar.style.width = '100%';
            saveButton.disabled = false;
            // Recarrega a página automaticamente após concluir o upload
            setTimeout(() => {
                window.location.reload();
            }, 800);

        } catch (error) {
            console.error('Erro no processo:', error);
             if (error.message !== 'Falha na validação') { // Não mostra alerta duplicado
                 showAlert(error.message || 'Ocorreu um erro inesperado.', 'error');
             }
            saveButton.disabled = false; // Reabilita o botão
        }
    }
    
    function uploadLivroViaStreaming(file, payload, progressCallback) {
        const client = window.StreamingUpload.getDefaultClient();

        return client.upload([file], {
            buildRequest: () => ({
                relativePath: file.name,
                fileName: file.name,
                context: 'livros',
                contextPayload: {
                    hash: payload.hash,
                    livro_id: payload.livroId,
                    original_name: payload.originalName,
                },
                fileIndex: 1,
                totalFiles: 1,
            }),
            onFileProgress: ({ percent }) => {
                if (typeof progressCallback === 'function') {
                    progressCallback(Number(percent));
                }
            },
        });
    }

    // Adicionar funcionalidade de pesquisa
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');

    function performSearch() {
        const termo = searchInput.value.trim();
        if (!termo) return;

        fetch(`/livros/pesquisar?termo=${encodeURIComponent(termo)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.data && data.data.length > 0) {
                    // Atualizar a tabela com os resultados
                    const tbody = document.querySelector('tbody');
                    tbody.innerHTML = ''; // Limpar tabela atual
                    
                    data.data.forEach(livro => {
                        const row = createBookRow(livro);
                        tbody.appendChild(row);
                    });
                } else {
                    showAlert('Nenhum livro encontrado para o termo pesquisado.', 'info');
                }
            } else {
                showAlert(data.message || 'Erro ao realizar a pesquisa.', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showAlert('Erro ao realizar a pesquisa. Tente novamente.', 'error');
        });
    }

    function createBookRow(livro) {
        const tr = document.createElement('tr');
        const status = livro.status || 'pendente';
        const statusClass = {
            'completo': 'bg-green-100 text-green-800',
            'pendente': 'bg-gray-100 text-gray-800',
            'validado': 'bg-blue-100 text-blue-800',
            'uploading': 'bg-yellow-100 text-yellow-800',
            'erro': 'bg-red-100 text-red-800'
        }[status] || 'bg-gray-100 text-gray-800';

        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-[200px] truncate" title="${livro.original_filename || 'N/A'}">${livro.original_filename || 'N/A'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-[180px] truncate" title="${livro.titulo}">${livro.titulo}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-[150px] truncate" title="${livro.autor}">${livro.autor}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-[120px] truncate" title="${livro.categoria}">${livro.categoria}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-[120px] truncate" title="${livro.genero}">${livro.genero}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-[120px] truncate" title="${livro.materia || 'N/A'}">${livro.materia || 'N/A'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${livro.data_publicacao ? new Date(livro.data_publicacao).toLocaleDateString('pt-BR') : 'N/A'}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                    ${status.charAt(0).toUpperCase() + status.slice(1)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex flex-col space-y-1">
                    ${livro.status === 'completo' && livro.hash ? `
                        <a href="/livros/download/${livro.hash}" 
                           class="text-indigo-600 hover:text-indigo-900 text-xs bg-indigo-50 px-2 py-1 rounded flex items-center justify-center">
                           <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                           </svg>
                           Baixar
                        </a>
                        
                        <a href="#" onclick="visualizarPDF('${livro.hash}')" 
                           class="text-green-600 hover:text-green-900 text-xs bg-green-50 px-2 py-1 rounded flex items-center justify-center">
                           <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                           </svg>
                           Ver
                        </a>
                    ` : ''}
                    <form action="/livros/${livro.id}" method="POST" class="inline">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="button" onclick="confirmarExclusao(${livro.id})" 
                                class="text-red-600 hover:text-red-900 text-xs bg-red-50 px-2 py-1 rounded w-full flex items-center justify-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Excluir
                        </button>
                    </form>
                </div>
            </td>
        `;
        return tr;
    }

    // Eventos de pesquisa
    searchButton.addEventListener('click', performSearch);
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });

    // Variável para armazenar o formulário atual
    let currentDeleteForm = null;

    // Funções do Modal de Deletar
    window.toggleDeleteModal = function(show) {
        const modal = document.getElementById('deleteModal');
        if (show) {
            modal.classList.remove('hidden');
        } else {
            modal.classList.add('hidden');
            currentDeleteForm = null;
        }
    }

    // Função para confirmar exclusão
    window.confirmarExclusao = function(livroId) {
        currentDeleteForm = event.target.closest('form');
        toggleDeleteModal(true);
    };

    // Evento de clique no botão de confirmar
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (currentDeleteForm) {
            currentDeleteForm.submit();
        }
        toggleDeleteModal(false);
    });
});
</script>
@endsection 