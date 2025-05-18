<!-- Modal Selecionar Entregador -->
<div class="modal fade" id="modalEntregador" tabindex="-1" aria-labelledby="modalEntregadorLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEntregadorLabel">Selecionar Entregador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <select id="selectEntregador" class="form-select">
                    <!-- Entregadores serão inseridos via JS -->
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarEntregador">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mudar Situação -->
<div class="modal fade" id="modalSituacao" tabindex="-1" aria-labelledby="modalSituacaoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSituacaoLabel">Alterar Situação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pedido_id_situacao">
                <div class="d-flex flex-wrap gap-2" id="botoesSituacao">
                    @foreach ($situacoes as $situacao)
                        @if (strtolower($situacao->descricao) != 'cancelado')
                            <button class="btn btn-sm text-white btn-situacao"
                                style="background-color: {{ $situacao->cor }}" data-id="{{ $situacao->id }}">
                                {{ ucfirst($situacao->descricao) }}
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>





<!-- Modal Cancelar Pedido -->
<div class="modal fade" id="modalCancelar" tabindex="-1" aria-labelledby="modalCancelarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formCancelarPedido">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCancelarLabel">Cancelar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="motivo_cancelamento" class="form-label">Motivo do Cancelamento</label>
                        <textarea class="form-control" id="motivo_cancelamento" rows="3" required></textarea>
                        <input type="hidden" id="pedido_cancelar_id">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-danger">Cancelar Pedido</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalSelecionarEntregador" tabindex="-1" aria-labelledby="modalSelecionarEntregadorLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Selecionar Entregador ou Tipo de Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="lista-entregadores" class="mb-3 d-flex flex-wrap gap-2 flex-column">
                    <!-- Botões dos entregadores + Select dos tipos serão inseridos aqui -->
                </div>

                <input type="hidden" id="pedido_id_entregador" value="">
            </div>
        </div>
    </div>
</div>


<input type="hidden" id="pedido_id_entregador">
