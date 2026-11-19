<?php

use App\Enums\SaleStatus;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stand;
use App\Models\Stock;
use App\Models\User;

beforeEach(function () {
    $this->sede = Location::create(['name' => 'Sede Prueba', 'type' => 'sede']);
    $this->stand = Stand::create(['location_id' => $this->sede->id, 'name' => 'Stand P-1', 'base_cash' => 100]);

    $this->categoryCases = Category::create(['name' => 'Carcasas & Fundas']);
    $this->categoryAccessories = Category::create(['name' => 'Cargadores']);

    $this->productCase = Product::create([
        'category_id' => $this->categoryCases->id,
        'sku' => 'TEST-CASE-01',
        'name' => 'Funda Silicona Test',
        'base_price' => 25.00,
    ]);

    $this->productCharger = Product::create([
        'category_id' => $this->categoryAccessories->id,
        'sku' => 'TEST-CHG-01',
        'name' => 'Cargador 20W Test',
        'base_price' => 30.00,
    ]);

    // Stock: 5 fundas, 2 cargadores
    $this->stockCase = Stock::create([
        'location_id' => $this->sede->id,
        'product_id' => $this->productCase->id,
        'quantity' => 5,
        'min_stock' => 2,
    ]);

    $this->stockCharger = Stock::create([
        'location_id' => $this->sede->id,
        'product_id' => $this->productCharger->id,
        'quantity' => 2,
        'min_stock' => 1,
    ]);

    $this->vendedor = User::factory()->create([
        'role' => 'vendedor',
        'location_id' => $this->sede->id,
        'stand_id' => $this->stand->id,
    ]);

    $this->encargado = User::factory()->create([
        'role' => 'encargado',
        'location_id' => $this->sede->id,
    ]);
});

test('pos rejects sale if no cash session is open', function () {
    $response = $this->actingAs($this->vendedor)
        ->postJson('/pos/sale', [
            'cart' => [
                ['id' => $this->productCharger->id, 'quantity' => 1, 'price' => 30.00],
            ],
            'total' => 30.00,
            'payment_method' => 'Efectivo',
        ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('pos rejects sale if stock is insufficient', function () {
    CashSession::create([
        'user_id' => $this->vendedor->id,
        'stand_id' => $this->stand->id,
        'expected_cash' => 100,
        'status' => 'abierta',
    ]);

    // Trata de vender 5 cargadores cuando solo hay 2
    $response = $this->actingAs($this->vendedor)
        ->postJson('/pos/sale', [
            'cart' => [
                ['id' => $this->productCharger->id, 'quantity' => 5, 'price' => 30.00],
            ],
            'total' => 150.00,
            'payment_method' => 'Efectivo',
        ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);

    expect($this->stockCharger->fresh()->quantity)->toBe(2);
});

test('pos rejects sale if product requires device model and it is not provided', function () {
    CashSession::create([
        'user_id' => $this->vendedor->id,
        'stand_id' => $this->stand->id,
        'expected_cash' => 100,
        'status' => 'abierta',
    ]);

    $response = $this->actingAs($this->vendedor)
        ->postJson('/pos/sale', [
            'cart' => [
                ['id' => $this->productCase->id, 'quantity' => 1, 'price' => 25.00, 'device_model' => ''],
            ],
            'total' => 25.00,
            'payment_method' => 'Efectivo',
        ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('pos registers sale, deducts stock, and records operator_name', function () {
    $cashSession = CashSession::create([
        'user_id' => $this->vendedor->id,
        'stand_id' => $this->stand->id,
        'operator_name' => 'Vendedor Operativo',
        'expected_cash' => 100,
        'status' => 'abierta',
    ]);

    $response = $this->actingAs($this->vendedor)
        ->postJson('/pos/sale', [
            'cart' => [
                [
                    'id' => $this->productCase->id,
                    'quantity' => 2,
                    'price' => 25.00,
                    'device_model' => 'iPhone 13',
                ],
            ],
            'total' => 50.00,
            'payment_method' => 'Efectivo',
        ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    expect($this->stockCase->fresh()->quantity)->toBe(3);

    $sale = Sale::latest()->first();
    expect($sale)->not->toBeNull()
        ->and((float) $sale->total_amount)->toBe(50.00)
        ->and($sale->operator_name)->toBe('Vendedor Operativo');
});

test('cannot cancel sale if cash session is already closed or approved', function () {
    $closedSession = CashSession::create([
        'user_id' => $this->vendedor->id,
        'stand_id' => $this->stand->id,
        'expected_cash' => 100,
        'declared_cash' => 100,
        'status' => 'precierre',
    ]);

    $sale = Sale::create([
        'cash_session_id' => $closedSession->id,
        'user_id' => $this->vendedor->id,
        'total_amount' => 30.00,
        'status' => 'completada',
    ]);

    $response = $this->actingAs($this->encargado)
        ->post('/historial/'.$sale->id.'/anular');

    $response->assertSessionHas('error');
    expect($sale->fresh()->status)->toBe(SaleStatus::Completada);
});

test('pos view correctly renders requires_device_model true for micas and cases', function () {
    $response = $this->actingAs($this->vendedor)->get('/pos');
    $response->assertOk();
    $response->assertSee('requires_device_model: true');
    $response->assertSee('requires_device_model: false');
});

test('pos registers sale with per unit discount and tracks original price and discount audit', function () {
    $cashSession = CashSession::create([
        'user_id' => $this->vendedor->id,
        'stand_id' => $this->stand->id,
        'operator_name' => 'Vendedor Descuento',
        'expected_cash' => 100,
        'status' => 'abierta',
    ]);

    // 2 fundas (base_price 25.00 c/u) con descuento de S/ 5.00 por unidad -> S/ 20.00 c/u = S/ 40.00
    $response = $this->actingAs($this->vendedor)
        ->postJson('/pos/sale', [
            'cart' => [
                [
                    'id' => $this->productCase->id,
                    'quantity' => 2,
                    'unit_discount' => 5.00,
                    'device_model' => 'iPhone 15',
                ],
            ],
            'total' => 40.00,
            'payment_method' => 'Efectivo',
        ]);

    $response->assertOk()
        ->assertJson(['success' => true, 'total' => 40.00]);

    $sale = Sale::with(['items', 'payments'])->latest()->first();
    expect((float) $sale->subtotal_amount)->toBe(50.00)
        ->and((float) $sale->discount_amount)->toBe(10.00)
        ->and((float) $sale->total_amount)->toBe(40.00);

    $item = $sale->items->first();
    expect((float) $item->original_price)->toBe(25.00)
        ->and((float) $item->unit_discount)->toBe(5.00)
        ->and((float) $item->discount_amount)->toBe(10.00)
        ->and((float) $item->unit_price)->toBe(20.00);

    expect((float) $sale->payments->first()->amount)->toBe(40.00);
    expect((float) $cashSession->fresh()->getMetrics()->total_ventas)->toBe(40.00);
});

test('pos allows free courtesy discount equal to base price', function () {
    CashSession::create([
        'user_id' => $this->vendedor->id,
        'stand_id' => $this->stand->id,
        'expected_cash' => 100,
        'status' => 'abierta',
    ]);

    $response = $this->actingAs($this->vendedor)
        ->postJson('/pos/sale', [
            'cart' => [
                [
                    'id' => $this->productCharger->id,
                    'quantity' => 1,
                    'unit_discount' => 30.00,
                ],
            ],
            'total' => 0.00,
            'payment_method' => 'Efectivo',
        ]);

    $response->assertOk()
        ->assertJson(['success' => true, 'total' => 0]);

    $sale = Sale::latest()->first();
    expect((float) $sale->subtotal_amount)->toBe(30.00)
        ->and((float) $sale->discount_amount)->toBe(30.00)
        ->and((float) $sale->total_amount)->toBe(0.00)
        ->and($this->stockCharger->fresh()->quantity)->toBe(1);
});

test('pos rejects unit discount exceeding base price or tampered client price', function () {
    CashSession::create([
        'user_id' => $this->vendedor->id,
        'stand_id' => $this->stand->id,
        'expected_cash' => 100,
        'status' => 'abierta',
    ]);

    // Descuento mayor al precio base (30.00)
    $this->actingAs($this->vendedor)
        ->postJson('/pos/sale', [
            'cart' => [
                [
                    'id' => $this->productCharger->id,
                    'quantity' => 1,
                    'unit_discount' => 35.00,
                ],
            ],
            'total' => 0.00,
            'payment_method' => 'Efectivo',
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    // Manipulación de precio sin descuento válido
    $this->actingAs($this->vendedor)
        ->postJson('/pos/sale', [
            'cart' => [
                [
                    'id' => $this->productCharger->id,
                    'quantity' => 1,
                    'price' => 1.00,
                    'unit_discount' => 0,
                ],
            ],
            'total' => 1.00,
            'payment_method' => 'Efectivo',
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    expect($this->stockCharger->fresh()->quantity)->toBe(2);
});

test('pos groups same product with different discounts separately in cash session summary and validates combined stock', function () {
    $cashSession = CashSession::create([
        'user_id' => $this->vendedor->id,
        'stand_id' => $this->stand->id,
        'expected_cash' => 100,
        'status' => 'abierta',
    ]);

    // Stock cargador = 2. Intentar vender 2 en dos líneas (2 + 1 = 3 > 2) debe fallar
    $this->actingAs($this->vendedor)
        ->postJson('/pos/sale', [
            'cart' => [
                ['id' => $this->productCharger->id, 'quantity' => 2, 'unit_discount' => 0],
                ['id' => $this->productCharger->id, 'quantity' => 1, 'unit_discount' => 5],
            ],
            'total' => 85.00,
            'payment_method' => 'Efectivo',
        ])
        ->assertStatus(422);

    // Venta válida: 1 sin descuento (30) + 1 con descuento S/ 5 (25) = 55
    $this->actingAs($this->vendedor)
        ->postJson('/pos/sale', [
            'cart' => [
                ['id' => $this->productCharger->id, 'quantity' => 1, 'unit_discount' => 0],
                ['id' => $this->productCharger->id, 'quantity' => 1, 'unit_discount' => 5],
            ],
            'total' => 55.00,
            'payment_method' => 'Efectivo',
        ])
        ->assertOk();

    $summary = $cashSession->fresh()->getItemsSummary();
    expect($summary)->toHaveCount(2);
});
