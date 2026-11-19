<?php

use App\Enums\CashSessionStatus;
use App\Enums\LocationType;
use App\Enums\TransferStatus;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Setting;
use App\Models\Stand;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\StandAccountsSeeder;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->sedeA = Location::create(['name' => 'Sede Plaza Norte', 'type' => 'sede']);
    $this->sedeB = Location::create(['name' => 'Sede Av 54', 'type' => 'sede']);

    $this->standA10 = Stand::create(['location_id' => $this->sedeA->id, 'name' => 'Tienda A-10', 'base_cash' => 100]);
    $this->standA5 = Stand::create(['location_id' => $this->sedeA->id, 'name' => 'Tienda A-5', 'base_cash' => 100]);
    $this->standAv54 = Stand::create(['location_id' => $this->sedeB->id, 'name' => 'Tienda Av 54', 'base_cash' => 100]);
});

test('only one admin account can exist in the system', function () {
    User::factory()->admin()->create();

    expect(fn () => User::factory()->admin()->create())
        ->toThrow(DomainException::class);
});

test('only one supervisor can exist per location', function () {
    User::factory()->encargado()->atStand($this->standA10)->create();

    expect(fn () => User::factory()->encargado()->atStand($this->standA5)->create())
        ->toThrow(DomainException::class);

    // En otra sede sí debe permitir crear su propio supervisor
    $otherSupervisor = User::factory()->encargado()->atStand($this->standAv54)->create();
    expect($otherSupervisor->exists)->toBeTrue();
});

test('admin login invalidates previous active session on another device', function () {
    $admin = User::factory()->admin()->create();

    // Dispositivo 1 inicia sesión
    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $firstSessionId = session()->getId();
    expect($admin->fresh()->current_session_id)->toBe($firstSessionId);

    // Simulamos un nuevo navegador / dispositivo independiente
    Auth::forgetGuards();
    $this->flushSession();

    // Dispositivo 2 inicia sesión con la misma cuenta Admin
    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $secondSessionId = session()->getId();
    expect($secondSessionId)->not->toBe($firstSessionId)
        ->and($admin->fresh()->current_session_id)->toBe($secondSessionId);

    // Si el Dispositivo 1 intenta hacer una petición con su antigua sesión, es expulsado al login
    Auth::forgetGuards();
    $this->actingAs($admin->fresh());
    session()->setId($firstSessionId);

    $this->get('/dashboard')
        ->assertRedirect(route('login'))
        ->assertSessionHas('warning');
});

test('supervisor login invalidates previous active session on another device', function () {
    $supervisor = User::factory()->encargado()->atStand($this->standA10)->create();

    $this->post('/login', [
        'email' => $supervisor->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $firstSessionId = session()->getId();

    Auth::forgetGuards();
    $this->flushSession();

    $this->post('/login', [
        'email' => $supervisor->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect($supervisor->fresh()->current_session_id)->not->toBe($firstSessionId);

    // Sesión antigua es rechazada también en peticiones JSON (POS)
    Auth::forgetGuards();
    $this->actingAs($supervisor->fresh());
    session()->setId($firstSessionId);

    $this->getJson('/dashboard')
        ->assertStatus(401)
        ->assertJson(['success' => false]);
});

test('vendedor and supervisor with fixed stand cannot switch to another stand but can update operator name', function () {
    $vendedor = User::factory()->vendedor()->atStand($this->standA5)->create();

    expect($vendedor->getAvailableStands())->toHaveCount(1)
        ->and($vendedor->getActiveStand()->id)->toBe($this->standA5->id);

    // Intento de cambiar a otro stand (A-10) debe ser rechazado
    $this->actingAs($vendedor)
        ->post(route('tienda.set'), [
            'stand_id' => $this->standA10->id,
            'operator_name' => 'Pedro Gómez',
        ])
        ->assertSessionHas('error');

    expect($vendedor->fresh()->stand_id)->toBe($this->standA5->id);

    // Actualizar solo el operador en su stand fijo sí debe funcionar
    $this->actingAs($vendedor)
        ->post(route('tienda.set'), [
            'stand_id' => $this->standA5->id,
            'operator_name' => 'María Torres',
        ])
        ->assertSessionHas('success');

    expect($vendedor->fresh()->display_name)->toBe('María Torres');
});

test('fixed stand user cannot open cash session in a different stand', function () {
    $vendedor = User::factory()->vendedor()->atStand($this->standA5)->create();

    $this->actingAs($vendedor)
        ->post(route('cash.open'), [
            'stand_id' => $this->standA10->id,
            'opening_amount' => 100,
            'operator_name' => 'María Torres',
        ])
        ->assertRedirect(route('cash.index'))
        ->assertSessionHas('warning');

    expect(CashSession::count())->toBe(0);
});

test('av54 supervisor can sell on fixed stand and approve own cash closing', function () {
    $supAv54 = User::factory()->encargado()->atStand($this->standAv54)->create();

    $this->actingAs($supAv54)
        ->post(route('cash.open'), [
            'opening_amount' => 100,
            'operator_name' => 'Lucía Ramos',
        ])
        ->assertRedirect(route('pos.index'));

    $session = CashSession::first();
    expect($session->stand_id)->toBe($this->standAv54->id)
        ->and($session->operator_name)->toBe('Lucía Ramos');

    $this->actingAs($supAv54)
        ->post(route('cash.close'), ['declared_cash' => 100])
        ->assertRedirect(route('cash.index'));

    $this->actingAs($supAv54)
        ->post(route('supervisor.aprobar', $session->id))
        ->assertRedirect(route('supervisor.reporte', $session->id));

    expect($session->fresh()->status)->toBe(CashSessionStatus::Aprobada->value)
        ->and($session->fresh()->approved_by)->toBe($supAv54->id);
});

test('stand accounts seeder creates exact 14 official accounts and migrates legacy accounts', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(StandAccountsSeeder::class);

    expect(User::count())->toBe(14)
        ->and(User::where('role', 'admin')->count())->toBe(1)
        ->and(User::where('role', 'almacenero')->count())->toBe(1)
        ->and(User::where('role', 'encargado')->count())->toBe(3)
        ->and(User::where('role', 'vendedor')->count())->toBe(9);

    $supLaMuela = User::where('email', 'supervisor.lamuela@amimportaciones.com')->firstOrFail();
    expect($supLaMuela->stand->name)->toBe('Tienda D-18');

    $supPlazaNorte = User::where('email', 'supervisor.plazanorte@amimportaciones.com')->firstOrFail();
    expect($supPlazaNorte->stand->name)->toBe('Tienda A-10');

    $supAv54 = User::where('email', 'supervisor.av54@amimportaciones.com')->firstOrFail();
    expect($supAv54->stand->name)->toBe('Tienda Av 54');
});

test('seller dashboard displays shift progress, bonus goal, scoped top products and incoming transfers without store monitor', function () {
    Setting::create(['key' => 'meta_comision', 'value' => '400.00']);
    Setting::create(['key' => 'bono_comision', 'value' => '10.00']);

    $almacen = Location::create(['name' => 'Almacén Central', 'type' => LocationType::Almacen]);
    $vendedor = User::factory()->vendedor()->atStand($this->standA5)->create();
    $otroVendedor = User::factory()->vendedor()->atStand($this->standAv54)->create();

    $category = Category::create(['name' => 'Cables']);
    $prodStand = Product::create(['category_id' => $category->id, 'sku' => 'CAB-A5', 'name' => 'Cable Stand A5', 'base_price' => 250.00]);
    $prodOtro = Product::create(['category_id' => $category->id, 'sku' => 'CAB-AV', 'name' => 'Cable Otro Stand', 'base_price' => 100.00]);

    $session = CashSession::create([
        'user_id' => $vendedor->id,
        'stand_id' => $this->standA5->id,
        'operator_name' => 'Juan Pérez',
        'opening_time' => now(),
        'expected_cash' => 100,
        'status' => 'abierta',
    ]);

    $sale = Sale::create([
        'cash_session_id' => $session->id,
        'user_id' => $vendedor->id,
        'operator_name' => 'Juan Pérez',
        'subtotal_amount' => 250.00,
        'discount_amount' => 0.00,
        'total_amount' => 250.00,
        'status' => 'completada',
    ]);
    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $prodStand->id,
        'quantity' => 1,
        'original_price' => 250.00,
        'unit_discount' => 0.00,
        'discount_amount' => 0.00,
        'unit_price' => 250.00,
    ]);
    SalePayment::create(['sale_id' => $sale->id, 'payment_method' => 'efectivo', 'amount' => 250.00]);

    // Venta en otro stand NO debe filtrarse en el Top Productos del vendedor
    $otherSession = CashSession::create([
        'user_id' => $otroVendedor->id,
        'stand_id' => $this->standAv54->id,
        'opening_time' => now(),
        'expected_cash' => 100,
        'status' => 'abierta',
    ]);
    $otherSale = Sale::create([
        'cash_session_id' => $otherSession->id,
        'user_id' => $otroVendedor->id,
        'total_amount' => 100.00,
        'status' => 'completada',
    ]);
    SaleItem::create([
        'sale_id' => $otherSale->id,
        'product_id' => $prodOtro->id,
        'quantity' => 5,
        'unit_price' => 20.00,
    ]);

    // Pedido en tránsito hacia la sede del vendedor
    $transfer = Transfer::create([
        'from_location_id' => $almacen->id,
        'to_location_id' => $this->sedeA->id,
        'status' => TransferStatus::EnTransito,
    ]);
    TransferItem::create(['transfer_id' => $transfer->id, 'product_id' => $prodStand->id, 'quantity' => 10]);

    $response = $this->actingAs($vendedor)->get('/dashboard');

    $response->assertOk()
        ->assertSee('Mi Turno')
        ->assertSee('Meta de Ventas y Bonos del Turno')
        ->assertSee('Juan Pérez')
        ->assertSee('S/ 150.00') // Faltante para 400
        ->assertSee('Cable Stand A5')
        ->assertSee('Recibir')
        ->assertDontSee('Monitor Operativo de Tiendas')
        ->assertDontSee('Cable Otro Stand');
});

test('supervisor and admin dashboards render analytical charts, live sales and store monitor', function () {
    $admin = User::factory()->admin()->create();
    $supervisor = User::factory()->encargado()->atStand($this->standA10)->create();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertViewHasAll(['ventasUltimos7Dias', 'rendimientoSedes', 'ventasPorCategoria'])
        ->assertSee('Evolución de Ventas (Últimos 7 Días)')
        ->assertSee('Distribución por Método de Pago')
        ->assertSee('Monitor Operativo de Tiendas')
        ->assertSee('Últimas Ventas en Tiempo Real')
        ->assertSee('Ventas por Categoría (Mes)');

    $this->actingAs($supervisor)
        ->get('/dashboard')
        ->assertOk()
        ->assertViewHasAll(['ventasUltimos7Dias', 'rendimientoSedes', 'ventasPorCategoria'])
        ->assertSee('Evolución de Ventas (Últimos 7 Días)')
        ->assertSee('Distribución por Método de Pago')
        ->assertSee('Monitor Operativo de Tiendas');
});

test('history accepts todos date_range filter and shows past sales', function () {
    $admin = User::factory()->admin()->create();
    $vendedor = User::factory()->vendedor()->atStand($this->standA5)->create();

    $session = CashSession::create([
        'user_id' => $vendedor->id,
        'stand_id' => $this->standA5->id,
        'opening_time' => now()->subDays(10),
        'expected_cash' => 100,
        'status' => 'aprobada',
    ]);

    $oldSale = Sale::create([
        'cash_session_id' => $session->id,
        'user_id' => $vendedor->id,
        'operator_name' => 'Vendedor Antiguo',
        'subtotal_amount' => 75.00,
        'discount_amount' => 0.00,
        'total_amount' => 75.00,
        'status' => 'completada',
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);

    $this->actingAs($admin)
        ->get('/historial?date_range=todos')
        ->assertOk()
        ->assertViewHas('dateRange', 'todos')
        ->assertSee('#'.str_pad((string) $oldSale->id, 6, '0', STR_PAD_LEFT))
        ->assertSee('S/ 75.00');
});
