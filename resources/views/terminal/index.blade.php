@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-gray-900 rounded-lg shadow-lg overflow-hidden">
        <!-- Barra de título -->
        <div class="bg-gray-800 px-4 py-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                </div>
                <span class="text-gray-400 text-sm">Terminal Web</span>
                <div class="flex items-center space-x-2">
                    <span id="connection-status" class="text-gray-400 text-sm">Conectando...</span>
                    <button onclick="reloadTerminal()" class="text-gray-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Terminal ttyd -->
        <div class="relative w-full h-[600px] bg-black">
            <iframe id="terminal-frame"
                src="http://{{ $_SERVER['SERVER_NAME'] }}:7681"
                class="w-full h-full"
                style="border: none;"
                sandbox="allow-same-origin allow-scripts allow-forms allow-modals allow-popups allow-presentation allow-top-navigation"
                allow="clipboard-read; clipboard-write; fullscreen; web-share"
            ></iframe>
            
            <!-- Overlay de erro -->
            <div id="error-overlay" class="hidden absolute inset-0 bg-gray-900 bg-opacity-90 flex items-center justify-center">
                <div class="text-center p-6">
                    <p class="text-red-500 text-lg mb-4">Não foi possível conectar ao terminal</p>
                    <p class="text-gray-400 mb-4">Verifique se o serviço ttyd está rodando:</p>
                    <code class="block bg-black p-3 rounded text-sm text-gray-300 mb-4">
                        sudo systemctl status ttyd
                    </code>
                    <button onclick="reloadTerminal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Tentar Novamente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Painel de ajuda -->
    <div class="mt-6 bg-white rounded-lg shadow-lg p-4">
        <h2 class="text-lg font-semibold mb-4">Informações do Terminal</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="font-medium mb-2">Recursos</h3>
                <ul class="text-sm text-gray-600">
                    <li>Terminal completo e nativo</li>
                    <li>Suporte a cores e formatação</li>
                    <li>Comandos interativos</li>
                    <li>Baixa latência</li>
                </ul>
            </div>
            <div>
                <h3 class="font-medium mb-2">Atalhos</h3>
                <ul class="text-sm text-gray-600">
                    <li>Ctrl+C - Interromper comando</li>
                    <li>Ctrl+D - Sair do terminal</li>
                    <li>Ctrl+L - Limpar tela</li>
                    <li>Tab - Autocompletar</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
let checkCount = 0;
const MAX_CHECKS = 3;

function updateStatus(message, isError = false) {
    const status = document.getElementById('connection-status');
    status.textContent = message;
    status.className = `text-sm ${isError ? 'text-red-500' : 'text-gray-400'}`;
}

function showError(show = true) {
    const overlay = document.getElementById('error-overlay');
    overlay.className = show 
        ? 'absolute inset-0 bg-gray-900 bg-opacity-90 flex items-center justify-center'
        : 'hidden';
}

function focusTerminal() {
    const frame = document.getElementById('terminal-frame');
    if (frame) {
        frame.focus();
        // Tenta focar o terminal dentro do iframe
        try {
            frame.contentWindow.focus();
        } catch (e) {
            console.log('Não foi possível focar o terminal diretamente');
        }
    }
}

function checkTerminalConnection() {
    fetch('http://{{ $_SERVER['SERVER_NAME'] }}:7681', {
        mode: 'no-cors'
    })
    .then(() => {
        updateStatus('Conectado');
        showError(false);
        checkCount = 0;
        // Tenta focar o terminal após conectar
        focusTerminal();
    })
    .catch(error => {
        console.error('Erro ao conectar:', error);
        checkCount++;
        
        if (checkCount >= MAX_CHECKS) {
            updateStatus('Desconectado', true);
            showError(true);
        } else {
            updateStatus('Reconectando...');
            setTimeout(checkTerminalConnection, 2000);
        }
    });
}

function reloadTerminal() {
    const frame = document.getElementById('terminal-frame');
    updateStatus('Reconectando...');
    showError(false);
    checkCount = 0;
    
    frame.src = frame.src;
    checkTerminalConnection();
}

// Monitora o iframe para erros
document.getElementById('terminal-frame').onerror = () => {
    updateStatus('Erro ao carregar', true);
    showError(true);
};

// Inicia verificação quando a página carrega
document.addEventListener('DOMContentLoaded', () => {
    checkTerminalConnection();
    setInterval(checkTerminalConnection, 5000);
    
    // Adiciona evento de clique no container do terminal
    document.querySelector('.relative.w-full.h-[600px]').addEventListener('click', focusTerminal);
    
    // Foca o terminal quando carregar
    const frame = document.getElementById('terminal-frame');
    frame.addEventListener('load', focusTerminal);
});
</script>

<style>
/* Estilo para o iframe do terminal */
#terminal-frame {
    background: #000;
    width: 100%;
    height: 100%;
}

/* Remove outline quando focado */
#terminal-frame:focus {
    outline: none;
}

/* Cursor pointer ao passar sobre o terminal */
.relative.w-full.h-[600px] {
    cursor: text;
}

/* Animação de reconexão */
@keyframes spin {
    to { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
@endsection

@endsection 