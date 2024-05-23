<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\TrolleyController;

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

/* Books Route */
Route::get('books', [BookController::class, 'index']);
Route::get('books/export/pdf', [BookController::class, 'exportToPdf'])->name('book.export.pdf');
Route::post('books/import/excel', [BookController::class, 'importFromExcel'])->name('book.import.excel');
Route::post('books', [BookController::class, 'store'])->name('book.store');
Route::get('books/{slug}', [BookController::class, 'detail'])->name('book.detail');
Route::put('books/{id}', [BookController::class, 'update'])->name('book.update');
Route::delete('books/{id}', [BookController::class, 'destroy'])->name('book.destroy');

/* Genres Route */
Route::get('genres', [GenreController::class, 'index']);
Route::post('genres', [GenreController::class, 'store'])->name('genres.store');
Route::get('genres/{slug}', [GenreController::class, 'detail'])->name('genres.detail');
Route::put('genres/{id}', [GenreController::class, 'update'])->name('update');
Route::delete('genres/{id}', [GenreController::class, 'destroy'])->name('destroy');

/* Product Route */
Route::get('products', [ProductController::class, 'index']);
Route::post('products', [ProductController::class, 'store'])->name('product.store');
Route::get('products/{slug}', [ProductController::class, 'detail'])->name('product.detail');
Route::put('products/{id}', [ProductController::class, 'update'])->name('product.update');
Route::delete('products/{id}', [ProductController::class, 'destroy'])->name('product.destroy');

/* Publisher */
Route::get('publishers', [PublisherController::class, 'index']);
Route::post('publishers', [PublisherController::class, 'store'])->name('product.store');
Route::get('publishers/{slug}', [PublisherController::class, 'detail'])->name('product.detail');
Route::put('publishers/{id}', [PublisherController::class, 'update'])->name('product.update');
Route::delete('publishers/{id}', [PublisherController::class, 'destroy'])->name('product.destroy');

/* Trolley */
Route::get('trolleys', [TrolleyController::class, 'index']);
Route::post('product/{id}/trolleys', [TrolleyController::class, 'store'])->name('product.trolley.store');
Route::put('products/{id}/trolleys', [TrolleyController::class, 'update'])->name('product.trolley.update');
Route::delete('products/{id}/trolleys', [TrolleyController::class, 'destroy'])->name('product.trolley.destroy');