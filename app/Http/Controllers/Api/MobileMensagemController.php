<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Messagen;
use Illuminate\Http\Request;



class MobileMensagemController extends Controller
{
    public function listarMensagens($pedido_id)
    {
        // Busca mensagens pelo pedido ordenando por data
        $mensagens = Messagen::where('pedido_id', $pedido_id)->orderBy('created_at')->get();
        return response()->json($mensagens);
    }

    public function enviarMensagem(Request $request)
    {
        // Validação mínima (adicione conforme precisar)
        $data = $request->validate([
            'pedido_id' => 'required|integer',
            'device_id' => 'nullable|integer',
            'usuario_id' => 'nullable|integer',
            'texto' => 'required|string',
            'direcao' => 'required|in:enviado,recebido',
        ]);

        $mensagem = Messagen::create($data);

        return response()->json(['mensagem' => 'Mensagem enviada com sucesso', 'data' => $mensagem]);
    }
}
