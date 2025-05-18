<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\EntregadorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\SorteioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GitWebhookController;
use Illuminate\Http\Request;

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

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('check.api.token')->group(function () {
    Route::prefix('/mobile')->controller(MobileAuthController::class)->group(function () {
        Route::post('/teste', 'login');
        Route::post('/mobile', 'logout');
    });
});

Route::post('/git-webhook', [GitWebhookController::class, 'handle']);
Route::get('/whoami', function () {
    return response()->json(['user' => exec('whoami')]);
});


Route::get('/sorteio/{hash}', [SorteioController::class, 'cliente'])->name('sorteio.cliente');
Route::post('/sorteio/{id}/salvar', [SorteioController::class, 'salvarNumeroSorte'])->name('sorteio.salvarNumeroSorte');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/sorteio', [SorteioController::class, 'index'])->name('sorteio.index');
    Route::post('/novo', [SorteioController::class, 'store'])->name('sorteio.store');
    Route::delete('/sorteio/delete/{id}', [SorteioController::class, 'destroy'])->name('sorteio.destroy');

    // Página de listagem de usuários
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuario.index');
    Route::get('/usuarios/create', [UsuarioController::class, 'create'])->name('usuario.create');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuario.store');
    Route::get('/usuarios/{user}/edit', [UsuarioController::class, 'edit'])->name('usuario.edit');
    Route::put('/usuarios/{user}', [UsuarioController::class, 'update'])->name('usuario.update');
    Route::delete('/usuarios/{user}', [UsuarioController::class, 'destroy'])->name('usuario.destroy');

    Route::prefix('/clientes')->controller(ClienteController::class)->group(function () {
        Route::get('/', [ClienteController::class, 'index'])->name('cliente.index');
        Route::get('/novo', [ClienteController::class, 'create'])->name('cliente.create');
        Route::post('/store', [ClienteController::class, 'store'])->name('cliente.store');
        Route::get('/edita/{cliente}', [ClienteController::class, 'edit'])->name('cliente.edit');
        Route::delete('/delete/{cliente}', [ClienteController::class, 'destroy'])->name('cliente.destroy');
        Route::put('/atualiza/{cliente}', [ClienteController::class, 'update'])->name('cliente.update');
        Route::get('/buscar-por-telefone', 'buscarPorTelefone')->name('cliente.buscar.telefone');
    });

    Route::prefix('/pedido')->controller(PedidoController::class)->group(function () {
        Route::get('/', 'index')->name('pedido.index');              // Listagem de pedidos (acompanhamento)
        Route::post('/{id}/alterar-situacao', 'alterarSituacao');
        Route::post('/filtro', 'filtro')->name('pedido.filtro');
        Route::get('/create', 'create')->name('pedido.create');      // Formulário de novo pedido
        Route::post('/', 'store')->name('pedido.store');             // Armazenar novo pedido
        Route::get('/{id}', 'show')->name('pedido.show');            // Exibir detalhes de um pedido
        Route::get('/{id}/edit', 'edit')->name('pedido.edit');       // Formulário para editar pedido
        Route::put('/{id}', 'update')->name('pedido.update');        // Atualizar pedido
        Route::delete('/{id}', 'destroy')->name('pedido.destroy');   // Deletar pedido
        Route::post('/{pedido}/atribuir-entregador', 'atribuirEntregador');   // Deletar pedido
        Route::post('/{pedido}/remover-entregador', 'removerEntregador');
        Route::post('/{pedido}/atualizar-tipo', 'atualizarTipo');
        Route::post('/{pedido}/cancelar', 'cancelar')->name('pedido.cancelar');
    });

    Route::prefix('/dispositivo')->controller(DeviceController::class)->group(function () {
        Route::post('/criar', 'store');
        Route::post('/gerarQr', 'gerarQr')->name('dispositivo.gerarQr');
        Route::get('/', 'index')->name('dispositivo.index');
        Route::get('/novo', 'create')->name('dispositivo.create');
        Route::post('/delete', 'delete')->name('dispositivo.delete');
        Route::get('/getDevices', 'getDevices');
        Route::post('/updateStatus', 'updateStatus');
        Route::post('/updateName', 'updateName');
        Route::get('/getStatus', 'getStatus');
    });

    Route::prefix('/entregador')->name('entregador.')->controller(EntregadorController::class)->group(function () {
        Route::get('/', 'index')->name('index');              // Lista de entregadores
        Route::get('/novo', 'create')->name('create');        // Formulário para novo
        Route::post('/criar', 'store')->name('store');        // Salvar novo entregador
        Route::post('/delete', 'delete')->name('delete');     // Deletar entregador
        Route::get('/editar/{id}', 'edit')->name('edit');     // Formulário de edição
        Route::put('/atualizar/{id}', 'update')->name('update'); // Atualizar dados
        Route::post('/toggleAtivo', 'toggleAtivo')->name('toggleAtivo'); // Ativar/desativar
        Route::post('/salvar-trabalhando', 'salvarTrabalhando')->name('salvarTrabalhando'); // Ativar/desativar
        Route::get('/listar', 'listar');
    });


    Route::prefix('produtos')->name('produto.')->middleware(['auth'])->group(function () {
        Route::get('/', [ProdutoController::class, 'index'])->name('index');
        Route::get('/create', [ProdutoController::class, 'create'])->name('create');
        Route::post('/store', [ProdutoController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [ProdutoController::class, 'edit'])->name('edit');
        Route::put('/update/{id}', [ProdutoController::class, 'update'])->name('update');
        Route::delete('/produtos/{id}', [ProdutoController::class, 'destroy'])->name('destroy');
        Route::post('/toggle-aplicativo', [ProdutoController::class, 'toggleAplicativo'])->name('toggle.aplicativo');
        Route::get('/buscar/{codigo}', [ProdutoController::class, 'buscar']);
    });


    // Route::get('/', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
    Route::get('/', [HomeController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

    Route::get('/buscar-ganhador', [SorteioController::class, 'buscarGanhador']);
});






Route::middleware(['cors'])->post('/obterparcelamento', [CronController::class, 'obterParcelamento']);


require __DIR__ . '/auth.php';
