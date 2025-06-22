@extends('sistema.layouts.app')
<style>
    #filtro_entregador {
        font-family: monospace;
    }

    .entregador-opcao {
        background: #8b7a7a0;
        padding: 10px 15px;
        font-size: 14px;
        border-radius: 5px;
    }
</style>
@section('content')
    <div class="container mt-4">
        <div class="page-header-content py-3">
            <div class="d-sm-flex align-items-center justify-content-between">
                <h1 class="h3 mb-0 text-gray-800">Pedidos</h1>
            </div>
            <ol class="breadcrumb mb-0 mt-4">
                <li class="breadcrumb-item"><a href="/">Início</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pedidos</li>
            </ol>
        </div>

        <!-- Filtros -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form id="form-filtro" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Data Início</label>
                        <input type="date" class="form-control" id="data_inicio" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data Fim</label>
                        <input type="date" class="form-control" id="data_fim" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="filtro_telefone">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Entregador</label>
                        <select class="form-select font-monospace" id="filtro_entregador">
                            <option value="">Todos</option>
                            @foreach ($entregadores as $entregador)
                                <option value="{{ $entregador->id }}">
                                    {{ str_pad($entregador->nome, 20) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Situação</label>
                        <select class="form-select" id="filtro_situacao">
                            <option value="">Todas</option>
                            @foreach ($situacoes as $situacao)
                                <option value="{{ $situacao->id }}">{{ $situacao->descricao }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-primary w-100" id="btnPesquisar">Pesquisar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabela -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center" id="tabelaPedidos">
                        <thead>
                            <tr>
                                <th>Telefone</th>
                                <th>Cliente</th>
                                <th>Data/Hora</th>
                                <th>Total</th>
                                <th>Situação</th>
                                <th>Entregador</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaPedidosCorpo">
                            <tr>
                                <td colspan="7">Carregando pedidos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('sistema.pedido.modais') <!-- Modal entregador/situação cancelamento etc -->
@endsection


@section('scripts')
    {{-- <script src="{{ asset('js/pedidos/index.js') }}"></script> --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabela = document.getElementById('tabelaPedidos');
            const btnPesquisar = document.getElementById('btnPesquisar');

            // Chamada inicial ao carregar a página
            carregarPedidos();

            // Atualiza os pedidos ao clicar em Pesquisar
            btnPesquisar.addEventListener('click', carregarPedidos);

            function carregarPedidos() {
                const filtros = {
                    data_inicio: document.getElementById('data_inicio').value,
                    data_fim: document.getElementById('data_fim').value,
                    telefone: document.getElementById('filtro_telefone').value,
                    entregador: document.getElementById('filtro_entregador').value,
                    situacao: document.getElementById('filtro_situacao').value,
                };

                fetch('/pedido/filtro', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(filtros)
                    })
                    .then(res => res.json())
                    .then(pedidos => montarTabela(pedidos))
                    .catch(erro => {
                        console.error('Erro ao buscar pedidos:', erro);
                    });
            }

            function montarTabela(pedidos) {
                const tabela = document.getElementById('tabelaPedidosCorpo');
                tabela.innerHTML = '';

                if (pedidos.length === 0) {
                    tabela.innerHTML = `<tr><td colspan="7" class="text-center">Nenhum pedido encontrado</td></tr>`;
                    return;
                }

                pedidos.forEach(pedido => {
                    const status = pedido.status_pedido?.descricao?.toLowerCase() ?? '';
                    const isFinalizadoOuCancelado = status === 'cancelado' || status === 'finalizado';

                    // Botão de entregador
                    let entregador = '';
                    if (isFinalizadoOuCancelado) {
                        // Se finalizado ou cancelado, mostra o nome do entregador (ou vazio) sem botão clicável
                        entregador = pedido.entregador ? pedido.entregador.nome : '';
                    } else {
                        let entregadorBtnTexto = '';

                        if (pedido.entregador) {
                            entregadorBtnTexto = pedido.entregador.nome;
                        } else if (pedido.tipo_pedido === 'tele_entrega') {
                            entregadorBtnTexto = '<i class="fas fa-user-plus"></i> Adicionar Entregador';
                        } else if (pedido.tipo_pedido) {
                            entregadorBtnTexto = pedido.tipo_pedido.replace('_', ' ').toUpperCase();
                        } else {
                            entregadorBtnTexto = 'Selecionar Tipo ou Entregador';
                        }

                        entregador = `
                                <button class="btn btn-sm btn-outline-primary selecionar-entregador" data-id="${pedido.id}" title="Selecionar ou alterar entregador/tipo">
                                    ${entregadorBtnTexto}
                                </button>`;
                    }


                    // Botão de situação
                    const cor = pedido.status_pedido?.cor ?? '#0d6efd';
                    const texto = pedido.status_pedido?.descricao ?? 'Alterar';

                    const situacao = `
                            <button class="btn btn-sm text-white alterar-situacao"
                                style="background-color: ${cor}; border: none;"
                                data-id="${pedido.id}" 
                                title="Alterar situação"
                                ${isFinalizadoOuCancelado ? 'disabled' : ''}>
                                <i class="fas fa-exchange-alt me-1"></i> ${texto}
                            </button>`;

                    // Endereço
                    let endereco = `${pedido.cliente.logradouro ?? ''}, ${pedido.cliente.numero ?? ''}`;
                    endereco += pedido.cliente.complemento ? ` - ${pedido.cliente.complemento}` : '';
                    endereco += pedido.cliente.bairro ? ` - ${pedido.cliente.bairro}` : '';
                    endereco += pedido.cliente.cidade ? ` - ${pedido.cliente.cidade}` : '';

                    const clienteInfo = `
                                        ido.cliente.nome}</strong><br>
                                        ${endereco}`;

                    // Botões de ação
                    let acoes = '';
                    if (!isFinalizadoOuCancelado) {
                        acoes = `
                <button class="btn editar" data-id="${pedido.id}" title="Editar">
                    <i class="fa fa-pencil-alt"></i>
                </button>
                <button class="btn cancelar" data-id="${pedido.id}" title="Cancelar pedido">
                    <i class="fa fa-times"></i>
                </button>`;
                    }

                    const row = `
            <tr>
                <td>${pedido.cliente.telefone}</td>
                <td>${clienteInfo}</td>
                <td>${formatarDataHora(pedido.created_at)}</td>
                <td>R$ ${Number(pedido.valor_total).toFixed(2)}</td>
                <td>${situacao}</td>
                <td>${entregador}</td>
                <td>${acoes}</td>
            </tr>`;
                    tabela.insertAdjacentHTML('beforeend', row);
                });

                // Eventos dinâmicos
                document.querySelectorAll('.editar').forEach(btn =>
                    btn.addEventListener('click', e => editarPedido(e.target.closest('button').dataset.id))
                );

                document.querySelectorAll('.cancelar').forEach(btn =>
                    btn.addEventListener('click', e => cancelarPedido(e.target.closest('button').dataset.id))
                );

                document.querySelectorAll('.selecionar-entregador').forEach(btn =>
                    btn.addEventListener('click', e => abrirModalEntregador(e.target.closest('button').dataset
                        .id))
                );

                document.querySelectorAll('.alterar-situacao').forEach(btn =>
                    btn.addEventListener('click', e => {
                        if (!btn.disabled) {
                            abrirModalSituacao(e.target.closest('button').dataset.id)
                        }
                    })
                );
            }

            function formatarDataHora(dataStr) {
                const data = new Date(dataStr);
                const dia = data.toLocaleDateString();
                const hora = data.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                return `${dia} ${hora}`;
            }

            function editarPedido(id) {
                window.location.href = `/pedidos/${id}/editar`;
            }

            function cancelarPedido(id) {
                // Abre modal perguntando motivo do cancelamento
                // Aqui você pode preencher o modal com o ID e abrir ele
                $('#modalCancelar').modal('show');
                document.getElementById('pedido_cancelar_id').value = id;
            }

            function abrirModalEntregador(id) {
                $('#modalSelecionarEntregador').modal('show');
                document.getElementById('pedido_id_entregador').value = id;

                const lista = document.getElementById('lista-entregadores');
                lista.innerHTML = '<span>Carregando entregadores...</span>';

                fetch('/entregador/listar')
                    .then(res => res.json())
                    .then(entregadores => {
                        lista.innerHTML = '';

                        // Botões de entregadores
                        entregadores.forEach(entregador => {
                            const botao = document.createElement('button');
                            botao.className = 'btn btn-outline-primary';
                            botao.innerHTML =
                                `${entregador.nome} (${entregador.pedidos_do_dia} pedidos hoje)`;
                            botao.onclick = () => selecionarEntregador(entregador.id);
                            lista.appendChild(botao);
                        });

                        // Depois dos entregadores, botões dos tipos de pedido
                        const tipos = [{
                                valor: 'pdv',
                                texto: 'PDV'
                            },
                            {
                                valor: 'portaria',
                                texto: 'Portaria'
                            }
                        ];

                        tipos.forEach(tipo => {
                            const botaoTipo = document.createElement('button');
                            botaoTipo.className =
                                'btn btn-outline-secondary'; // cor diferente dos entregadores
                            botaoTipo.innerHTML = tipo.texto;
                            botaoTipo.onclick = () => selecionarTipoPedido(tipo.valor);
                            lista.appendChild(botaoTipo);
                        });

                    })
                    .catch(error => {
                        lista.innerHTML = '<span>Erro ao carregar entregadores</span>';
                        console.error(error);
                    });
            }

            function selecionarEntregador(entregadorId) {
                const pedidoId = document.getElementById('pedido_id_entregador').value;

                fetch(`/pedido/${pedidoId}/atribuir-entregador`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            entregador_id: entregadorId
                        })
                    })
                    .then(res => res.json())
                    .then(res => {
                        $('#modalSelecionarEntregador').modal('hide');
                        carregarPedidos();
                    })
                    .catch(error => {
                        console.error('Erro ao selecionar entregador:', error);
                    });
            }

            function selecionarTipoPedido(tipo) {
                const pedidoId = document.getElementById('pedido_id_entregador').value;

                fetch(`/pedido/${pedidoId}/atualizar-tipo`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            tipo_pedido: tipo
                        })
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            // Se mudou para PDV ou Portaria, também remove entregador
                            if (tipo === 'pdv' || tipo === 'portaria') {
                                fetch(`/pedido/${pedidoId}/remover-entregador`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector(
                                                'meta[name="csrf-token"]').content
                                        }
                                    })
                                    .then(res => res.json())
                                    .then(() => {
                                        $('#modalSelecionarEntregador').modal('hide');
                                        carregarPedidos();
                                    })
                                    .catch(error => {
                                        console.error('Erro ao remover entregador:', error);
                                    });
                            } else {
                                // Se for Tele Entrega, só fecha o modal e recarrega
                                $('#modalSelecionarEntregador').modal('hide');
                                carregarPedidos();
                            }
                        } else {
                            console.error('Erro ao atualizar tipo:', res.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao atualizar tipo do pedido:', error);
                    });
            }




            function abrirModalSituacao(id) {
                $('#modalSituacao').modal('show');
                document.getElementById('pedido_id_situacao').value = id;
            }


            document.getElementById('formCancelarPedido').addEventListener('submit', function(e) {
                e.preventDefault();

                const id = document.getElementById('pedido_cancelar_id').value;
                const motivo = document.getElementById('motivo_cancelamento').value;

                fetch(`/pedido/${id}/cancelar`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            motivo_cancelamento: motivo
                        })
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            $('#modalCancelar').modal('hide');
                            carregarPedidos();
                        } else {
                            alert('Erro ao cancelar pedido: ' + res.message);
                        }
                    })
                    .catch(erro => {
                        console.error('Erro ao cancelar pedido:', erro);
                    });
            });
            document.addEventListener('click', function(e) {
                if (e.target.closest('.btn-situacao')) {
                    const btn = e.target.closest('.btn-situacao');
                    const statusId = btn.dataset.id;
                    const pedidoId = document.getElementById('pedido_id_situacao').value;

                    fetch(`/pedido/${pedidoId}/alterar-situacao`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content
                            },
                            body: JSON.stringify({
                                status_pedido_id: statusId
                            })
                        })
                        .then(res => res.json())
                        .then(res => {
                            $('#modalSituacao').modal('hide');

                            // Atualiza só o botão da tabela
                            const btnTabela = document.querySelector(
                                `.alterar-situacao[data-id='${pedidoId}']`);
                            if (btnTabela) {
                                btnTabela.textContent = res.descricao;
                                btnTabela.style.backgroundColor = res.cor;
                                btnTabela.style.color = 'white';
                                btnTabela.style.border = 'none';
                            }
                            carregarPedidos();
                        })
                        .catch(erro => {
                            console.error('Erro ao alterar situação:', erro);
                        });
                }
            });
            document.addEventListener('click', function(e) {
                if (e.target.closest('.entregador-opcao')) {
                    const btn = e.target.closest('.entregador-opcao');
                    const entregadorId = btn.dataset.entregadorId;
                    const pedidoId = document.getElementById('pedido_id_entregador').value;

                    // Agora envia para o backend: atualizar entregador + mudar status para 2 (em andamento)
                    fetch(`/pedido/${pedidoId}/atribuir-entregador`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content
                            },
                            body: JSON.stringify({
                                entregador_id: entregadorId
                            })
                        })
                        .then(res => res.json())
                        .then(res => {
                            $('#modalSelecionarEntregador').modal('hide');

                            // Atualiza o botão de entregador na tabela
                            const btnTabela = document.querySelector(
                                `.selecionar-entregador[data-id='${pedidoId}']`);
                            if (btnTabela) {
                                btnTabela.outerHTML = res.entregador_nome;
                            }

                            // Atualiza o botão da situação na tabela
                            const btnSituacao = document.querySelector(
                                `.alterar-situacao[data-id='${pedidoId}']`);
                            if (btnSituacao) {
                                btnSituacao.textContent = res.status_descricao;
                                btnSituacao.style.backgroundColor = res.status_cor;
                                btnSituacao.style.color = 'white';
                                btnSituacao.style.border = 'none';
                            }
                        })
                        .catch(erro => {
                            console.error('Erro ao atribuir entregador:', erro);
                        });
                }
            });
        });
    </script>
@endsection
