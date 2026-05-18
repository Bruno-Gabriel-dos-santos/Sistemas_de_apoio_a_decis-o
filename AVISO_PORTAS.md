# ⚠️ AVISO SOBRE PORTA 8843

## Observação Importante

O Ratchet WebSocket **sempre cria dois sockets** quando inicia:

1. **Socket Principal**: Na porta especificada (ex: 20001, 20010, 20020, 20040)
2. **Socket Secundário**: Sempre na porta **8843**

Isso é um comportamento padrão do Ratchet e **não é um erro**.

## O Que Fazer

✅ **SEMPRE conecte na porta PRINCIPAL** (20001, 20010, 20020, 20040)

❌ **NÃO conecte na porta 8843** - Ela é apenas para uso interno do Ratchet

## Verificando Qual Porta Usar

Para verificar em qual porta o worker está realmente escutando:

```bash
lsof -i -P | grep LISTEN | grep php
```

Você verá algo como:
```
php  PID bruno  7u  IPv4  TCP localhost:20040 (LISTEN)  ← USE ESTA
php  PID bruno  8u  IPv4  TCP localhost:8843 (LISTEN)   ← IGNORE ESTA
```

**Sempre use a porta que não seja 8843!**

## Portas Configuradas

- Worker 1: **20001** ✅
- Worker 2: **20010** ✅
- Worker 3: **20020** ✅
- Worker 4: **20040** ✅ (mudada de 20030 para evitar confusão)

## URLs Corretas no Frontend

```javascript
const workers = [
    new WebSocket('ws://127.0.0.1:20001/upload'),  // ✅ Correto
    new WebSocket('ws://127.0.0.1:20010/upload'),  // ✅ Correto
    new WebSocket('ws://127.0.0.1:20020/upload'),  // ✅ Correto
    new WebSocket('ws://127.0.0.1:20040/upload'),  // ✅ Correto
    // NUNCA use: ws://127.0.0.1:8843/upload ❌
];
```

## Resumo

- O socket 8843 é **normal** e **esperado**
- **Ignore** o socket 8843
- **Use sempre** as portas principais (20001, 20010, 20020, 20040)
- Se o frontend não conectar, verifique se está usando a porta correta






