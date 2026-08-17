<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [ImportController::class, 'dashboardView'])->name('dashboard');
Route::get('/upload', [ImportController::class, 'uploadView'])->name('upload');
Route::get('/imports/{id}', [ReportController::class, 'detailView'])->name('imports.detail');
