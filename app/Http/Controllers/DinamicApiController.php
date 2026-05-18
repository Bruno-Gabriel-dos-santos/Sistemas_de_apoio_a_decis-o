<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DinamicApiController extends Controller
{
    public function executarApiPost(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return response()->json([
                'error' => 'Campo "id" é obrigatório para direcionar a API.',
            ], 422);
        }

        $input = $request->all();
        $caminho = storage_path("app/sistemas/{$id}/start.php");

        if (!file_exists($caminho)) {
            return response()->json([
                'error' => 'API não configurada para este ID.',
                'id' => $id,
            ], 404);
        }

        return include $caminho;
    }
}
