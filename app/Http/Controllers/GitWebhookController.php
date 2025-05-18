<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GitWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $output = null;
        $returnVar = null;
        
        $projectPath = base_path();

        exec("cd {$projectPath} && git reset --hard && git clean -fd && git pull origin main 2>&1", $output, $returnVar);

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
