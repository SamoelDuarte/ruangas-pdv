<?php

use App\Http\Controllers\Controller;
use App\Models\Entregador;
use Illuminate\Http\Request;

class MobileUsuarioController extends Controller
{
    public function verificaUsuario($usuario_id)
    {
        $usuario = Entregador::find($usuario_id);

        if (! $usuario) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        return response()->json([
            'message' => 'Usuário válido',
            'user' => $usuario
        ], 200);
    }
}
