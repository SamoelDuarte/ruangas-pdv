<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileClienteController extends Controller
{


    public function getCliente(Request $request): JsonResponse
    {
        $telefone = $request->query('telefone');
        if (!$telefone) {
            return response()->json(['erro' => 'Telefone não informado'], 400);
        }

        // Limpa o telefone (só números)
        $telefoneLimpo = preg_replace('/\D/', '', $telefone);

        // Codifica com base64
        $telefoneCodificado = base64_encode($telefoneLimpo);

        // Busca no banco
        $cliente = Cliente::where('telefone', $telefoneCodificado)->first();

        if (!$cliente) {
            return response()->json(['erro' => 'Cliente não encontrado'], 404);
        }

        return response()->json($cliente);
    }
}
