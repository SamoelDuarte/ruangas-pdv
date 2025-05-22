<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;

class MobilePedidoController extends Controller
{
    public function listarPedidos($usuarioId)
    {
        $pedidos = Pedido::with(['cliente', 'entregador', 'statusPedido'])
            ->where('cliente_id', $usuarioId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pedidos);
    }
}
