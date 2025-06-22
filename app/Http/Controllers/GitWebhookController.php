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

        $cmd = "cd {$projectPath} && /usr/bin/git reset --hard && /usr/bin/git clean -fd && /usr/bin/git pull origin main 2>&1";
        exec($cmd, $output, $returnVar);


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
