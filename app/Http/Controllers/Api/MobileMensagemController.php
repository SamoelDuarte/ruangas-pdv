<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Messagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $data = $request->validate([
            'pedido_id' => 'required|integer',
            'device_id' => 'nullable|integer',
            'usuario_id' => 'nullable|integer',
            'messagem' => 'required|string',
            'direcao' => 'required|in:enviado,recebido',
        ]);

        // Verifica se já tem mensagens com esse pedido
        $mensagensExistentes = Messagen::where('pedido_id', $data['pedido_id'])->exists();

        // Se for a primeira mensagem, seleciona um device aleatório
        if (!$mensagensExistentes) {
            $device = Device::inRandomOrder()->where('status', 'open')->first(); // pega um ativo aleatório
            if ($device) {
                $data['device_id'] = $device->id;
            }
        }

        // Cria a mensagem
        $mensagem = Messagen::create($data);

        return response()->json(['mensagem' => 'Mensagem enviada com sucesso', 'data' => $mensagem]);
    }
}
