<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class DocsController extends Controller
{
    public function swaggerUi()
    {
        return view('docs.swagger');
    }

    public function openapi()
    {
        $path = base_path('docs/openapi.yaml');

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'application/yaml; charset=UTF-8',
        ]);
    }
}

