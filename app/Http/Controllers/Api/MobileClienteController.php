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
        $telefone = $request->input('telefone');
    
        if (!$telefone) {
            return response()->json(['erro' => 'Telefone não informado'], 400);
        }
   
        $cliente = Cliente::all()->filter(function ($c) use ($telefone) {
            return base64_decode($c->telefone) === preg_replace('/\D/', '', $telefone);
        })->first();
 dd($cliente);
        if (!$cliente) {
            return response()->json(['erro' => 'Cliente não encontrado'], 404);
        }

        return response()->json($cliente);
    }
}
