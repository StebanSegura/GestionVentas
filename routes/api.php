<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/imports', [ImportController::class, 'index']);
Route::post('/imports', [ImportController::class, 'store']);
Route::get('/imports/{id}/errors', [ImportController::class, 'errors']);
Route::delete('/imports/{id}', [ImportController::class, 'destroy']);

Route::get('/reports/summary', [ReportController::class, 'summary']);
