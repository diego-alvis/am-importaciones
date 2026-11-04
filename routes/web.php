<?php

use App\Http\Controllers\CashController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreSessionController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

// Redirección inicial
Route::get('/', fn () => redirect()->route('dashboard'));

// Rutas autenticadas
Route::middleware(['auth'])->group(function () {

    // 1. Panel de Control y Selección de Tienda
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/tienda/cambiar', [StoreSessionController::class, 'setStand'])->name('tienda.set');

    // 2. Terminal Punto de Venta (POS) y Comprobantes
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::post('/sale', [PosController::class, 'store'])->name('store');
        Route::get('/ticket/{id}', [PosController::class, 'ticket'])->name('ticket');
    });

    // 3. Control de Caja Operativa
    Route::prefix('caja')->name('cash.')->group(function () {
        Route::get('/', [CashController::class, 'index'])->name('index');
        Route::post('/abrir', [CashController::class, 'open'])->name('open');
        Route::post('/cerrar', [CashController::class, 'close'])->name('close');
    });

    // 4. Supervisión y Arqueos de Caja
    Route::prefix('supervision')->name('supervisor.')->group(function () {
        // Consulta de reporte de arqueo (disponible para el cajero de su turno y supervisores)
        Route::get('/cajas/{id}/reporte', [SupervisorController::class, 'report'])
            ->middleware('role:admin,encargado,vendedor')
            ->name('reporte');

        // Aprobación y monitoreo exclusivo para Encargados y Administrador
        Route::middleware(['role:admin,encargado'])->group(function () {
            Route::get('/cajas', [SupervisorController::class, 'index'])->name('cajas');
            Route::post('/cajas/{id}/aprobar', [SupervisorController::class, 'approve'])->name('aprobar');
        });
    });

    // 5. Historial y Auditoría de Ventas
    Route::prefix('historial')->name('history.')->group(function () {
        Route::get('/', [HistoryController::class, 'index'])->name('index');
        Route::post('/{id}/anular', [HistoryController::class, 'cancel'])
            ->middleware('role:admin,encargado')
            ->name('cancel');
    });

    // 6. Inventario y Catálogo Maestro
    Route::prefix('inventario')->name('inventory.')->group(function () {
        // Consulta general de stock
        Route::get('/', [InventoryController::class, 'index'])->name('index');

        // Ajustes operativos de existencias
        Route::post('/{id}/ajustar', [InventoryController::class, 'adjustStock'])
            ->middleware('role:admin,encargado,almacenero')
            ->name('adjust');

        // Modificaciones al catálogo maestro (Exclusivo Administrador)
        Route::middleware(['role:admin'])->group(function () {
            Route::post('/', [InventoryController::class, 'store'])->name('store');
            Route::put('/{id}', [InventoryController::class, 'update'])->name('update');
            Route::delete('/{id}', [InventoryController::class, 'destroy'])->name('destroy');
        });
    });

    // 7. Categorías de Productos (Exclusivo Administrador)
    Route::prefix('categorias')->name('categories.')->middleware(['role:admin'])->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    // 8. Transferencias entre Sedes y Pedidos de Mercadería (Admin, Almacenero, Encargado, Vendedor)
    Route::prefix('transferencias')->name('transfers.')->middleware(['role:admin,almacenero,encargado,vendedor'])->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::post('/', [TransferController::class, 'store'])->name('store');
        Route::post('/{id}/despachar', [TransferController::class, 'dispatch'])->name('dispatch');
        Route::post('/{id}/recibir', [TransferController::class, 'receive'])->name('receive');
        Route::post('/{id}/cancelar', [TransferController::class, 'cancel'])->name('cancel');
        Route::get('/{id}/hoja-picking', [TransferController::class, 'pickingSheet'])->name('picking');
    });

    // 9. Perfil de Usuario
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/auth.php';
