<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobilePedidoController;
use App\Http\Controllers\Api\MobileUsuarioController;
use App\Http\Controllers\AbastecimentoController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CarroController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ContactsController;
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
use App\Http\Controllers\MenssageController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\WebhookController;
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






Route::post('/git-webhook', [GitWebhookController::class, 'handle']);
Route::get('/whoami', function () {
    return response()->json(['user' => exec('whoami')]);
});

Route::post('/webhook', [WebhookController::class, 'evento'])->withoutMiddleware([
    \App\Http\Middleware\VerifyCsrfToken::class,
    \App\Http\Middleware\Authenticate::class,
]);

Route::post('/envent', [WebhookController::class, 'envent'])->withoutMiddleware([
    \App\Http\Middleware\VerifyCsrfToken::class,
    \App\Http\Middleware\Authenticate::class,
]);


Route::prefix('/cron')->controller(CronController::class)->group(function () {
    Route::get('/enviarMensagem', 'enviarPendentes');
    Route::get('/mensagemEmMas', 'mensagemEmMassa');
    Route::get('/atualizarWebhooks', 'atualizarWebhooksDispositivos');
    Route::get('/verificar-pendentes', 'verificarMensagensPendentes');
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
    Route::get('/usuarios/{user}/permissions', [UsuarioController::class, 'permissions'])->name('usuario.permissions');
    Route::put('/usuarios/{user}/permissions', [UsuarioController::class, 'updatePermissions'])->name('usuario.permissions.update');

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
        Route::get('/monitor', 'monitorStatus')->name('dispositivo.monitor');
        Route::post('/delete', 'delete')->name('dispositivo.delete');
        Route::get('/getDevices', 'getDevices');
        Route::get('/getStatusAll', 'getStatusAll');
        Route::post('/force-status-check', 'forceStatusCheck');
        Route::post('/updateStatus', 'updateStatus');
        Route::post('/updateName', 'updateName');
        Route::get('/getStatus', 'getStatus');
        Route::post('/check-evolution-status', 'checkEvolutionStatus')->name('dispositivo.checkEvolutionStatus');
        Route::get('/{id}/get', 'getDevice');
        Route::post('/update', 'update');
        Route::post('/atualizar-ultima-recarga', 'atualizarUltimaRecarga')->name('dispositivo.atualizarUltimaRecarga');
        Route::post('/update-recarga', 'updateRecarga')->name('dispositivo.updateRecarga');
        Route::post('/reconectar', 'reconectar')->name('dispositivo.reconectar');
    });

    Route::prefix('/mensagem')->controller(MenssageController::class)->group(function () {
        Route::get('/', 'create')->name('message.create');
        Route::get('/agendamentos', 'indexAgendamentos')->name('message.agendamento');
        Route::get('/getAgendamentos', 'getAgendamentos')->name('message.getAgendamento');
        Route::post('/upload', 'upload')->name('upload.imagem');
        Route::post('/countContact', 'countContact');
        Route::get('/novo', 'index')->name('message.index');;
        Route::get('/getMessage', 'getMessage');
        Route::post('/bulk', 'bulkMessage')->name('message.bulk');
    });

    Route::prefix('/contatos')->controller(ContactsController::class)->group(function () {
        Route::get('/', 'index')->name('contact.index');
        Route::post('/contato', 'store')->name('contact.store');;
        Route::post('/contatoFile', 'storeFile')->name('contact.storeFile');
        Route::put('/updateLista/{id}', 'update');
        Route::post('/new', 'storeContact')->name('contact-more-one.store');
        Route::get('/detalhes/{id}', 'show')->name('contact.show');
        Route::delete('/delete/{id}', 'destroy')->name('contact.destroy');
        Route::delete('/deleteLista', 'delete')->name('contact.deleteLista');
    });


    Route::prefix('/campanha')->controller(CampaignController::class)->group(function () {
        Route::get('/relatorio-de-envio', 'index')->name('campaign.index');;
        Route::get('/edit/{id}', 'edit')->name('campaign.edit');
        Route::get('/ver/{id}', 'show')->name('campaign.show');;
        Route::post('/updateStatus', 'updateStatus')->name('campaign.updateStatus');
        Route::put('/update/{id}', 'update')->name('campaign.update');
        Route::delete('/deletaCampanha/{id}', 'deleteCampanha');
        Route::delete('/{campaign}/contact/{contactList}', 'destroyContact');
    });

     Route::prefix('/agenda')->controller(ScheduleController::class)->group(function () {
            Route::get('/', 'index')->name('schedule.index');
            Route::post('/atualiza', 'update')->name('schedule.update');
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
    
    Route::resource('carros', CarroController::class);
    Route::post('/abastecimentos', [AbastecimentoController::class, 'store'])->name('abastecimentos.store');
    Route::get('/carros/{carro}/abastecimentos', [AbastecimentoController::class, 'getAbastecimentos'])->name('abastecimentos.get');
    Route::delete('/abastecimentos/{abastecimento}', [AbastecimentoController::class, 'destroy'])->name('abastecimentos.destroy');
});






Route::middleware(['cors'])->post('/obterparcelamento', [CronController::class, 'obterParcelamento']);


require __DIR__ . '/auth.php';
