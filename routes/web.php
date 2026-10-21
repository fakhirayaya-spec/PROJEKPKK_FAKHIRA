<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PreOrderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\KategoriMenuController;
use App\Http\Controllers\Admin\KasirController;
use App\Http\Controllers\Admin\StokController;
use App\Http\Controllers\Admin\BumbuController;
use App\Http\Controllers\Admin\RiwayatTransaksiController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PengeluaranController;
use App\Http\Controllers\Admin\ProfilController as AdminProfilController;
use App\Http\Controllers\Kasir\DashboardController as KasirDashboardController;
use App\Http\Controllers\Kasir\StokController as KasirStokController;
use App\Http\Controllers\Kasir\ProfilController as KasirProfilController;
use App\Http\Controllers\Kasir\TransaksiController as KasirTransaksiController;
use App\Http\Controllers\Kasir\RiwayatController as KasirRiwayatController;

Route::get('/', function () {
    return view('auth.login');
});

// Dashboard umum: otomatis di-route ke tampilan sesuai role (lihat DashboardController)
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Pre-order: admin & kasir sama-sama boleh MELIHAT (daftar + detail/progres).
// Mengubahnya (buat, ubah, ganti status, selesaikan, batalkan) hanya kasir — lihat grup kasir di bawah.
$rutePreorderLihat = function () {
    Route::get('/', [PreOrderController::class, 'index'])->name('index');
    Route::get('/{preorder}', [PreOrderController::class, 'show'])->name('show');
};

// ============ KHUSUS ADMIN ============
Route::middleware(['auth', 'role:admin'])->group(function () use ($rutePreorderLihat) {
    Route::get('/stok', [StokController::class, 'index'])->name('admin.stok.index');
    Route::post('/stok/{menu}/tambah', [StokController::class, 'tambahStok'])->name('admin.stok.tambah');
    Route::get('/stok/{menu}/riwayat', [StokController::class, 'riwayat'])->name('admin.stok.riwayat');
    // Manajemen bumbu (bumbu tabur & saus)
    Route::get('/bumbu', [BumbuController::class, 'index'])->name('admin.bumbu.index');
    Route::post('/bumbu', [BumbuController::class, 'store'])->name('admin.bumbu.store');
    Route::put('/bumbu/{bumbu}', [BumbuController::class, 'update'])->name('admin.bumbu.update');
    Route::post('/bumbu/{bumbu}/tambah', [BumbuController::class, 'tambahStok'])->name('admin.bumbu.tambah');
    Route::patch('/bumbu/{bumbu}/toggle', [BumbuController::class, 'toggle'])->name('admin.bumbu.toggle');
    Route::delete('/bumbu/{bumbu}', [BumbuController::class, 'destroy'])->name('admin.bumbu.destroy');
    Route::resource('kasir', KasirController::class)->names('admin.kasir')->except(['show']);
    Route::patch('/kasir/{kasir}/toggle-status', [KasirController::class, 'toggleStatus'])->name('admin.kasir.toggle-status');
    Route::patch('/kasir/{kasir}/reset-password', [KasirController::class, 'resetPassword'])->name('admin.kasir.reset-password');
    Route::resource('menu', MenuController::class)->names('admin.menu');
    Route::post('/kategori-menu', [KategoriMenuController::class, 'store'])->name('admin.kategori-menu.store');
    Route::delete('/kategori-menu/{kategoriMenu}', [KategoriMenuController::class, 'destroy'])->name('admin.kategori-menu.destroy');

    // Riwayat Transaksi (semua kasir)
    Route::get('/riwayat-transaksi', [RiwayatTransaksiController::class, 'index'])->name('admin.riwayat.index');
    Route::get('/riwayat-transaksi/{transaksi}', [RiwayatTransaksiController::class, 'show'])->name('admin.riwayat.show');

    // Pengeluaran (bahan baku, operasional, lainnya) — dipakai untuk hitung keuntungan bersih
    Route::get('/pengeluaran', [PengeluaranController::class, 'index'])->name('admin.pengeluaran.index');
    Route::post('/pengeluaran', [PengeluaranController::class, 'store'])->name('admin.pengeluaran.store');
    Route::delete('/pengeluaran/{pengeluaran}', [PengeluaranController::class, 'destroy'])->name('admin.pengeluaran.destroy');

    // Laporan (tampilan web + cetak PDF)
    Route::get('/laporan', [LaporanController::class, 'index'])->name('admin.laporan.index');
    Route::get('/laporan/cetak', [LaporanController::class, 'cetak'])->name('admin.laporan.cetak');

    // Pre-order (admin hanya memantau: lihat daftar & progres)
    Route::prefix('preorder')->name('admin.preorder.')->group($rutePreorderLihat);

    // Profil admin
    Route::get('/profil', [AdminProfilController::class, 'edit'])->name('admin.profil.edit');
    Route::put('/profil', [AdminProfilController::class, 'update'])->name('admin.profil.update');
    Route::delete('/profil/foto', [AdminProfilController::class, 'hapusFoto'])->name('admin.profil.foto');
});

// ============ KHUSUS KASIR ============
Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->name('kasir.')->group(function () use ($rutePreorderLihat) {
    Route::get('/dashboard', [KasirDashboardController::class, 'index'])->name('dashboard');

    Route::get('/transaksi', [KasirTransaksiController::class, 'index'])->name('transaksi.index');
    Route::post('/transaksi', [KasirTransaksiController::class, 'store'])->name('transaksi.store');
    Route::get('/transaksi/{transaksi}/struk', [KasirTransaksiController::class, 'struk'])->name('transaksi.struk');
    Route::post('/transaksi/{transaksi}/batal', [KasirTransaksiController::class, 'batal'])->name('transaksi.batal');

    Route::get('/stok', [KasirStokController::class, 'index'])->name('stok.index');

    Route::get('/riwayat', [KasirRiwayatController::class, 'index'])->name('riwayat.index');

    // Pre-order: kasir yang mengerjakan (buat, ubah, ganti status, selesaikan, batalkan)
    Route::prefix('preorder')->name('preorder.')->group(function () use ($rutePreorderLihat) {
        // '/buat' harus didaftarkan SEBELUM '/{preorder}' supaya tidak dianggap sebagai id
        Route::get('/buat', [PreOrderController::class, 'create'])->name('create');
        Route::post('/', [PreOrderController::class, 'store'])->name('store');

        $rutePreorderLihat();

        Route::put('/{preorder}', [PreOrderController::class, 'update'])->name('update');
        Route::post('/{preorder}/status', [PreOrderController::class, 'status'])->name('status');
        Route::post('/{preorder}/selesai', [PreOrderController::class, 'selesai'])->name('selesai');
        Route::post('/{preorder}/batal', [PreOrderController::class, 'batal'])->name('batal');
    });

    // Profil kasir: kasir mengganti foto sendiri
    Route::get('/profil', [KasirProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [KasirProfilController::class, 'update'])->name('profil.update');
    Route::delete('/profil/foto', [KasirProfilController::class, 'hapusFoto'])->name('profil.foto');
});

// ============ PROFIL (SEMUA ROLE, dipakai bersama) ============
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
