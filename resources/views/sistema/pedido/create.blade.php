@extends('sistema.layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="container mt-4">
            <div class="page-header-content py-3">
                <h1 class="h3 mb-0 text-gray-800">Novo Pedido</h1>
            </div>
    
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('pedido.store') }}" id="formPedido" method="POST">
                        @csrf
                        <div class="row">
                            <!-- Coluna 1 -->
                            <div class="col-md-3">
                                <div class="mb-3 position-relative">
                                    <label for="telefone" class="form-label">Telefone do Cliente</label>
                                    <input type="text" class="form-control" id="telefone" name="telefone" autocomplete="off"
                                        required oninvalid="this.setCustomValidity('O campo Telefone deve ser preenchido.')"
                                        oninput="this.setCustomValidity('')">
    
                                    <div id="sugestoes-clientes" class="list-group position-absolute w-100 d-none"
                                        style="z-index: 1000;"></div>
                                </div>
    
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome</label>
                                    <input type="text" class="form-control" id="nome" name="nome">
                                </div>
    
                                <div class="mb-3">
                                    <label for="logradouro" class="form-label">Logradouro</label>
                                    <input type="text" class="form-control" id="logradouro" name="logradouro"
                                        autocomplete="off" required
                                        oninvalid="this.setCustomValidity('O campo Logradouro deve ser preenchido.')"
                                        oninput="this.setCustomValidity('')">
                                </div>
    
                            </div>
                            <!-- Coluna 2 -->
                            <div class="col-md-3">
    
                                <div class="mb-3">
                                    <label for="numero" class="form-label">Número</label>
                                    <!-- Número -->
                                    <input type="text" class="form-control" id="numero" name="numero" required
                                        oninvalid="this.setCustomValidity('O campo Número deve ser preenchido.')"
                                        oninput="this.setCustomValidity('')">
                                </div>
                                <div class="mb-3">
                                    <label for="cep" class="form-label">CEP</label>
                                    <input type="text" class="form-control" id="cep" name="cep">
                                </div>
    
                                <div class="mb-3">
                                    <label for="cidade" class="form-label">Cidade</label>
                                    <input type="text" class="form-control" id="cidade" name="cidade">
                                </div>
    
    
                            </div>
                            <!-- Coluna 3 -->
                            <div class="col-md-3">
    
                                <div class="mb-3">
                                    <label for="referencia" class="form-label">Ponto de Referência</label>
                                    <input type="text" class="form-control" id="referencia" name="referencia">
                                </div>
    
                                <div class="mb-3">
                                    <label for="bairro" class="form-label">Bairro</label>
                                    <input type="text" class="form-control" id="bairro" name="bairro">
                                </div>
    
                                <div class="mb-3">
                                    <label for="complemento" class="form-label">Complemento</label>
                                    <input type="text" class="form-control" id="complemento" name="complemento">
                                </div>
    
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                    <input type="date" class="form-control" id="data_nascimento" name="data_nascimento">
                                </div>
                                <div class="mb-3">
                                    <label for="observacao" class="form-label">Observação</label>
                                    <textarea class="form-control" id="observacao" name="observacao" rows="4"></textarea>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4">
    
                        <div class="row g-2">
                            <div class="col-md-7">
                                <div class="row">
                                    <!-- Código -->
                                    <div class="col-md-1">
                                        <label class="form-label">Código</label>
                                        <input type="text" class="form-control" id="codigo_produto">
                                    </div>
    
                                    <!-- Nome Produto -->
                                    <div class="col-md-3">
                                        <label class="form-label">Produto</label>
                                        <input type="text" class="form-control" id="nome_produto" readonly>
                                    </div>
    
                                    <!-- Quantidade -->
                                    <div class="col-md-2">
                                        <label class="form-label">Qtd.</label>
                                        <input type="number" class="form-control" id="quantidade_produto" value="1"
                                            min="1">
                                    </div>
    
                                    <!-- Valor Unitário -->
                                    <div class="col-md-2">
                                        <label class="form-label">Valor (R$)</label>
                                        <input type="text" class="form-control" id="valor_produto" step="0.01">
                                    </div>
    
                                    <!-- Total -->
                                    <div class="col-md-2">
                                        <label class="form-label">Total</label>
                                        <input type="text" class="form-control" id="total_produto" readonly>
                                    </div>
    
                                    <!-- Botão Adicionar Produto -->
                                    <div class="col-md-1 align-items-center d-flex">
                                        <button type="button" class="btn btn-success" id="btnAdicionarProduto">+</button>
                                    </div>
                                    <!-- Lista de produtos adicionados -->
                                    <div class="row g-2 mt-3" id="lista_produtos"></div>
                                </div>
                                <hr class="my-4">
    
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label for="tipo_pedido" class="form-label">Tipo de Pedido</label>
                                        <select class="form-select" id="tipo_pedido" name="tipo_pedido" required>
                                            <option value="tele_entrega">Tele Entrega</option>
                                            <option value="automatico">Automático</option>
                                            <option value="pdv">PDV</option>
                                            <option value="portaria">Portaria</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="mensagem" class="form-label">Mensagem</label>
                                        <textarea class="form-control" id="mensagem" name="mensagem" rows="2"
                                            placeholder="Alguma observação sobre o pedido..."></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="row">
                                    <!-- Select Forma Pagamento -->
                                    <div class="col-md-6">
                                        <label class="form-label">Forma de Pagamento</label>
                                        <select class="form-select" id="forma_pagamento">
                                            @foreach ($formasPagamento as $forma)
                                                <option value="{{ $forma->id }}">{{ $forma->descricao }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!-- Valor Pagamento -->
                                    <div class="col-md-4">
                                        <label class="form-label">Valor (R$)</label>
                                        <input type="number" class="form-control" id="valor_pagamento" step="0.01">
                                    </div>
                                    <!-- Botão Adicionar Pagamento -->
                                    <div class="col-md-1 align-items-center d-flex">
                                        <button type="button" class="btn btn-primary" id="btnAdicionarPagamento">+</button>
                                    </div>
                                    <!-- Lista de formas de pagamento -->
                                    <div class="row g-2 mt-3" id="lista_pagamentos"></div>
                                </div>
                                <div class="d-flex flex-column justify-content-end align-items-end">
                                    <!-- Total Geral -->
                                    <div class="mt-4">
                                        <h5>Total Geral: R$ <span id="total_geral">0.00</span></h5>
                                        <h6 class="text-muted">Falta pagar: R$ <span id="total_falta">0.00</span></h6>
                                        <h6>Total Pago: R$ <span id="total_pago">0.00</span></h6>
                                        <!-- Novo campo para mostrar o troco -->
                                        <h6 class="text-success"><span id="troco"></span></h6>
                                    </div>
    
                                    <div id="inputs_ocultos"></div> <!-- aqui os JS vão inserir os hidden inputs -->
                                    <input type="hidden" name="total" id="total_geral_input">
    
    
                                    <button type="submit" class="btn btn-primary" id="salvar-pedido">Salvar Pedido</button>
                                </div>
                            </div>
    
    
                        </div>
    
                    </form>
                </div>
            </div>
        </div>
    
        <footer class="footer-shortcut bg-light border-top py-2 px-3 fixed-bottom shadow-sm">
            <div class="d-flex justify-content-between align-items-center small text-muted">
                <div>
                    <strong>Atalhos:</strong>
                    <span class="ms-3">[ Alt + T - Telefone ] </span>
                    <span class="ms-3">[ Alt + P - Produto ] </span> <!-- "U" de "produto" (já que P tá em uso) -->
                    <span class="ms-3">[ Alt + M - Pagamento ] </span> <!-- "M" de "forma de pagamento" -->
                    <span class="ms-3">[ Alt + R - Rua ] </span> <!-- "M" de "forma de pagamento" -->
                    <span class="ms-3">[ Alt + S - Salvar Pedido ] </span> <!-- "M" de "forma de pagamento" -->
    
                </div>
                <div>
                    <span>Pressione os atalhos para facilitar o uso</span>
                </div>
            </div>
        </footer>
    </div>
</div>
    
@endsection


@section('scripts')
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBjtRzX47y95pI2XlmJrsXgka8SHSMLtQw&libraries=places">
    </script>
    {{-- atalhos --}}
    <script>
        document.addEventListener('keydown', function(e) {
            // Alt + T → Focar Telefone
            if (e.altKey && e.key.toLowerCase() === 't') {
                e.preventDefault();
                document.getElementById('telefone')?.focus();
            }

            // Alt + D → Focar campo Código Produto
            if (e.altKey && e.key.toLowerCase() === 'p') {
                e.preventDefault();
                document.getElementById('codigo_produto')?.focus();
            }

            // Alt + M → Focar forma de pagamento
            if (e.altKey && e.key.toLowerCase() === 'm') {
                e.preventDefault();
                document.getElementById('forma_pagamento')?.focus();
            }
            // Alt + M → Focar forma de pagamento
            if (e.altKey && e.key.toLowerCase() === 'r') {
                e.preventDefault();
                document.getElementById('logradouro')?.focus();
            }

            // Alt + M → Focar forma de pagamento
            if (e.altKey && e.key.toLowerCase() === 's') {
                e.preventDefault();
                document.getElementById('salvar-pedido')?.click();
            }
        });
    </script>
    <script>
        document.getElementById('valor_produto')?.addEventListener('blur', function() {
            // Espera um pouquinho pra garantir que os outros valores estejam atualizados
            setTimeout(() => {
                document.getElementById('btnAdicionarProduto')?.click();
            }, 100);
        });

        document.getElementById('valor_pagamento')?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // impede o form de tentar ser enviado
                document.getElementById('btnAdicionarPagamento')?.click();
            }
        });
    </script>

    <script src="{{ asset('/assets/admin/js/utils.js') }}"></script>
    <script>
        const quantidadeInput = document.getElementById('quantidade_produto');
        const valorUnitarioInput = document.getElementById('valor_produto');
        const totalProdutoInput = document.getElementById('total_produto');

        function atualizarTotalProduto() {
            const quantidade = parseFloat(quantidadeInput.value) || 0;
            const valorStr = valorUnitarioInput.value.trim();

            const valorUnitario = parseFloat(valorStr) || 0;

            const total = quantidade * valorUnitario;

            totalProdutoInput.value = total;
        }

        // Eventos: quando mudar ou digitar
        quantidadeInput.addEventListener('input', atualizarTotalProduto);
        valorUnitarioInput.addEventListener('input', atualizarTotalProduto);
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let produtos = [];
            let pagamentos = [];

            // Seletores
            const btnAdicionarProduto = document.getElementById('btnAdicionarProduto');
            const btnAdicionarPagamento = document.getElementById('btnAdicionarPagamento');
            const listaProdutos = document.getElementById('lista_produtos');
            const listaPagamentos = document.getElementById('lista_pagamentos');
            const totalGeral = document.getElementById('total_geral');
            const totalFalta = document.getElementById('total_falta');
            const totalPago = document.getElementById('total_pago');
            const trocoDisplay = document.getElementById('troco'); // Exibição de troco

            // Função para atualizar os totais
            function atualizarTotais() {
                let totalProdutos = 0;
                let totalPagamentos = 0;
                let troco = 0;

                // Somar os totais dos produtos
                produtos.forEach(produto => {
                    totalProdutos += produto.total;
                });

                // Somar os totais dos pagamentos
                pagamentos.forEach(pagamento => {
                    totalPagamentos += pagamento.valor;
                });

                let falta = totalProdutos - totalPagamentos;

                // Cálculo de troco inteligente
                let valorPagoEmDinheiro = 0;

                pagamentos.forEach(pagamento => {
                    if (pagamento.formaPagamento === "1") { // 1 = dinheiro
                        valorPagoEmDinheiro += pagamento.valor;
                    }
                });

                let valorRestanteSemDinheiro = totalProdutos - (totalPagamentos - valorPagoEmDinheiro);

                if (valorPagoEmDinheiro > valorRestanteSemDinheiro) {
                    troco = valorPagoEmDinheiro - valorRestanteSemDinheiro;
                } else {
                    troco = 0;
                }

                // Atualizar os totais
                totalGeral.textContent = totalProdutos.toFixed(2);
                totalPago.textContent = totalPagamentos.toFixed(2);
                totalFalta.textContent = falta > 0 ? falta.toFixed(2) : '0.00';

                // Exibir troco apenas se houver
                if (troco > 0) {
                    trocoDisplay.textContent = `Troco: R$ ${troco.toFixed(2)}`;
                } else {
                    trocoDisplay.textContent = '';
                }

                // ⚠️ Atualizar o valor no campo de pagamento
                document.getElementById('valor_pagamento').value = falta > 0 ? falta.toFixed(2) : '';
                document.getElementById('total_geral_input').value = totalProdutos.toFixed(2);

            }



            // Função para atualizar a lista de produtos
            function atualizarListaProdutos() {
                listaProdutos.innerHTML = '';

                produtos.forEach((produto, index) => {
                    let row = document.createElement('div');
                    row.classList.add('row', 'g-2', 'align-items-center');

                    row.innerHTML = `
            <div class="col-md-1"><span>${produto.codigo}</span><input type="hidden" value="${produto.codigo}"></div>
            <div class="col-md-3"><span>${produto.nome}</span><input type="hidden" value="${produto.nome}"></div>
            <div class="col-md-2"><span>${produto.quantidade}</span><input type="hidden" value="${produto.quantidade}"></div>
            <div class="col-md-2"><span>${produto.valorUnitario.toFixed(2)}</span><input type="hidden" value="${produto.valorUnitario.toFixed(2)}"></div>
            <div class="col-md-2"><span>${produto.total.toFixed(2)}</span><input type="hidden" value="${produto.total.toFixed(2)}"></div>
            <div class="col-md-1"><button class="btn btn-danger" onclick="removerProduto(${index})">-</button></div>
            `;

                    listaProdutos.appendChild(row);
                });

                // Atualizar totais
                atualizarTotais();
            }

            // Função para adicionar produto
            btnAdicionarProduto.addEventListener('click', function() {
                const codigo = document.getElementById('codigo_produto').value;
                const nome = document.getElementById('nome_produto').value;
                const quantidade = parseFloat(document.getElementById('quantidade_produto').value);
                const valorUnitario = parseFloat(document.getElementById('valor_produto').value);

                if (codigo && nome && quantidade > 0 && valorUnitario > 0) {
                    const total = quantidade * valorUnitario;

                    produtos.push({
                        codigo: codigo,
                        nome: nome,
                        quantidade: quantidade,
                        valorUnitario: valorUnitario,
                        total: total
                    });

                    // Limpar campos
                    document.getElementById('codigo_produto').value = '';
                    document.getElementById('nome_produto').value = '';
                    document.getElementById('quantidade_produto').value = 1;
                    document.getElementById('valor_produto').value = '';
                    document.getElementById('total_produto').value = '';

                    // Atualizar a lista de produtos
                    atualizarListaProdutos();
                } else {
                    alert('Por favor, preencha todos os campos corretamente.');
                }
            });

            // Função para remover produto
            window.removerProduto = function(index) {
                produtos.splice(index, 1);
                atualizarListaProdutos();
            }

            // Função para adicionar pagamento
            btnAdicionarPagamento.addEventListener('click', function() {
                const formaPagamento = document.getElementById('forma_pagamento').value;
                const valorPagamento = parseFloat(document.getElementById('valor_pagamento').value);
                const nomeFormaPagamento = document.getElementById('forma_pagamento').options[document
                    .getElementById('forma_pagamento').selectedIndex].text;

                if (valorPagamento > 0) {
                    pagamentos.push({
                        formaPagamento: formaPagamento,
                        nomeFormaPagamento: nomeFormaPagamento,
                        valor: valorPagamento
                    });
                }

                // Limpar campos de pagamento
                // document.getElementById('forma_pagamento').value = '';
                document.getElementById('valor_pagamento').value = '';

                // Atualizar lista de pagamentos
                atualizarListaPagamentos();
            });

            // Função para atualizar a lista de pagamentos
            function atualizarListaPagamentos() {
                listaPagamentos.innerHTML = '';

                pagamentos.forEach((pagamento, index) => {
                    let row = document.createElement('div');
                    row.classList.add('row', 'g-2', 'align-items-center');

                    row.innerHTML = `
            <div class="col-md-7"><span>${pagamento.nomeFormaPagamento}</span><input type="hidden" value="${pagamento.formaPagamento}"></div>
            <div class="col-md-2"><span>${pagamento.valor.toFixed(2)}</span><input type="hidden" value="${pagamento.valor.toFixed(2)}"></div>
            <div class="col-md-1"><button class="btn btn-danger" onclick="removerPagamento(${index})">-</button></div>
            `;

                    listaPagamentos.appendChild(row);
                });

                // Atualizar totais
                atualizarTotais();
            }

            // Função para remover pagamento
            window.removerPagamento = function(index) {
                pagamentos.splice(index, 1);
                atualizarListaPagamentos();
            }

            function gerarInputsOcultos() {
                const container = document.getElementById('inputs_ocultos');
                container.innerHTML = ''; // limpa os inputs anteriores

                // Produtos
                produtos.forEach((produto, i) => {
                    for (const chave in produto) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `produtos[${i}][${chave}]`;
                        input.value = produto[chave];
                        container.appendChild(input);
                    }
                });

                // Pagamentos
                pagamentos.forEach((pagamento, i) => {
                    for (const chave in pagamento) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `pagamentos[${i}][${chave}]`;
                        input.value = pagamento[chave];
                        container.appendChild(input);
                    }
                });
            }


            // Impede envio do formulário sem produtos ou pagamentos
            const form = document.getElementById('formPedido'); // <--- ajuste o ID se necessário

            form.addEventListener('submit', function(e) {
                if (produtos.length === 0) {
                    e.preventDefault();
                    alert('Adicione pelo menos um produto antes de salvar o pedido.');
                    return;
                }

                if (pagamentos.length === 0) {
                    e.preventDefault();
                    alert('Adicione pelo menos uma forma de pagamento antes de salvar o pedido.');
                    return;
                }
                gerarInputsOcultos();
            });

        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initGoogleAutocomplete('#logradouro', {
                logradouro: '#logradouro',
                bairro: '#bairro',
                cidade: '#cidade',
                estado: '#estado',
                cep: '#cep',
                numero: '#numero'
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const telefoneInput = document.getElementById('telefone');
            const sugestoesBox = document.getElementById('sugestoes-clientes');
            let sugestoes = [];
            let indexSelecionado = -1;
            let ultimoTelefoneBuscado = '';

            function preencherCampos(cliente) {
                telefoneInput.value = cliente.telefone;
                document.getElementById('nome').value = cliente.nome ?? '';
                document.getElementById('logradouro').value = cliente.logradouro ?? '';
                document.getElementById('numero').value = cliente.numero ?? '';
                document.getElementById('cep').value = cliente.cep ?? '';
                document.getElementById('bairro').value = cliente.bairro ?? '';
                document.getElementById('cidade').value = cliente.cidade ?? '';
                document.getElementById('complemento').value = cliente.complemento ?? '';
                document.getElementById('referencia').value = cliente.referencia ?? '';
                document.getElementById('data_nascimento').value = cliente.data_nascimento ?? '';
            }

            function buscarTelefone(valor) {
                if (valor.length < 8 || valor === ultimoTelefoneBuscado) return;

                ultimoTelefoneBuscado = valor;

                fetch(`/clientes/buscar-por-telefone?telefone=${encodeURIComponent(valor)}`)
                    .then(res => res.json())
                    .then(data => {
                        sugestoes = data;
                        sugestoesBox.innerHTML = '';
                        indexSelecionado = -1;

                        if (data.length === 1 && data[0].telefone.replace(/\D/g, '') === valor.replace(/\D/g,
                                '')) {
                            preencherCampos(data[0]);
                            sugestoesBox.classList.add('d-none');
                        } else if (data.length) {
                            data.forEach((cliente, index) => {
                                const item = document.createElement('a');
                                item.href = '#';
                                item.classList.add('list-group-item', 'list-group-item-action');
                                item.textContent =
                                    `${cliente.telefone} - ${cliente.nome} - ${cliente.logradouro ?? ''}`;
                                item.dataset.index = index;

                                item.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    preencherCampos(cliente);
                                    sugestoesBox.classList.add('d-none');
                                });

                                sugestoesBox.appendChild(item);
                            });
                            sugestoesBox.classList.remove('d-none');
                        } else {
                            sugestoesBox.classList.add('d-none');
                        }
                    });
            }

            telefoneInput.addEventListener('input', function() {
                const valor = telefoneInput.value.trim();
                buscarTelefone(valor);
            });

            telefoneInput.addEventListener('keydown', function(e) {
                const itens = sugestoesBox.querySelectorAll('.list-group-item');

                if (e.key === 'ArrowDown') {
                    if (indexSelecionado < itens.length - 1) {
                        indexSelecionado++;
                        itens.forEach(item => item.classList.remove('active'));
                        itens[indexSelecionado].classList.add('active');
                    }
                }

                if (e.key === 'ArrowUp') {
                    if (indexSelecionado > 0) {
                        indexSelecionado--;
                        itens.forEach(item => item.classList.remove('active'));
                        itens[indexSelecionado].classList.add('active');
                    }
                }

                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (indexSelecionado >= 0 && sugestoes[indexSelecionado]) {
                        preencherCampos(sugestoes[indexSelecionado]);
                        sugestoesBox.classList.add('d-none');
                    }
                }
            });
        });
    </script>
    <script>
        const codigoInput = document.getElementById('codigo_produto');

        function buscarProdutoPorCodigo(codigo) {
            if (!codigo) return;

            fetch(`/produtos/buscar/${codigo}`) // Ajusta a rota conforme sua aplicação
                .then(response => {
                    if (!response.ok) throw new Error("Produto não encontrado");
                    return response.json();
                })
                .then(produto => {
                    document.getElementById('nome_produto').value = produto.nome;
                    document.getElementById('valor_produto').value = parseFloat(produto.valor).toFixed(2);

                    const quantidade = parseFloat(document.getElementById('quantidade_produto').value) || 1;
                    const total = quantidade * parseFloat(produto.valor);

                    document.getElementById('total_produto').value = total.toFixed(2);
                })
                .catch(error => {
                    alert("Produto não encontrado.");
                    document.getElementById('nome_produto').value = '';
                    document.getElementById('valor_produto').value = '';
                    document.getElementById('total_produto').value = '';
                });
        }

        // Busca ao apertar Enter
        codigoInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarProdutoPorCodigo(this.value);
            }
        });

        // Busca ao sair do campo (blur)
        codigoInput.addEventListener('blur', function() {
            buscarProdutoPorCodigo(this.value);
        });
    </script>
@endsection
