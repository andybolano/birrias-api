<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Ruta personalizada para la documentación de la API
Route::get('/api/documentation', function () {
    return redirect('/documentation');
});

Route::get('/docs', function () {
    // Depurar qué está llegando
    $query = request()->query();
    $uri = request()->getRequestUri();
    
    // Si la URL contiene api-docs.json, servir el archivo JSON
    if (str_contains($uri, 'api-docs.json') || request()->has('api-docs.json') || isset($query['api-docs.json'])) {
        $path = storage_path('api-docs/api-docs.json');
        if (file_exists($path)) {
            return response()->file($path, [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET',
                'Access-Control-Allow-Headers' => 'Content-Type'
            ]);
        }
        return response()->json(['error' => 'Documentation not found'], 404);
    }
    return redirect('/documentation');
})->name('l5-swagger.default.docs');

// Ruta de prueba
Route::get('/test', function () {
    return response()->json(['message' => 'Test route works!']);
});

// Ruta personalizada para servir el archivo JSON de Swagger
Route::get('/docs/api-docs.json', function () {
    $path = storage_path('api-docs/api-docs.json');
    if (file_exists($path)) {
        return response()->file($path, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET',
            'Access-Control-Allow-Headers' => 'Content-Type'
        ]);
    }
    return response()->json(['error' => 'Documentation not found'], 404);
})->name('swagger.docs.json');

// Auth routes are now included in API routes only
