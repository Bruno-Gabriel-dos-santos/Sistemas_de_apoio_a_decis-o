# Análise de Upload e Download - Sistema de Apoio Laravel 10

## 📋 Resumo Executivo

Este documento lista todas as páginas e funcionalidades de upload e download identificadas no sistema, incluindo os tipos de arquivos aceitos e os métodos utilizados.

---

## 📤 PÁGINAS COM UPLOAD

### 1. **Arquivos (ArquivoController)**
- **Fluxo**: Cliente StreamingUpload (WebSocket) utilizando o contexto `arquivos`
- **Página**: `/arquivos` → `/arquivos/show/{id_categoria}`
- **Método**: Upload assíncrono via workers Workerman (chunks + gerenciador central)
- **Tipos Aceitos**: 
  - Qualquer tipo de arquivo (sem restrição específica)
  - Suporta estrutura de pastas hierárquica
- **Localização Storage**: `storage/app/arquivos/{categoria}/{path}`
- **Funcionalidades**:
  - Upload de arquivos únicos ou múltiplos
  - Criação automática de pastas
  - Upload em chunks com controle de backpressure
  - Registro no banco de dados (tabela `arquivos`)

### 2. **Códigos/Projetos (CodigosController + UploadController)**
- **Rotas**: 
  - `/api/codigos/{id}/upload` (POST)
  - `/codigos/{codigo}/files/upload-chunk` (POST)
  - `/codigos/{codigo}/files/complete-upload` (POST)
- **Página**: `/codigos` → `/codigos/{codigo}`
- **Método**: Upload simples e upload por chunks
- **Tipos Aceitos**: 
  - Qualquer tipo de arquivo (sem restrição)
  - Arquivos de código, texto, binários
- **Localização Storage**: `storage/app/codigos/{hash_identidade}/`
- **Funcionalidades**:
  - Upload de arquivos de projeto
  - Upload por chunks para arquivos grandes
  - Criação de arquivos vazios
  - Criação de pastas
  - Gerenciamento de estrutura de arquivos

### 3. **Livros (LivroController)**
- **Rotas**: 
  - `/livros/validate-data` (POST)
  - `/livros/upload-chunk` (POST)
  - `/livros/complete-upload` (POST)
- **Página**: `/livros`
- **Método**: Upload por chunks (multipart)
- **Tipos Aceitos**: 
  - **Apenas PDF** (`.pdf`)
  - Validação no frontend: `accept=".pdf"`
- **Localização Storage**: `storage/app/public/livros/pdfs/{hash}.pdf`
- **Funcionalidades**:
  - Upload de livros em PDF
  - Upload em chunks para arquivos grandes
  - Validação de dados antes do upload
  - Registro no banco com hash único

### 4. **Estudos (EstudoController)**
- **Rota**: `/estudos` (POST - store)
- **Página**: `/estudos` → `/estudos/create`
- **Método**: Upload simples (formulário)
- **Tipos Aceitos**: 
  - **Apenas imagens**: `image` (jpeg, png, jpg, gif, svg)
  - Validação: `required|image`
- **Localização Storage**: `storage/app/public/capas/`
- **Funcionalidades**:
  - Upload de capa para estudos
  - Armazenamento no disco `public`

### 5. **Pesquisas (PesquisaController)**
- **Rota**: `/pesquisas` (POST - store)
- **Página**: `/estudos` (compartilhada com estudos)
- **Método**: Upload simples (formulário)
- **Tipos Aceitos**: 
  - **Apenas imagens**: `image` (jpeg, png, jpg, gif, svg)
  - Validação: `required|image`
- **Localização Storage**: `storage/app/public/capas/`
- **Funcionalidades**:
  - Upload de capa para pesquisas
  - Armazenamento no disco `public`

### 6. **Relatórios Situacionais (RelatoriosSituacionaisController)**
- **Rota**: `/relatorios-situacionais` (POST - store)
- **Página**: `/relatorios-situacionais` → `/relatorios-situacionais/create`
- **Método**: Upload simples (formulário)
- **Tipos Aceitos**: 
  - **Imagens**: `image|mimes:jpeg,png,jpg,gif,svg|max:2048`
  - Tamanho máximo: 2MB
- **Localização Storage**: `storage/app/public/capas/`
- **Funcionalidades**:
  - Upload de capa para relatórios
  - Validação de tamanho

### 7. **Sistemas - Arquivos (SistemasArquivosController)**
- **Rotas**: 
  - `/sistemasArquivos/{id}/{path?}/upload` (POST)
  - `/api/sistemasArquivos/{id}/upload` (POST)
- **Página**: `/sistemas/{nome}` → `/sistemasArquivos/{id}`
- **Método**: Upload simples e múltiplo
- **Tipos Aceitos**: 
  - Qualquer tipo de arquivo (sem restrição)
- **Localização Storage**: `storage/app/sistemas/{id}/{path}`
- **Funcionalidades**:
  - Upload de arquivos para sistemas
  - Upload múltiplo (AJAX)
  - Suporte a estrutura de pastas

### 8. **Páginas de Sistemas (PaginasSistemasController)**
- **Rotas**: 
  - `/sistemas/{sistema_id}/paginas_sistemas/{pagina_id}/upload-arquivo` (POST)
  - `/api/paginas_sistemas/upload-arquivos` (POST)
- **Página**: `/sistemas/{sistema_id}/paginas_sistemas/{pagina_id}/upload`
- **Método**: Upload múltiplo por categoria
- **Tipos Aceitos**: 
  - Qualquer tipo de arquivo (sem restrição)
  - Organizados por categorias: `video`, `audio`, `texto`, `imagem`, `arquivos`, `graficos`, `asset`
- **Localização Storage**: `storage/app/paginaSistemas/{pagina_id}/{categoria}/`
- **Funcionalidades**:
  - Upload múltiplo de arquivos
  - Organização por categorias
  - Verificação de arquivos existentes

### 9. **Upload Geral (UploadController)**
- **Rotas**: 
  - `/upload` (POST)
  - `/sistemas/{sistema}/upload-arquivo` (POST)
  - `/sistemas/{sistema_id}/paginas_sistemas/{pagina_id}/upload-arquivo` (POST)
- **Método**: Upload simples
- **Tipos Aceitos**: 
  - Qualquer tipo de arquivo
  - Limite: 10MB (`max:10240`)
- **Localização Storage**: 
  - `storage/app/sistemas/{sistema}/arquivos/`
  - `storage/app/paginaSistemas/{pagina_id}/arquivos/`

### 10. **Categorias de Arquivos (ArquivosCategoriaController)**
- **Rota**: `/categorias-arquivos` (POST)
- **Página**: `/arquivos` (index)
- **Método**: Upload simples
- **Tipos Aceitos**: 
  - **Apenas imagens**: `accept="image/*"`
- **Localização Storage**: `storage/app/public/` (capa da categoria)
- **Funcionalidades**:
  - Upload de imagem de capa para categoria

---

## 📥 PÁGINAS COM DOWNLOAD

### 1. **Arquivos (ArquivoController)**
- **Rota**: `/arquivos/download/{id}` (GET)
- **Método**: Download direto ou ZIP
- **Tipos**:
  - Arquivo único: Download direto
  - Pasta: Compactação em ZIP antes do download
- **Funcionalidades**:
  - Download de arquivos individuais
  - Download de pastas (compactadas em ZIP)
  - Preview de arquivos (`/arquivos/preview/{id}`)
  - Visualizador (`/arquivos/visualizador/{id}`)

### 2. **Códigos/Projetos (CodigosController + UploadController)**
- **Rotas**: 
  - `/api/codigos/download` (POST)
  - `/codigos/{codigo}/files/download` (GET)
  - `/codigos/{codigo}/files/download-project` (GET)
- **Método**: Download direto ou ZIP
- **Tipos**:
  - Arquivo único: Download direto
  - Pasta: Compactação em ZIP
  - Projeto completo: Compactação em ZIP
  - Seleção múltipla: Compactação em ZIP
- **Funcionalidades**:
  - Download de arquivos individuais
  - Download de pastas (compactadas)
  - Download do projeto completo
  - Download de seleção múltipla (compactada)

### 3. **Livros (LivroController)**
- **Rotas**: 
  - `/livros/download/{hash}` (GET)
  - `/livros/view/{hash}` (GET)
- **Método**: Download direto ou visualização
- **Tipos**:
  - PDF: Download ou visualização inline
- **Funcionalidades**:
  - Download de PDF por hash
  - Visualização de PDF no navegador
  - Preserva nome original do arquivo

### 4. **Sistemas - Arquivos (SistemasArquivosController)**
- **Rotas**: 
  - `/sistemasArquivos/{id}/download/{path}` (GET)
  - `/api/sistemasArquivos/download` (POST)
- **Método**: Download direto
- **Tipos**:
  - Arquivo único: Download direto
- **Funcionalidades**:
  - Download de arquivos do sistema

### 5. **Backup (BackupController)**
- **Rota**: `/backup/download/{id}` (GET)
- **Método**: Download direto
- **Tipos**:
  - Arquivo SQL: `.sql`
- **Funcionalidades**:
  - Download de backups do banco de dados
  - Arquivos gerados via mysqldump

---

## 🔧 TIPOS DE UPLOAD UTILIZADOS

### 1. **Upload Simples (Formulário)**
- **Controllers**: EstudoController, PesquisaController, RelatoriosSituacionaisController
- **Método**: `$request->file('campo')->store('path', 'disk')`
- **Uso**: Uploads rápidos de arquivos pequenos (imagens de capa)

### 2. **Upload por Chunks (Multipart)**
- **Controllers / Fluxos**: LivroController, UploadController e serviço de streaming (`ArquivoController` via Workerman)
- **Método**: Divisão do arquivo em partes menores
- **Uso**: Arquivos grandes (PDFs, arquivos de projeto)
- **Processo**:
  1. Arquivo dividido em chunks no frontend
  2. Cada chunk enviado separadamente
  3. Chunks salvos temporariamente
  4. Chunks combinados no final

### 3. **Upload Múltiplo**
- **Controllers**: PaginasSistemasController, SistemasArquivosController
- **Método**: `$request->file('arquivos')` (array)
- **Uso**: Múltiplos arquivos de uma vez

### 4. **Upload AJAX**
- **Controllers**: CodigosController, SistemasArquivosController
- **Método**: Requisições AJAX com FormData
- **Uso**: Uploads assíncronos sem recarregar página

---

## 📊 RESUMO POR TIPO DE ARQUIVO

### Imagens
- **Aceitas em**: Estudos, Pesquisas, Relatórios Situacionais, Categorias de Arquivos
- **Tipos**: jpeg, png, jpg, gif, svg
- **Tamanho máximo**: 2MB (Relatórios), sem limite específico (outros)
- **Storage**: `storage/app/public/capas/`

### PDFs
- **Aceitos em**: Livros
- **Tipo**: `.pdf`
- **Storage**: `storage/app/public/livros/pdfs/`

### Arquivos Gerais
- **Aceitos em**: Arquivos, Códigos, Sistemas, Páginas de Sistemas
- **Tipos**: Qualquer tipo (sem restrição)
- **Storage**: Vários diretórios conforme contexto

### SQL (Backups)
- **Aceitos em**: Backup (geração, não upload)
- **Tipo**: `.sql`
- **Storage**: `storage/app/backup/`

---

## 🗂️ ESTRUTURA DE STORAGE

```
storage/app/
├── arquivos/              # Arquivos por categoria
│   └── {categoria}/
│       └── {path}/
├── codigos/               # Projetos de código
│   └── {hash_identidade}/
├── sistemas/              # Arquivos de sistemas
│   └── {id}/
│       └── {path}/
├── paginaSistemas/        # Arquivos de páginas de sistemas
│   └── {pagina_id}/
│       ├── video/
│       ├── audio/
│       ├── texto/
│       ├── imagem/
│       ├── arquivos/
│       ├── graficos/
│       └── asset/
├── backup/                # Backups do banco
│   └── backup_*.sql
├── tmp/                   # Arquivos temporários
│   └── *.zip
└── streaming/
    └── upload/            # Staging dos uploads via Workerman

storage/app/public/
├── capas/                 # Capas de estudos/pesquisas/relatórios
├── livros/
│   └── pdfs/              # PDFs de livros
└── temp_livros/           # Chunks temporários de livros
```

---

## 🔐 VALIDAÇÕES E SEGURANÇA

### Validações de Upload
- **Tamanho**: 
  - UploadController: 10MB máximo
  - RelatoriosSituacionaisController: 2MB máximo
  - Outros: Sem limite específico
- **Tipos**: 
  - Validação por MIME type
  - Validação por extensão (PDFs)
  - Validação por tipo de imagem

### Autenticação
- Todas as rotas protegidas por middleware `auth`
- Verificação de permissões em alguns controllers

### Validação de Senha
- CodigosController: Requer senha para exclusão
- BackupController: Pode requerer autenticação

---

## 📝 OBSERVAÇÕES IMPORTANTES

1. **Múltiplos métodos de upload**: O sistema utiliza diferentes estratégias dependendo do contexto
2. **Upload por chunks**: Implementado para arquivos grandes (PDFs, projetos)
3. **Estrutura hierárquica**: Suporte a pastas e subpastas em vários módulos
4. **Compactação ZIP**: Pastas são compactadas automaticamente no download
5. **Armazenamento misto**: Uso de `local` e `public` disks conforme necessidade
6. **Temporários**: Arquivos temporários são criados e limpos automaticamente

---

## 🎯 PRÓXIMOS PASSOS SUGERIDOS

1. Padronizar validações de tamanho de arquivo
2. Implementar validação de tipos MIME mais rigorosa
3. Adicionar logs de upload/download para auditoria
4. Implementar rate limiting para uploads
5. Adicionar verificação de vírus/malware
6. Implementar compressão automática de imagens
7. Adicionar suporte a progresso de upload no frontend
8. Implementar retry automático para uploads falhos

---

**Data da Análise**: 2025-01-27
**Versão do Sistema**: Laravel 10
**Ambiente**: Linux



