<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', UserController::class);
});

Route::middleware(['auth', 'role:guru,gurumapel'])->group(function () {
    Route::post('/absensi', [AbsensiController::class, 'store']);
});

Route::middleware(['auth', 'role:guru,walikelas'])->group(function () {
    Route::post('/laporan', [LaporanController::class, 'store']);
    Route::post('/pengumuman', [PengumumanController::class, 'store']);
});

Route::middleware(['auth', 'role:orangtua'])->group(function () {
    Route::get('/laporan', [LaporanController::class, 'index']);
    Route::get('/pengumuman', [PengumumanController::class, 'index']);
});

require __DIR__.'/auth.php';
