<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stand;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_opens_cash_session_with_operator_name(): void
    {
        $location = Location::create(['name' => 'Sede Plaza Norte', 'type' => 'sede']);
        $stand = Stand::create(['name' => 'Tienda A-5', 'location_id' => $location->id, 'base_cash' => 100]);

        $vendedor = User::factory()->create([
            'name' => 'Vendedor Genérico',
            'role' => 'vendedor',
            'location_id' => $location->id,
            'stand_id' => $stand->id,
        ]);

        $response = $this->actingAs($vendedor)->post(route('cash.open'), [
            'stand_id' => $stand->id,
            'opening_amount' => 100.00,
            'operator_name' => 'María Torres',
        ]);

        $response->assertRedirect(route('pos.index'));
        $this->assertDatabaseHas('cash_sessions', [
            'user_id' => $vendedor->id,
            'stand_id' => $stand->id,
            'operator_name' => 'María Torres',
            'status' => 'abierta',
        ]);

        $session = CashSession::first();
        $this->assertEquals('María Torres', $session->cashier_name);
        $this->assertEquals('María Torres', $vendedor->fresh()->display_name);
    }

    public function test_sales_and_history_and_supervisor_display_real_cashier_name(): void
    {
        $location = Location::create(['name' => 'Sede Plaza Norte', 'type' => 'sede']);
        $stand = Stand::create(['name' => 'Tienda A-5', 'location_id' => $location->id, 'base_cash' => 100]);
        $category = Category::create(['name' => 'Cables']);

        $product = Product::create([
            'name' => 'Cable USB-C 1M',
            'sku' => 'CAB-001',
            'category_id' => $category->id,
            'base_price' => 20.00,
        ]);

        Stock::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 25,
            'min_stock' => 5,
        ]);

        $vendedor = User::factory()->create([
            'name' => 'Cuenta Vendedor Stand',
            'role' => 'vendedor',
            'location_id' => $location->id,
            'stand_id' => $stand->id,
        ]);

        $cashSession = CashSession::create([
            'user_id' => $vendedor->id,
            'stand_id' => $stand->id,
            'operator_name' => 'Carlos Mendoza',
            'opening_time' => now(),
            'expected_cash' => 100.00,
            'status' => 'abierta',
        ]);

        // Registrar venta
        $posResponse = $this->actingAs($vendedor)->postJson(route('pos.store'), [
            'cart' => [
                ['id' => $product->id, 'quantity' => 1, 'price' => 20.00],
            ],
            'total' => 20.00,
            'payment_method' => 'efectivo',
            'cash_tendered' => 20.00,
            'change' => 0.00,
        ]);

        $posResponse->assertOk();
        $this->assertDatabaseHas('sales', [
            'cash_session_id' => $cashSession->id,
            'operator_name' => 'Carlos Mendoza',
            'total_amount' => 20.00,
        ]);

        // Verificar en Historial de Ventas
        $historyResponse = $this->actingAs($vendedor)->get(route('history.index'));
        $historyResponse->assertOk();
        $historyResponse->assertSee('Carlos Mendoza');

        // Cerrar turno
        $closeResponse = $this->actingAs($vendedor)->post(route('cash.close'), [
            'declared_cash' => 120.00,
        ]);
        $closeResponse->assertRedirect(route('cash.index'));

        // Supervisor aprueba y consulta reporte
        $supervisor = User::factory()->create([
            'name' => 'Supervisor Sede',
            'role' => 'encargado',
            'location_id' => $location->id,
        ]);

        $cajasResponse = $this->actingAs($supervisor)->get(route('supervisor.cajas'));
        $cajasResponse->assertOk();
        $cajasResponse->assertSee('Carlos Mendoza');

        $reportResponse = $this->actingAs($supervisor)->get(route('supervisor.reporte', $cashSession->id));
        $reportResponse->assertOk();
        $reportResponse->assertSee('Carlos Mendoza');
        $reportResponse->assertSee('Volver a Supervisión');

        // Vendedor consulta su propio reporte de arqueo y ve botón de retorno a caja
        $vendedorReport = $this->actingAs($vendedor)->get(route('supervisor.reporte', $cashSession->id));
        $vendedorReport->assertOk();
        $vendedorReport->assertSee('Volver a Caja');
        $vendedorReport->assertDontSee('Volver a Supervisión');
    }
}
