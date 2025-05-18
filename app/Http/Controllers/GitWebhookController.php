<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GitWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Opcional: validar segredo do webhook para segurança
        // $secret = env('GIT_WEBHOOK_SECRET');
        // $signature = 'sha1=' . hash_hmac('sha1', $request->getContent(), $secret);
        // if (!hash_equals($signature, $request->header('X-Hub-Signature'))) {
        //     return response('Unauthorized', 401);
        // }

        // Executar o git pull
        $output = null;
        $returnVar = null;
        
        // Caminho da pasta do seu projeto
        $projectPath = base_path(); // normalmente a raiz do Laravel

        // Comando para atualizar o repositório
        exec("cd {$projectPath} && git pull origin main 2>&1", $output, $returnVar);

        if ($returnVar !== 0) {
            return response()->json([
                'status' => 'error',
                'output' => $output
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'output' => $output
        ]);
    }
}
