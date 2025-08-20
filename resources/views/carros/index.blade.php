@extends('sistema.layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Lista de carros - Lado esquerdo -->
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-car me-2"></i>Carros Cadastrados</h4>
                    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#carroModal" onclick="limparModal()">
                        <i class="fas fa-plus"></i> Novo Carro
                    </button>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($carros->count() > 0)
                        <div class="list-group">
                            @foreach($carros as $carro)
                            <div class="list-group-item list-group-item-action carro-item" onclick="selecionarCarro({{ $carro->id }}, '{{ $carro->nome }}')">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><i class="fas fa-car me-2"></i>{{ $carro->nome }}</h6>
                                    <small>{{ $carro->created_at->format('d/m/Y') }}</small>
                                </div>
                                <div class="btn-group mt-2" role="group">
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" data-bs-target="#carroModal"
                                            onclick="event.stopPropagation(); editarCarro({{ $carro->id }}, '{{ $carro->nome }}')"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="event.stopPropagation(); confirmarExclusao({{ $carro->id }}, '{{ $carro->nome }}')"
                                            title="Deletar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-car fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhum carro cadastrado ainda</h5>
                            <p class="text-muted">Clique em "Novo Carro" para adicionar o primeiro carro.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Área à direita (abastecimentos) -->
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" id="tituloAbastecimento">
                        <i class="fas fa-gas-pump me-2"></i>Selecione um carro para ver os abastecimentos
                    </h5>
                    <button type="button" class="btn btn-light" id="btnNovoAbastecimento" data-bs-toggle="modal" data-bs-target="#abastecimentoModal" style="display: none;">
                        <i class="fas fa-plus"></i> Adicionar Abastecimento
                    </button>
                </div>
                
                <!-- Filtros de Data -->
                <div class="card-body border-bottom" id="filtrosData" style="display: none;">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="dataInicio" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Data Início
                            </label>
                            <input type="date" class="form-control" id="dataInicio">
                        </div>
                        <div class="col-md-4">
                            <label for="dataFim" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Data Fim
                            </label>
                            <input type="date" class="form-control" id="dataFim">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-primary" onclick="aplicarFiltro()">
                                <i class="fas fa-filter me-1"></i>Filtrar
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetarFiltro()">
                                <i class="fas fa-undo me-1"></i>Resetar
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card-body" id="areaAbastecimentos">
                    <div class="text-center py-5">
                        <i class="fas fa-mouse-pointer fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Clique em um carro na lista ao lado</h5>
                        <p class="text-muted">Para visualizar os abastecimentos e estatísticas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para adicionar/editar carro -->
<div class="modal fade" id="carroModal" tabindex="-1" aria-labelledby="carroModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="carroModalLabel"><i class="fas fa-car me-2"></i>Novo Carro</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="carroForm" method="POST" action="{{ route('carros.store') }}">
                @csrf
                <div id="method-field"></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome" class="form-label"><i class="fas fa-tag me-2"></i>Nome do Carro *</label>
                        <input type="text" class="form-control form-control-lg" id="nome" name="nome" 
                               placeholder="Ex: Honda Civic, Toyota Corolla..." required>
                        <div class="form-text">Digite o nome/modelo do carro</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvar">
                        <i class="fas fa-save me-1"></i>Salvar Carro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para adicionar abastecimento -->
<div class="modal fade" id="abastecimentoModal" tabindex="-1" aria-labelledby="abastecimentoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="abastecimentoModalLabel">
                    <i class="fas fa-gas-pump me-2"></i>Novo Abastecimento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="abastecimentoForm">
                @csrf
                <input type="hidden" id="carro_id" name="carro_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="litros_abastecido" class="form-label">
                                    <i class="fas fa-tint me-2"></i>Litros Abastecido *
                                </label>
                                <input type="number" class="form-control" id="litros_abastecido" name="litros_abastecido" 
                                       step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="preco_por_litro" class="form-label">
                                    <i class="fas fa-money-bill me-2"></i>Preço por Litro (R$) *
                                </label>
                                <input type="number" class="form-control" id="preco_por_litro" name="preco_por_litro" 
                                       step="0.01" min="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="km_atual" class="form-label">
                                    <i class="fas fa-tachometer-alt me-2"></i>KM Atual *
                                </label>
                                <input type="number" class="form-control" id="km_atual" name="km_atual" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_abastecimento" class="form-label">
                                    <i class="fas fa-calendar me-2"></i>Data do Abastecimento *
                                </label>
                                <input type="date" class="form-control" id="data_abastecimento" name="data_abastecimento" required>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-calculator me-2"></i>
                        <strong>Total a Pagar:</strong> 
                        <span id="totalCalculado">R$ 0,00</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Registrar Abastecimento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form oculto para deletar -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@section('scripts')
<script>
let carroSelecionadoId = null;

function limparModal() {
    document.getElementById('carroModalLabel').innerHTML = '<i class="fas fa-car me-2"></i>Novo Carro';
    document.getElementById('carroForm').action = '{{ route("carros.store") }}';
    document.getElementById('method-field').innerHTML = '';
    document.getElementById('nome').value = '';
    document.getElementById('btnSalvar').innerHTML = '<i class="fas fa-save me-1"></i>Salvar Carro';
}

function editarCarro(id, nome) {
    document.getElementById('carroModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Carro';
    document.getElementById('carroForm').action = '/carros/' + id;
    document.getElementById('method-field').innerHTML = '@method("PUT")';
    document.getElementById('nome').value = nome;
    document.getElementById('btnSalvar').innerHTML = '<i class="fas fa-save me-1"></i>Atualizar Carro';
}

function confirmarExclusao(id, nome) {
    Swal.fire({
        title: 'Confirmar Exclusão',
        text: `Tem certeza que deseja excluir o carro "${nome}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '<i class="fas fa-trash"></i> Sim, excluir!',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('deleteForm');
            form.action = '/carros/' + id;
            form.submit();
        }
    });
}

function selecionarCarro(carroId, nomeCarr) {
    carroSelecionadoId = carroId;
    
    // Remover classe ativa de todos os itens
    document.querySelectorAll('.carro-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Adicionar classe ativa ao item clicado
    event.target.closest('.carro-item').classList.add('active');
    
    // Atualizar título
    document.getElementById('tituloAbastecimento').innerHTML = 
        `<i class="fas fa-gas-pump me-2"></i>Abastecimentos - ${nomeCarr}`;
    
    // Mostrar botão de adicionar abastecimento e filtros
    document.getElementById('btnNovoAbastecimento').style.display = 'block';
    document.getElementById('filtrosData').style.display = 'block';
    
    // Configurar datas padrão (início do mês até hoje)
    const hoje = new Date();
    const inicioMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    
    document.getElementById('dataInicio').value = inicioMes.toISOString().split('T')[0];
    document.getElementById('dataFim').value = hoje.toISOString().split('T')[0];
    
    // Carregar abastecimentos
    carregarAbastecimentos(carroId);
}

function carregarAbastecimentos(carroId) {
    const area = document.getElementById('areaAbastecimentos');
    area.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Carregando...</p></div>';
    
    // Pegar datas do filtro
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;
    
    let url = `/carros/${carroId}/abastecimentos`;
    if (dataInicio && dataFim) {
        url += `?data_inicio=${dataInicio}&data_fim=${dataFim}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            renderizarAbastecimentos(data);
        })
        .catch(error => {
            area.innerHTML = '<div class="alert alert-danger">Erro ao carregar abastecimentos</div>';
            console.error('Error:', error);
        });
}

function aplicarFiltro() {
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;
    
    if (!dataInicio || !dataFim) {
        showToast('error', 'Por favor, selecione ambas as datas');
        return;
    }
    
    if (new Date(dataInicio) > new Date(dataFim)) {
        showToast('error', 'Data início não pode ser maior que data fim');
        return;
    }
    
    if (carroSelecionadoId) {
        carregarAbastecimentos(carroSelecionadoId);
    }
}

function resetarFiltro() {
    // Configurar datas padrão (início do mês até hoje)
    const hoje = new Date();
    const inicioMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    
    document.getElementById('dataInicio').value = inicioMes.toISOString().split('T')[0];
    document.getElementById('dataFim').value = hoje.toISOString().split('T')[0];
    
    if (carroSelecionadoId) {
        carregarAbastecimentos(carroSelecionadoId);
    }
}

function renderizarAbastecimentos(data) {
    const area = document.getElementById('areaAbastecimentos');
    
    if (data.abastecimentos.length === 0) {
        const dataInicioFormatada = new Date(data.periodo.data_inicio).toLocaleDateString('pt-BR');
        const dataFimFormatada = new Date(data.periodo.data_fim).toLocaleDateString('pt-BR');
        
        area.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-gas-pump fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Nenhum abastecimento encontrado</h5>
                <p class="text-muted">No período de ${dataInicioFormatada} a ${dataFimFormatada}</p>
                <p class="text-muted">Clique em "Adicionar Abastecimento" para registrar o primeiro</p>
            </div>
        `;
        return;
    }
    
    const dataInicioFormatada = new Date(data.periodo.data_inicio).toLocaleDateString('pt-BR');
    const dataFimFormatada = new Date(data.periodo.data_fim).toLocaleDateString('pt-BR');
    
    let html = `
        <!-- Período -->
        <div class="alert alert-info mb-3">
            <i class="fas fa-calendar me-2"></i>
            <strong>Período:</strong> ${dataInicioFormatada} a ${dataFimFormatada}
            <span class="badge bg-primary ms-2">${data.abastecimentos.length} registro(s)</span>
        </div>
        
        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-info text-white text-center">
                    <div class="card-body p-2">
                        <h6 class="mb-1">${data.stats.total_km_rodado.toLocaleString('pt-BR')} km</h6>
                        <small>Total KM Rodado</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-primary text-white text-center">
                    <div class="card-body p-2">
                        <h6 class="mb-1">${data.stats.total_litros_abastecido.toFixed(2)} L</h6>
                        <small>Total Litros</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning text-dark text-center">
                    <div class="card-body p-2">
                        <h6 class="mb-1">R$ ${data.stats.preco_medio_por_litro.toFixed(2)}</h6>
                        <small>Preço Médio/L</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white text-center">
                    <div class="card-body p-2">
                        <h6 class="mb-1">R$ ${data.stats.total_pago.toFixed(2)}</h6>
                        <small>Total Pago</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white text-center">
                    <div class="card-body p-2">
                        <h6 class="mb-1">R$ ${data.stats.valor_gasto_por_km.toFixed(3)}</h6>
                        <small>Gasto/KM</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-dark text-white text-center">
                    <div class="card-body p-2">
                        <h6 class="mb-1">${data.stats.media_km_por_litro.toFixed(2)} km/L</h6>
                        <small>Média KM/L</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabela de Abastecimentos -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Data</th>
                        <th>Litros</th>
                        <th>Preço/L</th>
                        <th>Total Pago</th>
                        <th>KM Atual</th>
                        <th width="80">Ações</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.abastecimentos.forEach(abast => {
        const data_formatted = new Date(abast.data_abastecimento).toLocaleDateString('pt-BR');
        const total_pago = (abast.litros_abastecido * abast.preco_por_litro);
        
        html += `
            <tr>
                <td>${data_formatted}</td>
                <td>${parseFloat(abast.litros_abastecido).toFixed(2)} L</td>
                <td>R$ ${parseFloat(abast.preco_por_litro).toFixed(2)}</td>
                <td>R$ ${total_pago.toFixed(2)}</td>
                <td>${parseFloat(abast.km_atual).toLocaleString('pt-BR')} km</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" 
                            onclick="confirmarExclusaoAbastecimento(${abast.id}, '${data_formatted}')"
                            title="Deletar registro">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    area.innerHTML = html;
}

// Preparar modal de abastecimento
document.getElementById('btnNovoAbastecimento').addEventListener('click', function() {
    document.getElementById('carro_id').value = carroSelecionadoId;
    document.getElementById('data_abastecimento').value = new Date().toISOString().split('T')[0];
});

// Calcular total automaticamente
function calcularTotal() {
    const litros = parseFloat(document.getElementById('litros_abastecido').value) || 0;
    const preco = parseFloat(document.getElementById('preco_por_litro').value) || 0;
    const total = litros * preco;
    document.getElementById('totalCalculado').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
}

document.getElementById('litros_abastecido').addEventListener('input', calcularTotal);
document.getElementById('preco_por_litro').addEventListener('input', calcularTotal);

// Enviar formulário de abastecimento
document.getElementById('abastecimentoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/abastecimentos', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fechar modal
            bootstrap.Modal.getInstance(document.getElementById('abastecimentoModal')).hide();
            
            // Limpar formulário
            document.getElementById('abastecimentoForm').reset();
            
            // Recarregar abastecimentos com filtro atual
            carregarAbastecimentos(carroSelecionadoId);
            
            // Mostrar mensagem de sucesso
            showToast('success', data.message);
        }
    })
    .catch(error => {
        showToast('error', 'Erro ao registrar abastecimento');
        console.error('Error:', error);
    });
});

// Validação do formulário de carro
document.getElementById('carroForm').addEventListener('submit', function(e) {
    const nome = document.getElementById('nome').value.trim();
    if (nome.length < 2) {
        e.preventDefault();
        showToast('error', 'O nome do carro deve ter pelo menos 2 caracteres');
        return false;
    }
});

// Função para confirmar exclusão de abastecimento
function confirmarExclusaoAbastecimento(abastecimentoId, dataAbastecimento) {
    Swal.fire({
        title: 'Confirmar Exclusão',
        text: `Tem certeza que deseja excluir o abastecimento do dia ${dataAbastecimento}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '<i class="fas fa-trash"></i> Sim, excluir!',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            deletarAbastecimento(abastecimentoId);
        }
    });
}

// Função para deletar abastecimento
function deletarAbastecimento(abastecimentoId) {
    fetch(`/abastecimentos/${abastecimentoId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recarregar abastecimentos com filtro atual
            carregarAbastecimentos(carroSelecionadoId);
            
            // Mostrar mensagem de sucesso
            showToast('success', data.message);
        }
    })
    .catch(error => {
        showToast('error', 'Erro ao excluir abastecimento');
        console.error('Error:', error);
    });
}
</script>

<style>
.carro-item {
    cursor: pointer;
    transition: all 0.3s;
}
.carro-item:hover {
    background-color: #f8f9fa;
}
.carro-item.active {
    background-color: #007bff;
    color: white;
}
.carro-item.active .btn {
    border-color: white;
}
</style>
@endsection
