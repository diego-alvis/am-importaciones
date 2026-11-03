<?php

use App\Http\Controllers\CashController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\StoreSessionController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {

    // Panel Principal (Dashboard)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Selección de Tienda / Operador
    Route::post('/tienda/cambiar', [StoreSessionController::class, 'setStand'])->name('tienda.set');

    // Punto de Venta (POS)
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/sale', [PosController::class, 'store'])->name('pos.store');
    Route::get('/pos/ticket/{id}', [PosController::class, 'ticket'])->name('pos.ticket');

    // Caja y Turnos Operativos
    Route::get('/caja', [CashController::class, 'index'])->name('cash.index');
    Route::post('/caja/abrir', [CashController::class, 'open'])->name('cash.open');
    Route::post('/caja/cerrar', [CashController::class, 'close'])->name('cash.close');

    // Historial de Ventas y Reportes
    Route::get('/historial', [HistoryController::class, 'index'])->name('history.index');
    Route::post('/historial/{id}/anular', [HistoryController::class, 'cancel'])->name('history.cancel');

    // Inventario y Catálogo (Acceso general para consulta, gestión de stock)
    Route::get('/inventario', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventario', [InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/inventario/{id}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::post('/inventario/{id}/ajustar', [InventoryController::class, 'adjustStock'])->name('inventory.adjust');
    Route::delete('/inventario/{id}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    // Categorías de Productos
    Route::get('/categorias', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categorias', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categorias/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categorias/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Transferencias entre Sedes
    Route::get('/transferencias', [TransferController::class, 'index'])->name('transfers.index');
    Route::post('/transferencias', [TransferController::class, 'store'])->name('transfers.store');

    // Supervisión y Arqueo (Exclusivo para Administrador y Encargados)
    Route::middleware(['role:admin,encargado'])->group(function () {
        Route::get('/supervision/cajas', [SupervisorController::class, 'index'])->name('supervisor.cajas');
        Route::post('/supervision/cajas/{id}/aprobar', [SupervisorController::class, 'approve'])->name('supervisor.aprobar');
        Route::get('/supervision/cajas/{id}/reporte', [SupervisorController::class, 'report'])->name('supervisor.reporte');
    });

});

require __DIR__.'/auth.php';
