<?php



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Endpoint autenticado para devolver o utilizador da API atual.
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



