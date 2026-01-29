<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ai-analysis/{analysis}/pdf', [\App\Http\Controllers\AiAnalysisController::class, 'downloadPdf'])
    ->name('ai-analysis.pdf');
