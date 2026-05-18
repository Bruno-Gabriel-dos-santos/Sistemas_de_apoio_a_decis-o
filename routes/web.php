<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\LivroController;
use App\Http\Controllers\ArquivoController;
use App\Http\Controllers\EstudoController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\LeituraProgressoController;
use App\Http\Controllers\TerminalController;
use App\Http\Controllers\CodigosController;
use App\Http\Controllers\SistemasController;
use App\Http\Controllers\PaginasSistemasController;
use App\Http\Controllers\SistemasArquivosController;
use App\Http\Controllers\FinanceiroController;
use App\Http\Controllers\FinanceiroArquivosController;
use App\Http\Controllers\ArquivosCategoriaController;
use App\Http\Controllers\SystemDatabaseController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\PesquisaController;
use App\Http\Controllers\RelatoriosSituacionaisController;
use App\Http\Controllers\StreamUploadController;
use App\Http\Controllers\DiarioController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Página inicial
Route::get('/', function () {
    return view('welcome');
});

// Rotas de teste de upload streaming (necessita autenticação)
Route::middleware(['auth'])->prefix('streaming')->name('streaming.')->group(function () {
    Route::get('/test', [StreamUploadController::class, 'test'])->name('test');
    Route::get('/files', [StreamUploadController::class, 'listFiles'])->name('files');
});

// Autenticação
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('web');

// Rotas protegidas
Route::middleware(['auth'])->group(function () {
    // Dashboard e Monitoramento
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/monitor', [MonitorController::class, 'index'])->name('monitor.index');

    // Databases
    Route::prefix('databases')->name('databases.')->group(function () {
        Route::get('/', [DatabaseController::class, 'index'])->name('index');
        Route::post('/create', [DatabaseController::class, 'createDatabase'])->name('create');
        Route::post('/drop', [DatabaseController::class, 'dropDatabase'])->name('drop');
        Route::post('/select', [DatabaseController::class, 'selectDatabase'])->name('select');
        Route::post('/table/create', [DatabaseController::class, 'createTable'])->name('table.create');
        Route::post('/table/drop', [DatabaseController::class, 'dropTable'])->name('table.drop');
        Route::get('/table/{table}', [DatabaseController::class, 'showTableDetails'])->name('table.details');
        Route::post('/table/search', [DatabaseController::class, 'searchTable'])->name('table.search');
        Route::get('/table/{table}/edit/{id}', [DatabaseController::class, 'editRecord'])->name('record.edit');
        Route::put('/table/{table}/update/{id}', [DatabaseController::class, 'updateRecord'])->name('record.update');
    });

    // Códigos e Projetos
    Route::get('/dashboard/codigos', [ProjectController::class, 'index'])->name('dashboard.codigos');
    // (As rotas de ProjectController não aparecem nas views, comentar para validação)
    // Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    // Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    // Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    // Route::post('/projects/{project}/execute', [ProjectController::class, 'execute'])->name('projects.execute');

    // Livros
    Route::prefix('livros')->name('livros.')->group(function () {
        Route::get('/', [LivroController::class, 'index'])->name('index');
        Route::post('/validate-data', [LivroController::class, 'validateData'])->name('validateData');
        Route::post('/upload-chunk', [LivroController::class, 'uploadChunk'])->name('uploadChunk');
        Route::post('/complete-upload', [LivroController::class, 'completeUpload'])->name('completeUpload');
        Route::get('/download/{hash}', [LivroController::class, 'downloadByHash'])->name('downloadByHash');
        Route::get('/view/{hash}', [LivroController::class, 'viewPDF'])->name('viewPDF');
        Route::delete('/{id}', [LivroController::class, 'destroy'])->name('destroy');
        Route::get('/pesquisar', [LivroController::class, 'pesquisar'])->name('pesquisar');
        // Progresso de leitura
        Route::prefix('progresso')->name('progresso.')->group(function () {
        Route::get('/', [LeituraProgressoController::class, 'index'])->name('index');
        Route::post('/', [LeituraProgressoController::class, 'storeLeituraAtual'])->name('store');
        Route::put('/{leitura}', [LeituraProgressoController::class, 'updateLeituraAtual'])->name('update');
        Route::delete('/{leitura}', [LeituraProgressoController::class, 'deleteLeituraAtual'])->name('delete');
        Route::post('/registro', [LeituraProgressoController::class, 'updateRegistro'])->name('updateRegistro');
    });

    });

    // Arquivos
    Route::get('/arquivos', [ArquivoController::class, 'index'])->name('arquivos.index');
    Route::get('/arquivos/show/{id_categoria}', [ArquivoController::class, 'show'])->name('arquivos.show');
    Route::post('/arquivos/listar', [ArquivoController::class, 'listar']);
    Route::post('/arquivos/visualizar', [ArquivoController::class, 'visualizar']);
    Route::post('/arquivos/excluir', [ArquivoController::class, 'excluir']);
    Route::post('/arquivos/excluir-pasta', [ArquivoController::class, 'excluirPasta']);
    Route::get('/arquivos/download/{id}', [ArquivoController::class, 'download']);
    Route::post('/arquivos/criar-pasta', [ArquivoController::class, 'criarPasta']);
    Route::get('/arquivos/visualizador/{id}', [ArquivoController::class, 'visualizador'])->name('arquivos.visualizador');
    Route::get('/arquivos/preview/{id}', [ArquivoController::class, 'preview'])->name('arquivos.preview');
    Route::post('/arquivos/token', [ArquivoController::class, 'streamingToken'])->name('arquivos.token');

    // Estudos
    Route::get('/estudos', [EstudoController::class, 'index'])->name('estudos.index');
    Route::resource('estudos', EstudoController::class);
    Route::get('ajax/estudos', [EstudoController::class, 'ajaxListJson'])->name('ajax.estudos');

    // Financeiro
    Route::get('/financeiro', [FinanceiroController::class, 'index'])->name('financeiro.index');
    Route::get('/financeiro/{section}', [FinanceiroController::class, 'show'])
        ->where('section', '[A-Za-z\-]+')
        ->name('financeiro.show');
    Route::post('/financeiro/irpf/upload', [FinanceiroController::class, 'uploadIrpf'])
        ->name('financeiro.irpf.upload');
    Route::get('/financeiro/irpf/download/{bucket}/{filename}', [FinanceiroController::class, 'downloadIrpf'])
        ->where('filename', '.+')
        ->name('financeiro.irpf.download');
    Route::get('/financeiro/irpf/view/{bucket}/{filename}', [FinanceiroController::class, 'viewIrpf'])
        ->where('filename', '.+')
        ->name('financeiro.irpf.view');

    Route::prefix('api/financeiro-arquivos')->group(function () {
        Route::get('{section}/tree', [FinanceiroArquivosController::class, 'tree'])
            ->where('section', '[A-Za-z\-]+')
            ->name('financeiro.files.tree');
        Route::get('{section}/file', [FinanceiroArquivosController::class, 'getFile'])
            ->where('section', '[A-Za-z\-]+')
            ->name('financeiro.files.file');
        Route::post('{section}/save', [FinanceiroArquivosController::class, 'saveFile'])
            ->where('section', '[A-Za-z\-]+')
            ->name('financeiro.files.save');
        Route::post('{section}/create-file', [FinanceiroArquivosController::class, 'createFile'])
            ->where('section', '[A-Za-z\-]+')
            ->name('financeiro.files.create-file');
        Route::post('{section}/create-folder', [FinanceiroArquivosController::class, 'createFolder'])
            ->where('section', '[A-Za-z\-]+')
            ->name('financeiro.files.create-folder');
        Route::post('{section}/delete', [FinanceiroArquivosController::class, 'delete'])
            ->where('section', '[A-Za-z\-]+')
            ->name('financeiro.files.delete');
        Route::post('{section}/download', [FinanceiroArquivosController::class, 'download'])
            ->where('section', '[A-Za-z\-]+')
            ->name('financeiro.files.download');
    });

    // Diário
    Route::prefix('diario')->name('diario.')->group(function () {
        Route::get('/', [DiarioController::class, 'index'])->name('index');
        Route::get('/{date}', [DiarioController::class, 'show'])
            ->where('date', '\d{4}-\d{2}-\d{2}')
            ->name('show');
        Route::post('/{date}', [DiarioController::class, 'save'])
            ->where('date', '\d{4}-\d{2}-\d{2}')
            ->name('save');
    });

    // Terminal
    Route::get('/terminal', function () {
        return view('terminal.index');
    })->name('terminal.index');
    Route::post('/terminal/execute', [TerminalController::class, 'execute'])->name('terminal.execute');

    // Códigos e Sistemas (views)
    Route::get('/codigos-sistemas', function () {
        return view('codigos-sistemas.index');
    })->name('codigos-sistemas.index');

    // Códigos (CRUD e arquivos)
    Route::prefix('codigos')->name('codigos.')->group(function () {
        Route::get('/', [CodigosController::class, 'index'])->name('index');
        Route::post('/', [CodigosController::class, 'store'])->name('store');
        Route::get('/{codigo}', [CodigosController::class, 'show'])->name('show');
        Route::delete('/{id}', [CodigosController::class, 'destroy'])->name('destroy');
        // Gerenciamento de arquivos do projeto
        Route::prefix('{codigo}/files')->group(function () {
            Route::get('/tree', [UploadController::class, 'getFileTree']);
            Route::get('/list', [UploadController::class, 'listFiles']);
            Route::get('/search', [UploadController::class, 'searchFiles']);
            Route::get('/view', [UploadController::class, 'viewFile']);
            Route::get('/download', [UploadController::class, 'downloadFile']);
            Route::get('/download-project', [UploadController::class, 'downloadProject']);
            Route::post('/upload-chunk', [UploadController::class, 'uploadChunk']);
            Route::post('/complete-upload', [UploadController::class, 'completeUpload']);
            Route::post('/create-folder', [UploadController::class, 'createFolder']);
            Route::post('/create-file', [UploadController::class, 'createFile']);
            Route::delete('/delete', [UploadController::class, 'deleteItem']);
            Route::post('/compress', [UploadController::class, 'compressSelection']);
            Route::post('/rename', [UploadController::class, 'renameItem']);
            Route::post('/move', [UploadController::class, 'moveItem']);
        });
    });

    // Sistemas
    Route::get('/sistemas', [SistemasController::class, 'index'])->name('sistemas.index');
    Route::post('/sistemas', [SistemasController::class, 'store'])->name('sistemas.store');
    Route::get('/sistemas/busca', [SistemasController::class, 'busca'])->name('sistemas.busca');
    Route::get('/sistemas/{nome}', [SistemasController::class, 'show'])->name('sistemas.show');
    Route::delete('/sistemas/{id}/excluir-completo', [SistemasController::class, 'destroyCompleto'])->name('sistemas.destroyCompleto');

    // Páginas de Sistemas
    Route::prefix('sistemas/{sistema_id}/paginas_sistemas')->name('paginas_sistemas.')->group(function () {
        Route::get('/', [PaginasSistemasController::class, 'index'])->name('index');
        Route::get('/create', [PaginasSistemasController::class, 'create'])->name('create');
        Route::post('/', [PaginasSistemasController::class, 'store'])->name('store');
        Route::get('/{id}', [PaginasSistemasController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [PaginasSistemasController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PaginasSistemasController::class, 'update'])->name('update');
        Route::delete('/{id}', [PaginasSistemasController::class, 'destroy'])->name('destroy');
        Route::get('/{pagina_id}/upload', [PaginasSistemasController::class, 'uploadView'])->name('upload');
    });
    Route::post('/sistemas/{sistema}/upload-arquivo', [UploadController::class, 'uploadArquivo'])->name('upload.arquivo');
    Route::post('/sistemas/{sistema_id}/paginas_sistemas/{pagina_id}/upload-arquivo', [UploadController::class, 'uploadArquivoPagina'])->name('paginas_sistemas.upload_arquivo');

    Route::prefix('sistemas/{sistema}/db')->name('sistemas.db.')->group(function () {
        Route::get('/tables', [SystemDatabaseController::class, 'tables'])->name('tables');
        Route::get('/tables/{table}', [SystemDatabaseController::class, 'tableData'])->name('table.data');
        Route::post('/sql', [SystemDatabaseController::class, 'runSql'])->name('sql');
        Route::post('/table/create', [SystemDatabaseController::class, 'createTable'])->name('table.create');
        Route::post('/table/drop', [SystemDatabaseController::class, 'dropTable'])->name('table.drop');
        Route::post('/table/{table}/insert', [SystemDatabaseController::class, 'insertRow'])->name('table.insert');
        Route::put('/table/{table}/update/{id}', [SystemDatabaseController::class, 'updateRow'])->name('table.update');
        Route::delete('/table/{table}/delete/{id}', [SystemDatabaseController::class, 'deleteRow'])->name('table.delete');
        Route::post('/table/{table}/alter', [SystemDatabaseController::class, 'alterTable'])->name('table.alter');
        Route::get('/adminer', [AdminerController::class, 'open'])->name('adminer')->middleware('auth');
        Route::match(['get','post'], '/adminer/proxy', [AdminerController::class, 'proxy'])->name('adminer.proxy')->middleware('auth');
    });

    // Uploads gerais
    Route::post('/upload', [UploadController::class, 'upload'])->name('upload');
    Route::post('/criar-diretorio', [UploadController::class, 'criarDiretorio'])->name('criar.diretorio');
    Route::get('/listar/{codigo}', [UploadController::class, 'listar'])->name('listar.arquivos');
    Route::delete('/excluir/{codigo}/{path}', [UploadController::class, 'excluir'])->name('excluir.arquivo');

    // Rotas API AJAX (usadas por JS, não removidas)
    Route::get('/codigos/info/{id}', [CodigosController::class, 'getCodigoInfo']);
    Route::get('/api/codigos/{id}/tree', [CodigosController::class, 'fileTree']);
    Route::post('/api/codigos/{id}/upload', [CodigosController::class, 'uploadArquivo']);
    Route::post('/api/codigos/{id}/delete-arquivo', [CodigosController::class, 'destroyArquivo']);
    Route::post('/api/codigos/salvar-arquivo', [CodigosController::class, 'salvarArquivo']);
    Route::get('/api/codigos/arquivo', [CodigosController::class, 'getArquivo']);
    Route::post('/api/codigos/criar-arquivo', [CodigosController::class, 'criarArquivo']);
    Route::post('/api/codigos/criar-pasta', [CodigosController::class, 'criarPasta']);
    Route::post('/api/codigos/download', [CodigosController::class, 'downloadArquivoOuPasta']);
    Route::post('/api/codigos/excluir', [CodigosController::class, 'excluirProjeto']);
    Route::post('/api/paginas_sistemas/check-arquivos', [PaginasSistemasController::class, 'checkArquivosExistentes']);
    Route::post('/api/paginas_sistemas/upload-arquivos', [PaginasSistemasController::class, 'uploadArquivos']);

    // CSRF Token (usado por JS)
    Route::get('/csrf-token', function () {
        return response()->json(['token' => csrf_token()]);
    })->name('csrf.token');

    // Gerenciamento de arquivos dos sistemas
    Route::prefix('sistemasArquivos')->name('sistemas.arquivos.')->group(function () {
        Route::get('/{id}/{path?}', [SistemasArquivosController::class, 'index'])->where('path', '.*')->name('index');
        Route::post('/{id}/{path?}/upload', [SistemasArquivosController::class, 'upload'])->where('path', '.*')->name('upload');
        Route::post('/{id}/{path?}/create-folder', [SistemasArquivosController::class, 'createFolder'])->where('path', '.*')->name('createFolder');
        Route::get('/{id}/download/{path}', [SistemasArquivosController::class, 'download'])->where('path', '.*')->name('download');
        Route::delete('/{id}/delete/{path}', [SistemasArquivosController::class, 'destroy'])->where('path', '.*')->name('destroy');
    });

    // Rotas API AJAX para sistemasArquivos (padrão igual codigos)
    Route::prefix('api/sistemasArquivos')->group(function () {
        Route::get('{id}/tree', [SistemasArquivosController::class, 'apiTree']);
        Route::post('{id}/upload', [SistemasArquivosController::class, 'apiUpload']);
        Route::post('{id}/delete-arquivo', [SistemasArquivosController::class, 'apiDelete']);
        Route::post('salvar-arquivo', [SistemasArquivosController::class, 'apiSalvarArquivo']);
        Route::post('criar-arquivo', [SistemasArquivosController::class, 'apiCriarArquivo']);
        Route::post('criar-pasta', [SistemasArquivosController::class, 'apiCriarPasta']);
        Route::post('download', [SistemasArquivosController::class, 'apiDownload']);
        Route::get('arquivo', [SistemasArquivosController::class, 'apiGetArquivo']);
    });

    // Categorias de Arquivos
    Route::prefix('categorias-arquivos')->group(function () {
        Route::get('/', [ArquivosCategoriaController::class, 'index']);
        Route::post('/', [ArquivosCategoriaController::class, 'store']);
        Route::delete('/{id}', [ArquivosCategoriaController::class, 'destroy']);
    });

    // Rotas de Backup
    Route::prefix('backup')->group(function() {
        Route::post('/criar', [BackupController::class, 'criarBackup']);
        Route::get('/listar', [BackupController::class, 'listarBackups']);
        Route::get('/download/{id}', [BackupController::class, 'downloadBackup']);
    });

    // Pesquisas
    Route::resource('pesquisas', PesquisaController::class);
    Route::get('ajax/pesquisas', [PesquisaController::class, 'ajaxListJson'])->name('ajax.pesquisas');

    // Relatórios Situacionais
    Route::get('/relatorios-situacionais', [RelatoriosSituacionaisController::class, 'index'])->name('relatorios-situacionais.index');
    Route::post('/relatorios-situacionais', [RelatoriosSituacionaisController::class, 'store'])->name('relatorios-situacionais.store');
    Route::get('/relatorios-situacionais/{id}', [RelatoriosSituacionaisController::class, 'show'])->name('relatorios-situacionais.show');
    Route::get('/relatorios-situacionais/{id}/edit', [RelatoriosSituacionaisController::class, 'edit'])->name('relatorios-situacionais.edit');
    Route::put('/relatorios-situacionais/{id}', [RelatoriosSituacionaisController::class, 'update'])->name('relatorios-situacionais.update');
    Route::delete('/relatorios-situacionais/{id}', [RelatoriosSituacionaisController::class, 'destroy'])->name('relatorios-situacionais.destroy');
});

// Rotas antigas/duplicadas (não usadas nas views, comentar para validação)
// Route::prefix('codigos/{hash}/files')->group(function () {
//     Route::post('/tree', [UploadController::class, 'getFileTree']);
//     Route::post('/list', [UploadController::class, 'listFiles']);
//     // ... demais rotas ...
// });
