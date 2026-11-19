<?php

use App\Enums\LocationType;
use App\Enums\TransferStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;

beforeEach(function (): void {
    $this->almacen = Location::create(['name' => 'Almacén Central', 'type' => LocationType::Almacen->value]);
    $this->sedeNorte = Location::create(['name' => 'Sede Plaza Norte', 'type' => LocationType::Sede->value]);

    $this->almacenero = User::factory()->create([
        'role' => UserRole::Almacenero->value,
        'location_id' => $this->almacen->id,
    ]);

    $this->encargado = User::factory()->create([
        'role' => UserRole::Encargado->value,
        'location_id' => $this->sedeNorte->id,
    ]);

    $this->vendedor = User::factory()->create([
        'role' => UserRole::Vendedor->value,
        'location_id' => $this->sedeNorte->id,
    ]);

    $this->category = Category::create(['name' => 'Cargadores']);

    $this->productOk = Product::create([
        'name' => 'Cargador Turbo 30W',
        'sku' => 'CHG-30W',
        'category_id' => $this->category->id,
        'base_price' => 45.00,
    ]);

    $this->productLow = Product::create([
        'name' => 'Cable USB-C 2M',
        'sku' => 'CAB-2M',
        'category_id' => $this->category->id,
        'base_price' => 20.00,
    ]);

    $this->productOut = Product::create([
        'name' => 'Adaptador Audio Pro',
        'sku' => 'ADP-PRO',
        'category_id' => $this->category->id,
        'base_price' => 15.00,
    ]);

    Stock::create(['product_id' => $this->productOk->id, 'location_id' => $this->almacen->id, 'quantity' => 30, 'min_stock' => 5]);
    Stock::create(['product_id' => $this->productOk->id, 'location_id' => $this->sedeNorte->id, 'quantity' => 4, 'min_stock' => 5]);

    Stock::create(['product_id' => $this->productLow->id, 'location_id' => $this->almacen->id, 'quantity' => 3, 'min_stock' => 5]);
    Stock::create(['product_id' => $this->productLow->id, 'location_id' => $this->sedeNorte->id, 'quantity' => 0, 'min_stock' => 5]);

    Stock::create(['product_id' => $this->productOut->id, 'location_id' => $this->almacen->id, 'quantity' => 0, 'min_stock' => 4]);
    Stock::create(['product_id' => $this->productOut->id, 'location_id' => $this->sedeNorte->id, 'quantity' => 8, 'min_stock' => 4]);
});

test('almacenero sees enriched warehouse dashboard with pending requests, analytics, and store needs', function (): void {
    $pendingTransfer = Transfer::create([
        'from_location_id' => $this->almacen->id,
        'to_location_id' => $this->sedeNorte->id,
        'requested_by' => $this->encargado->id,
        'status' => TransferStatus::Solicitado,
    ]);
    TransferItem::create([
        'transfer_id' => $pendingTransfer->id,
        'product_id' => $this->productOk->id,
        'quantity' => 6,
    ]);

    $dispatchedTransfer = Transfer::create([
        'from_location_id' => $this->almacen->id,
        'to_location_id' => $this->sedeNorte->id,
        'requested_by' => $this->almacenero->id,
        'dispatched_by' => $this->almacenero->id,
        'status' => TransferStatus::EnTransito,
    ]);
    TransferItem::create([
        'transfer_id' => $dispatchedTransfer->id,
        'product_id' => $this->productOk->id,
        'quantity' => 12,
    ]);

    $response = $this->actingAs($this->almacenero)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Pedidos por Despachar')
        ->assertSee('Actividad Logística')
        ->assertSee('Stock por Categoría')
        ->assertSee('Necesidades de Reposición en Sedes')
        ->assertSee('Sede Plaza Norte')
        ->assertSee('Productos Críticos y Agotados')
        ->assertSee('Pedido #'.$pendingTransfer->id)
        ->assertSee('ADP-PRO');
});

test('almacenero sees warehouse inventory with store distribution, quick filters, and sorting', function (): void {
    $response = $this->actingAs($this->almacenero)->get(route('inventory.index', ['sort' => 'stock_asc']));

    $response->assertOk()
        ->assertSee('Stock en Almacén Central')
        ->assertSee('Distribución en Sedes')
        ->assertSee('Nuevo Producto')
        ->assertSeeInOrder(['ADP-PRO', 'CAB-2M', 'CHG-30W']);

    $onlyOut = $this->actingAs($this->almacenero)->get(route('inventory.index', ['stock_status' => 'sin_stock']));
    $onlyOut->assertOk()
        ->assertSee('ADP-PRO')
        ->assertDontSee('CHG-30W')
        ->assertDontSee('CAB-2M');

    $onlyOk = $this->actingAs($this->almacenero)->get(route('inventory.index', ['stock_status' => 'ok']));
    $onlyOk->assertOk()
        ->assertSee('CHG-30W')
        ->assertDontSee('ADP-PRO');
});

test('almacenero can create and edit products but cannot delete them', function (): void {
    $createResponse = $this->actingAs($this->almacenero)->post(route('inventory.store'), [
        'name' => 'Power Bank 10000mAh',
        'sku' => 'pb-10k',
        'category_id' => $this->category->id,
        'base_price' => 89.90,
        'min_stock' => 8,
        'stock_loc_'.$this->almacen->id => 25,
        'stock_loc_'.$this->sedeNorte->id => 99,
    ]);

    $createResponse->assertRedirect(route('inventory.index'));

    $created = Product::where('sku', 'PB-10K')->first();
    expect($created)->not->toBeNull();

    $almacenStock = Stock::where('product_id', $created->id)->where('location_id', $this->almacen->id)->first();
    $sedeStock = Stock::where('product_id', $created->id)->where('location_id', $this->sedeNorte->id)->first();

    expect($almacenStock->quantity)->toBe(25)
        ->and($almacenStock->min_stock)->toBe(8)
        ->and($sedeStock->quantity)->toBe(0);

    $updateResponse = $this->actingAs($this->almacenero)->put(route('inventory.update', $created->id), [
        'name' => 'Power Bank 10000mAh Pro',
        'sku' => 'PB-10K-PRO',
        'category_id' => $this->category->id,
        'base_price' => 95.00,
    ]);

    $updateResponse->assertRedirect(route('inventory.index'));
    expect($created->fresh()->name)->toBe('Power Bank 10000mAh Pro')
        ->and($created->fresh()->sku)->toBe('PB-10K-PRO');

    $this->actingAs($this->almacenero)
        ->delete(route('inventory.destroy', $created->id))
        ->assertForbidden();
});

test('almacenero can adjust warehouse stock and min_stock but cannot adjust store stock', function (): void {
    $response = $this->actingAs($this->almacenero)->post(route('inventory.adjust', $this->productOut->id), [
        'location_id' => $this->almacen->id,
        'operation' => 'add',
        'quantity' => 15,
        'min_stock' => 10,
    ]);

    $response->assertRedirect(route('inventory.index'));

    $updatedStock = Stock::where('product_id', $this->productOut->id)
        ->where('location_id', $this->almacen->id)
        ->first();

    expect($updatedStock->quantity)->toBe(15)
        ->and($updatedStock->min_stock)->toBe(10);

    $forbiddenLocation = $this->actingAs($this->almacenero)->post(route('inventory.adjust', $this->productOut->id), [
        'location_id' => $this->sedeNorte->id,
        'operation' => 'add',
        'quantity' => 5,
    ]);

    $forbiddenLocation->assertSessionHas('error');
    expect(Stock::where('product_id', $this->productOut->id)->where('location_id', $this->sedeNorte->id)->value('quantity'))->toBe(8);
});

test('encargado and vendedor cannot create or edit catalog products', function (): void {
    foreach ([$this->encargado, $this->vendedor] as $user) {
        $this->actingAs($user)
            ->post(route('inventory.store'), [
                'name' => 'Producto No Autorizado',
                'sku' => 'NO-AUTH',
                'category_id' => $this->category->id,
                'base_price' => 10.00,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('inventory.update', $this->productOk->id), [
                'name' => 'Intento Edit',
                'sku' => 'CHG-30W',
                'category_id' => $this->category->id,
                'base_price' => 10.00,
            ])
            ->assertForbidden();
    }
});
