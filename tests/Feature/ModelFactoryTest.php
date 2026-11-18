<?php

use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Setting;
use App\Models\Stand;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

test('factory persists a valid record', function (Factory $factory) {
    $model = $factory->create();

    expect($model->exists)->toBeTrue();
})->with([
    'location' => fn () => Location::factory(),
    'almacen location' => fn () => Location::factory()->almacen(),
    'stand' => fn () => Stand::factory(),
    'category' => fn () => Category::factory(),
    'product' => fn () => Product::factory(),
    'stock' => fn () => Stock::factory(),
    'customer' => fn () => Customer::factory(),
    'ruc customer' => fn () => Customer::factory()->ruc(),
    'open cash session' => fn () => CashSession::factory()->abierta(),
    'pre-closed cash session' => fn () => CashSession::factory()->precierre(),
    'approved cash session' => fn () => CashSession::factory()->aprobada(),
    'sale' => fn () => Sale::factory(),
    'cancelled sale' => fn () => Sale::factory()->anulada(),
    'sale item' => fn () => SaleItem::factory(),
    'sale payment' => fn () => SalePayment::factory(),
    'transfer' => fn () => Transfer::factory(),
    'in transit transfer' => fn () => Transfer::factory()->enTransito(),
    'received transfer' => fn () => Transfer::factory()->recibido(),
    'transfer item' => fn () => TransferItem::factory(),
    'expense' => fn () => Expense::factory(),
    'setting' => fn () => Setting::factory(),
]);

test('sale factory takes seller from its cash session', function () {
    $session = CashSession::factory()->create();

    $sale = Sale::factory()->for($session)->create();

    expect($sale->user_id)->toBe($session->user_id)
        ->and($sale->operator_name)->toBe($session->operator_name);
});

test('product factory state requires device model on sale', function () {
    expect(Product::factory()->requiresDeviceModel()->create()->requiresDeviceModel())->toBeTrue()
        ->and(Product::factory()->create()->requiresDeviceModel())->toBeFalse();
});

test('user factory role states grant the expected access', function (string $state, string $method) {
    expect(User::factory()->{$state}()->create()->{$method}())->toBeTrue();
})->with([
    'admin' => ['admin', 'isAdmin'],
    'encargado' => ['encargado', 'isEncargado'],
    'vendedor' => ['vendedor', 'isVendedor'],
    'almacenero' => ['almacenero', 'isAlmacenero'],
]);

test('user factory stand state assigns the stand location', function () {
    $stand = Stand::factory()->create();

    $user = User::factory()->vendedor()->atStand($stand)->create();

    expect($user->stand_id)->toBe($stand->id)
        ->and($user->location_id)->toBe($stand->location_id);
});
