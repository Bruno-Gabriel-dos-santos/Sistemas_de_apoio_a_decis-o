@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-b-xl shadow-lg p-6">
        <!-- Breadcrumb -->
        <div class="mb-4">
            <span class="text-gray-600">Caminho: </span>
            <a href="{{ route('sistemas.arquivos.index', ['id' => $id]) }}" class="text-indigo-600 hover:underline">/</a>
            @php
                $parts = $currentPath ? explode('/', $currentPath) : [];
                $accum = '';
            @endphp
            @foreach($parts as $i => $part)
                @php $accum .= ($i > 0 ? '/' : '') . $part; @endphp
                / <a href="{{ route('sistemas.arquivos.index', ['id' => $id, 'path' => $accum]) }}" class="text-indigo-600 hover:underline">{{ $part }}</a>
            @endforeach
        </div>

        <!-- Criar nova pasta -->
        <form action="{{ route('sistemas.arquivos.createFolder', ['id' => $id, 'path' => $currentPath]) }}" method="POST" class="mb-4 flex gap-2">
            @csrf
            <input type="text" name="folder_name" placeholder="Nova pasta" class="border rounded px-2 py-1">
            <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded">Criar Pasta</button>
        </form>

        <!-- Upload de arquivo -->
        <form id="sistema-upload-form" action="{{ route('sistemas.arquivos.upload', ['id' => $id, 'path' => $currentPath]) }}" method="POST" enctype="multipart/form-data" class="mb-2 flex gap-2">
            @csrf
            <input type="file" name="arquivo" id="sistema-upload-input" class="border rounded px-2 py-1">
            <button type="submit" id="sistema-upload-button" class="bg-blue-600 text-white px-3 py-1 rounded">Upload</button>
        </form>
        <div id="sistema-upload-status" class="text-sm text-gray-600 mb-4"></div>

        <!-- Listagem de pastas -->
        <h3 class="font-bold text-gray-700 mt-4 mb-2">Pastas</h3>
        <ul>
            @foreach($folders as $folder)
                @php $folderName = basename($folder); $folderPath = ltrim(str_replace('sistemas/' . $id, '', $folder), '/'); @endphp
                <li class="flex items-center gap-2">
                    <a href="{{ route('sistemas.arquivos.index', ['id' => $id, 'path' => $folderPath]) }}" class="text-indigo-700 font-semibold">{{ $folderName }}</a>
                    <form action="{{ route('sistemas.arquivos.destroy', ['id' => $id, 'path' => $folderPath]) }}" method="POST" onsubmit="return confirm('Excluir esta pasta e todo o conteúdo?')" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline text-xs">Excluir</button>
                    </form>
                </li>
            @endforeach
        </ul>

        <!-- Listagem de arquivos -->
        <h3 class="font-bold text-gray-700 mt-4 mb-2">Arquivos</h3>
        <ul>
            @foreach($files as $file)
                @php $fileName = basename($file); $filePath = ltrim(str_replace('sistemas/' . $id, '', $file), '/'); @endphp
                <li class="flex items-center gap-2">
                    <span>{{ $fileName }}</span>
                    <a href="{{ route('sistemas.arquivos.download', ['id' => $id, 'path' => $filePath]) }}" class="text-blue-600 hover:underline text-xs">Download</a>
                    <form action="{{ route('sistemas.arquivos.destroy', ['id' => $id, 'path' => $filePath]) }}" method="POST" onsubmit="return confirm('Excluir este arquivo?')" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline text-xs">Excluir</button>
                    </form>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    const form = document.getElementById('sistema-upload-form');
    const fileInput = document.getElementById('sistema-upload-input');
    const statusEl = document.getElementById('sistema-upload-status');
    const sistemaId = @json($id);
    const currentPath = @json($currentPath ?? '');
    let isUploading = false;

    if (!form || !fileInput) {
        return;
    }

    function updateStatus(message, type = 'info') {
        if (!statusEl) {
            return;
        }
        statusEl.textContent = message;
        statusEl.className = `text-sm mb-4 ${type === 'error' ? 'text-red-600' : type === 'success' ? 'text-green-600' : 'text-gray-600'}`;
    }

    function buildRelativePath(file) {
        const source = (file.webkitRelativePath || file.name || '').replace(/^\/+/, '');
        if (!currentPath) {
            return source;
        }
        return `${currentPath.replace(/\/+$/, '')}/${source}`.replace(/^\/+/, '');
    }

    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        if (isUploading) {
            return;
        }

        const files = fileInput.files ? Array.from(fileInput.files) : [];
        if (!files.length) {
            updateStatus('Selecione pelo menos um arquivo para enviar.', 'error');
            return;
        }
        if (!window.StreamingUpload) {
            updateStatus('Streaming não configurado.', 'error');
            return;
        }

        try {
            isUploading = true;
            updateStatus('Preparando upload...');
            const client = window.StreamingUpload.getDefaultClient();
            await client.upload(files, {
                buildRequest: (file, index) => ({
                    relativePath: buildRelativePath(file),
                    fileName: file.name,
                    context: 'sistemas',
                    contextPayload: {
                        sistema_id: sistemaId,
                    },
                    fileIndex: index + 1,
                    totalFiles: files.length,
                }),
                onFileProgress: ({ descriptor, percent }) => {
                    const pct = Number(percent || 0).toFixed(1);
                    updateStatus(`Arquivo ${descriptor.fileIndex}/${descriptor.totalFiles} - ${pct}%`);
                },
            });
            updateStatus('Upload concluído! Recarregando...', 'success');
            setTimeout(() => window.location.reload(), 600);
        } catch (error) {
            updateStatus(error.message || 'Erro ao enviar arquivos.', 'error');
            isUploading = false;
        } finally {
            fileInput.value = '';
        }
    });
})();
</script>
@endsection 