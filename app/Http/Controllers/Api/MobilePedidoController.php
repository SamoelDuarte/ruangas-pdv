<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Carbon\Carbon;

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
}
