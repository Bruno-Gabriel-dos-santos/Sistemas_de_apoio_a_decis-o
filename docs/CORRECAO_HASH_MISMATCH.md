# Correção de Hash Mismatch

## 🐛 Problema

Chunks estavam sendo reenviados mas continuavam dando erro de hash mismatch:
- Cliente calcula hash **SHA-256**
- Servidor estava usando **MD5** por padrão
- Resultado: hash sempre diferente = chunks sempre inválidos

## ✅ Correções Aplicadas

### 1. Sincronização de Algoritmo de Hash

**Antes**:
- Cliente: SHA-256 (Web Crypto API)
- Servidor: MD5 (padrão)

**Depois**:
- Cliente: SHA-256 (Web Crypto API)
- Servidor: SHA-256 (configurável via config)

### 2. Melhorias na Conversão Base64

**Problema**: Conversão de Uint8Array para string pode corromper dados

**Solução**:
```javascript
// ANTES (pode corromper):
base64 += btoa(String.fromCharCode.apply(null, chunk));

// DEPOIS (seguro):
const chunkArray = Array.from(chunk);
const chunkString = String.fromCharCode.apply(null, chunkArray);
base64 += btoa(chunkString);
```

### 3. Validação Rigorosa de Base64

**Backend**:
```php
// Decodifica com strict = true
$chunkData = base64_decode($data['chunk'], true);

// Valida tamanho
if ($expectedSize && abs(strlen($chunkData) - $expectedSize) > 1024) {
    // Log warning
}
```

**Frontend**:
```javascript
// Valida tamanho do base64
const expectedBase64Size = Math.ceil(uint8Array.length * 4 / 3);
if (base64.length < expectedBase64Size * 0.9) {
    // Log warning
}
```

## ⚙️ Configuração

### No `.env`:
```env
# Algoritmo de hash (deve ser o mesmo no cliente e servidor)
STREAM_HASH_ALGORITHM=sha256  # sha256 ou md5
STREAM_HASH_REQUIRED=true      # Hash obrigatório
STREAM_ENABLE_HASH=true        # Habilitar hash
```

### No `config/streaming.php`:
```php
'performance' => [
    'hash_algorithm' => env('STREAM_HASH_ALGORITHM', 'sha256'),
    'hash_required' => env('STREAM_HASH_REQUIRED', true),
    'enable_hash' => env('STREAM_ENABLE_HASH', true),
]
```

## 🔍 Como Funciona Agora

### Cliente:
1. Lê chunk de 64MB
2. Calcula hash SHA-256 do ArrayBuffer
3. Converte para base64 em pedaços de 8KB
4. Envia chunk + hash + sequência

### Servidor:
1. Recebe chunk + hash + sequência
2. Decodifica base64 com validação rigorosa
3. Calcula hash SHA-256 dos dados decodificados
4. Compara com hash recebido
5. Se válido, adiciona à fila
6. Se inválido, solicita reenvio

## 🧪 Teste

1. **Reinicie o servidor WebSocket**:
```bash
lsof -ti:8080 | xargs kill
php artisan websocket:start
```

2. **Teste upload**:
   - Chunks devem ser validados corretamente
   - Não deve mais dar hash mismatch
   - Reenvios devem funcionar

3. **Monitore logs**:
```bash
tail -f storage/logs/laravel.log | grep -i "hash\|chunk"
```

## ⚠️ Importante

1. **Algoritmo deve ser o mesmo** no cliente e servidor
2. **Base64 deve ser decodificado corretamente** (strict = true)
3. **Tamanho deve ser validado** após decodificação
4. **Conversão de Uint8Array** deve ser feita corretamente

---

**Data**: 2025-01-27
**Problema**: Hash mismatch (SHA-256 vs MD5)
**Solução**: Sincronização de algoritmo + validação rigorosa







