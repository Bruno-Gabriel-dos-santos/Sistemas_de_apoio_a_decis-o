# 🔧 Resolvendo Problema de Conexão Workerman

## Problema Identificado

O Workerman não estava iniciando corretamente via Artisan porque precisa ser executado de forma específica.

## Solução Implementada

Criamos um arquivo `worker-server.php` na raiz do projeto que pode ser executado diretamente. Este é o método recomendado pelo Workerman.

## Como Usar

### Iniciar um Worker Manualmente

```bash
php worker-server.php start --host=127.0.0.1 --port=20001
```

### Iniciar Todos os Workers

```bash
./scripts/start-all-services-workerman.sh
```

O script agora usa `worker-server.php` diretamente em vez de Artisan.

## Arquivos Modificados

1. **worker-server.php** (NOVO)
   - Arquivo bootstrap do Workerman
   - Carrega o Laravel antes de criar workers
   - Executado diretamente pelo PHP

2. **start-all-services-workerman.sh** (ATUALIZADO)
   - Agora usa `php worker-server.php start` diretamente
   - Não usa mais `php artisan websocket:start-workerman`

## Testando

1. Teste um worker individual:
   ```bash
   php worker-server.php start --host=127.0.0.1 --port=20001
   ```

2. Verifique se está ouvindo:
   ```bash
   lsof -i :20001
   ```

3. Teste a conexão WebSocket:
   ```bash
   # Use uma ferramenta como wscat ou teste pelo navegador
   ```

## Se Ainda Não Funcionar

1. Verifique os logs:
   ```bash
   tail -f storage/logs/websocket-worker-1.log
   ```

2. Verifique se há erros do PHP:
   ```bash
   php worker-server.php start --port=20001 2>&1
   ```

3. Verifique se as dependências estão instaladas:
   ```bash
   composer show workerman/workerman
   ```






