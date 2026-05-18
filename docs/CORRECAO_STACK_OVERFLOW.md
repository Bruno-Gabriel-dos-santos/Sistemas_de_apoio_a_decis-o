# Correção de Stack Overflow

## 🐛 Problema

Erro: `RangeError: Maximum call stack size exceeded` na linha 577 do `test.blade.php`

### Causa:
1. **Recursão síncrona**: Quando `chunkDelay` é 0, a função `processNextChunk()` chama a si mesma diretamente
2. **Stack overflow**: Para chunks de 64MB, isso cria uma pilha de chamadas muito profunda
3. **Event loop bloqueado**: A recursão síncrona não permite que o event loop processe outras coisas

## ✅ Solução Aplicada

### 1. Sempre usar `setTimeout`
```javascript
// ANTES (causava stack overflow):
if (this.chunkDelay > 0) {
    setTimeout(processNextChunk, this.chunkDelay);
} else {
    processNextChunk(); // ❌ Recursão síncrona
}

// DEPOIS (corrigido):
setTimeout(processNextChunk, Math.max(1, this.chunkDelay)); // ✅ Sempre assíncrono
```

### 2. Otimização de `btoa` para chunks grandes
```javascript
// ANTES (causava stack overflow com 64MB):
const base64 = btoa(String.fromCharCode.apply(null, uint8Array)); // ❌ Stack overflow

// DEPOIS (processa em pedaços):
let base64 = '';
const chunkSize = 8192; // 8KB por vez
for (let i = 0; i < uint8Array.length; i += chunkSize) {
    const chunk = uint8Array.slice(i, i + chunkSize);
    base64 += btoa(String.fromCharCode.apply(null, chunk)); // ✅ Sem stack overflow
}
```

## 🔧 Por que funciona?

### `setTimeout` com delay mínimo:
- **Libera a pilha**: Permite que o event loop processe outras coisas
- **Evita stack overflow**: Não acumula chamadas na pilha
- **Mantém velocidade**: Delay de 1ms é imperceptível

### `btoa` em pedaços:
- **Chunks grandes**: 64MB são processados em pedaços de 8KB
- **Menos memória**: Não cria string gigante de uma vez
- **Mais estável**: Evita problemas de memória e stack

## 📊 Impacto

### Antes:
- ❌ Stack overflow com arquivos grandes
- ❌ Upload falha após alguns chunks
- ❌ Navegador pode travar

### Depois:
- ✅ Sem stack overflow
- ✅ Upload funciona com arquivos de qualquer tamanho
- ✅ Navegador responsivo

## 🧪 Teste

1. **Teste com arquivo grande** (>500MB):
   - Deve processar todos os chunks sem erro
   - Progressbar deve atualizar suavemente
   - Navegador deve permanecer responsivo

2. **Monitore console**:
   - Não deve haver erros de stack overflow
   - Logs devem aparecer normalmente

---

**Data**: 2025-01-27
**Problema**: Stack overflow em chunks de 64MB
**Solução**: setTimeout assíncrono + btoa em pedaços







