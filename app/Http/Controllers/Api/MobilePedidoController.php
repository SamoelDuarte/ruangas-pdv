<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\StatusPedido;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MobilePedidoController extends Controller
{
    public function listarPedidos($usuarioId)
    {
        $pedidos = Pedido::with(['cliente', 'entregador', 'statusPedido', 'itens.produto'])
            ->where('entregador_id', $usuarioId)
            ->whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'desc')
            ->get();

        $pedidosFormatados = $pedidos->map(function ($pedido) {
            $pedido->itens = $pedido->itens->map(function ($item) {
                return [
                    'id' => $item->id,
                    'produto_id' => $item->produto_id,
                    'nome_produto' => $item->produto->nome ?? null,
                    'quantidade' => $item->quantidade,
                    'preco_unitario' => $item->preco_unitario,
                ];
            });
            return $pedido;
        });

        return response()->json($pedidosFormatados);
    }
    public function atualizarStatus(Request $request, $id)
    {
        $pedido = Pedido::find($id);
        if (!$pedido) {
            return response()->json(['erro' => 'Pedido não encontrado'], 404);
        }

        $status = $request->input('descricao');
        $motivo = $request->input('motivo');

        // Valida status permitido
        $statusPermitidos = ['aceito', 'recusado', 'cancelado', 'finalizado'];
        if (!in_array($status, $statusPermitidos)) {
            return response()->json(['erro' => 'Status inválido'], 400);
        }

        $statusId = StatusPedido::where('descricao', $status)->value('id');
        if (!$statusId) {
            return response()->json(['erro' => 'Status não encontrado'], 400);
        }

        $pedido->status_pedido_id = $statusId;

        // Se for cancelado, precisa do motivo_cancelamento
        if ($status === 'cancelado') {
            if (!$motivo) {
                return response()->json(['erro' => 'Motivo do cancelamento é obrigatório'], 422);
            }
            $pedido->motivo_cancelamento = $motivo;
        }

        // Se for recusado, precisa do motivo_reculsa
        if ($status === 'recusado') {
            if (!$motivo) {
                return response()->json(['erro' => 'Motivo da recusa é obrigatório'], 422);
            }
            $pedido->motivo_reculsa = $motivo;
        }

        $pedido->save();

        // Busca nome do entregador (relacionado ao pedido)
        $nomeEntregador = $pedido->entregador ? $pedido->entregador->nome : 'Desconhecido';

        // Monta a observação personalizada
        $observacao = null;
        if (in_array($status, ['cancelado', 'recusado'])) {
            $observacao = ucfirst($status) . " por $nomeEntregador. Motivo: $motivo";
        }

        $pedido->historicoStatus()->create([
            'status' => $statusId,
            'data' => now(),
            'observacao' => $observacao,
        ]);


        return response()->json(['sucesso' => true, 'pedido' => $pedido]);
    }
}
