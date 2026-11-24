<?php
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BusinessController::class, 'index'])->name('dashboard');
Route::get('/businesses/create', [BusinessController::class, 'create'])->name('businesses.create');
Route::post('/businesses', [BusinessController::class, 'store'])->name('businesses.store');
Route::get('/businesses/{business}/ingest', [BusinessController::class, 'ingest'])->name('businesses.ingest');
Route::post('/businesses/{business}/ingest', [BusinessController::class, 'ingest'])->name('businesses.ingest.post');
Route::get('/businesses/{business}/review', [BusinessController::class, 'review'])->name('businesses.review');
Route::post('/businesses/{business}/pick-candidate', [BusinessController::class, 'pickCandidate'])->name('businesses.pick-candidate');
Route::post('/businesses/{business}/resolve-conflict', [BusinessController::class, 'resolveConflict'])->name('businesses.resolve-conflict');
Route::post('/businesses/{business}/answer-question', [BusinessController::class, 'answerQuestion'])->name('businesses.answer-question');
Route::post('/businesses/{business}/confirm-facts', [BusinessController::class, 'confirmFacts'])->name('businesses.confirm-facts');
Route::post('/businesses/{business}/manual-source', [BusinessController::class, 'addManualSource'])->name('businesses.manual-source');
Route::get('/businesses/{business}/generate', [SiteController::class, 'generate'])->name('businesses.generate');
Route::post('/businesses/{business}/generate', [SiteController::class, 'generate'])->name('businesses.generate.post');

Route::get('/sites/{site}/edit', [SiteController::class, 'edit'])->name('sites.edit');
Route::post('/sites/{site}/sections/{section}', [SiteController::class, 'updateSection'])->name('sites.sections.update');
Route::post('/sites/{site}/sections/{section}/regenerate', [SiteController::class, 'regenerateSection'])->name('sites.sections.regenerate');
Route::post('/sites/{site}/publish', [SiteController::class, 'publish'])->name('sites.publish');
Route::get('/sites/{site}/preview', [SiteController::class, 'preview'])->name('sites.preview');
Route::get('/sites/{slug}', [SiteController::class, 'public'])->name('sites.public');
