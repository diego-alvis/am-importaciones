<?php

use App\Enums\LocationType;
use App\Enums\TransferStatus;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;

beforeEach(function () {
    $this->almacen = Location::create(['name' => 'Almacén Central', 'type' => LocationType::Almacen]);
    $this->sedeA = Location::create(['name' => 'Sede Plaza Norte', 'type' => LocationType::Sede]);
    $this->sedeB = Location::create(['name' => 'Sede La Muela', 'type' => LocationType::Sede]);

    $this->admin = User::factory()->create(['role' => 'admin', 'location_id' => $this->almacen->id]);
    $this->almacenero = User::factory()->create(['role' => 'almacenero', 'location_id' => $this->almacen->id]);
    $this->encargadoA = User::factory()->create(['role' => 'encargado', 'location_id' => $this->sedeA->id]);
    $this->vendedorA = User::factory()->create(['role' => 'vendedor', 'location_id' => $this->sedeA->id]);
    $this->encargadoB = User::factory()->create(['role' => 'encargado', 'location_id' => $this->sedeB->id]);

    $this->category = Category::create(['name' => 'Fundas', 'slug' => 'fundas']);

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'name' => 'Funda Silicona',
        'sku' => 'FUN-001',
        'base_price' => 20.00,
    ]);

    // Stock inicial en almacén central: 50 und. En sede A: 0 und.
    Stock::create(['location_id' => $this->almacen->id, 'product_id' => $this->product->id, 'quantity' => 50, 'min_stock' => 5]);
    Stock::create(['location_id' => $this->sedeA->id, 'product_id' => $this->product->id, 'quantity' => 0, 'min_stock' => 5]);
});

test('store seller and encargado can access transfers page and see order button', function () {
    $this->actingAs($this->encargadoA)
        ->get(route('transfers.index'))
        ->assertOk()
        ->assertSee('Pedidos de Mercadería a Almacén Central')
        ->assertSee('Realizar Pedido de Mercadería');

    $this->actingAs($this->vendedorA)
        ->get(route('transfers.index'))
        ->assertOk();

    $this->actingAs($this->almacenero)
        ->get(route('transfers.index'))
        ->assertOk()
        ->assertSee('Centro de Despacho y Pedidos de Almacén');
});

test('store seller can create an order request (status solicitado and warehouse stock not decremented yet)', function () {
    $response = $this->actingAs($this->vendedorA)
        ->post(route('transfers.store'), [
            'notes' => 'Pedido de reposición para el fin de semana',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 15,
                ],
            ],
        ]);

    $response->assertRedirect(route('transfers.index'));
    $response->assertSessionHas('success');

    $transfer = Transfer::latest()->first();
    expect($transfer)->not->toBeNull();
    expect($transfer->status)->toBe(TransferStatus::Solicitado);
    expect($transfer->from_location_id)->toBe($this->almacen->id);
    expect($transfer->to_location_id)->toBe($this->sedeA->id);
    expect($transfer->requested_by)->toBe($this->vendedorA->id);
    expect($transfer->notes)->toBe('Pedido de reposición para el fin de semana');

    // El stock en almacén central NO debe haberse descontado todavía (sigue en 50)
    $stockAlmacen = Stock::where('location_id', $this->almacen->id)->where('product_id', $this->product->id)->first();
    expect($stockAlmacen->quantity)->toBe(50);

    // Stock de destino sigue en 0
    $stockSedeA = Stock::where('location_id', $this->sedeA->id)->where('product_id', $this->product->id)->first();
    expect($stockSedeA->quantity)->toBe(0);
});

test('warehouse can dispatch the order request (status en_transito and warehouse stock decremented)', function () {
    $transfer = Transfer::create([
        'from_location_id' => $this->almacen->id,
        'to_location_id' => $this->sedeA->id,
        'status' => TransferStatus::Solicitado,
        'requested_by' => $this->vendedorA->id,
    ]);

    TransferItem::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 15,
    ]);

    $response = $this->actingAs($this->almacenero)
        ->post(route('transfers.dispatch', $transfer->id));

    $response->assertSessionHas('success');

    $transfer->refresh();
    expect($transfer->status)->toBe(TransferStatus::EnTransito);
    expect($transfer->dispatched_by)->toBe($this->almacenero->id);

    // El stock en almacén central se descuenta ahora (50 - 15 = 35)
    $stockAlmacen = Stock::where('location_id', $this->almacen->id)->where('product_id', $this->product->id)->first();
    expect($stockAlmacen->quantity)->toBe(35);

    // Stock de destino todavía no sube (mercadería en camino)
    $stockSedeA = Stock::where('location_id', $this->sedeA->id)->where('product_id', $this->product->id)->first();
    expect($stockSedeA->quantity)->toBe(0);
});

test('destination store supervisor can confirm reception of dispatched transfer', function () {
    $transfer = Transfer::create([
        'from_location_id' => $this->almacen->id,
        'to_location_id' => $this->sedeA->id,
        'status' => TransferStatus::EnTransito,
        'dispatched_by' => $this->almacenero->id,
    ]);

    TransferItem::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
    ]);

    // Stock de sede A antes de recibir: 0
    expect(Stock::where('location_id', $this->sedeA->id)->where('product_id', $this->product->id)->value('quantity'))->toBe(0);

    // Sede A confirma la recepción física
    $response = $this->actingAs($this->encargadoA)
        ->post(route('transfers.receive', $transfer->id));

    $response->assertSessionHas('success');

    $transfer->refresh();
    expect($transfer->status)->toBe(TransferStatus::Recibido);

    // Stock de sede A debe haber subido a 10
    expect(Stock::where('location_id', $this->sedeA->id)->where('product_id', $this->product->id)->value('quantity'))->toBe(10);
});

test('store seller cannot dispatch an order (403 forbidden)', function () {
    $transfer = Transfer::create([
        'from_location_id' => $this->almacen->id,
        'to_location_id' => $this->sedeA->id,
        'status' => TransferStatus::Solicitado,
    ]);

    // Vendedor intenta despachar el pedido
    $this->actingAs($this->vendedorA)
        ->post(route('transfers.dispatch', $transfer->id))
        ->assertForbidden();

    expect($transfer->fresh()->status)->toBe(TransferStatus::Solicitado);
});

test('warehouse cannot dispatch if warehouse stock is insufficient', function () {
    $transfer = Transfer::create([
        'from_location_id' => $this->almacen->id,
        'to_location_id' => $this->sedeA->id,
        'status' => TransferStatus::Solicitado,
    ]);

    TransferItem::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 999, // Stock en almacén es solo 50
    ]);

    $response = $this->actingAs($this->almacenero)
        ->post(route('transfers.dispatch', $transfer->id));

    $response->assertSessionHas('error');
    expect($transfer->fresh()->status)->toBe(TransferStatus::Solicitado);
});

test('supervisor of different store cannot confirm reception', function () {
    $transfer = Transfer::create([
        'from_location_id' => $this->almacen->id,
        'to_location_id' => $this->sedeA->id,
        'status' => TransferStatus::EnTransito,
    ]);

    TransferItem::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
    ]);

    // Encargado B (de Sede La Muela) intenta confirmar traslado destinado a Sede Plaza Norte
    $this->actingAs($this->encargadoB)
        ->post(route('transfers.receive', $transfer->id))
        ->assertForbidden();

    expect($transfer->fresh()->status)->toBe(TransferStatus::EnTransito);
});

test('can view picking sheet for order preparation', function () {
    $transfer = Transfer::create([
        'from_location_id' => $this->almacen->id,
        'to_location_id' => $this->sedeA->id,
        'status' => TransferStatus::Solicitado,
    ]);

    TransferItem::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 5,
    ]);

    $this->actingAs($this->almacenero)
        ->get(route('transfers.picking', $transfer->id))
        ->assertOk()
        ->assertSee('Hoja de Picking y Despacho de Mercadería')
        ->assertSee('Funda Silicona');
});
