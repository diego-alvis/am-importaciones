<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stand;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_sees_inventory_scoped_to_active_stand_location(): void
    {
        $locPlazaNorte = Location::create(['name' => 'Sede Plaza Norte', 'type' => 'sede']);
        $locLaMuela = Location::create(['name' => 'Sede La Muela', 'type' => 'sede']);
        $locAlmacen = Location::create(['name' => 'Almacén Central', 'type' => 'almacen']);

        $standPn = Stand::create(['name' => 'Tienda PN-1', 'location_id' => $locPlazaNorte->id, 'base_cash' => 100]);
        $category = Category::create(['name' => 'Accesorios']);

        $product = Product::create([
            'name' => 'Cargador Rápido 20W',
            'sku' => 'CHG-20W',
            'category_id' => $category->id,
            'base_price' => 35.00,
        ]);

        // Stock: 10 en Plaza Norte, 0 en La Muela, 50 en Almacén
        Stock::create(['product_id' => $product->id, 'location_id' => $locPlazaNorte->id, 'quantity' => 10, 'min_stock' => 5]);
        Stock::create(['product_id' => $product->id, 'location_id' => $locLaMuela->id, 'quantity' => 0, 'min_stock' => 5]);
        Stock::create(['product_id' => $product->id, 'location_id' => $locAlmacen->id, 'quantity' => 50, 'min_stock' => 5]);

        $vendedor = User::factory()->create([
            'role' => 'vendedor',
            'location_id' => $locPlazaNorte->id,
            'stand_id' => $standPn->id,
        ]);

        $response = $this->actingAs($vendedor)->get(route('inventory.index'));
        $response->assertOk();
        $response->assertSee('Stock en Sede Plaza Norte');
        $response->assertSee('10 und');
        $response->assertSee('50 und');
        $response->assertSee('Almacén Central');

        // Filtrar por agotados (sin_stock) en Plaza Norte no debe incluir el producto (porque tiene 10)
        $responseFilter = $this->actingAs($vendedor)->get(route('inventory.index', ['stock_status' => 'sin_stock']));
        $responseFilter->assertOk();
        $responseFilter->assertDontSee('Cargador Rápido 20W');
    }

    public function test_admin_can_filter_inventory_by_specific_location(): void
    {
        $locPlazaNorte = Location::create(['name' => 'Sede Plaza Norte', 'type' => 'sede']);
        $locAlmacen = Location::create(['name' => 'Almacén Central', 'type' => 'almacen']);

        $category = Category::create(['name' => 'Cables']);
        $product = Product::create([
            'name' => 'Cable USB-C 1M',
            'sku' => 'CAB-USBC',
            'category_id' => $category->id,
            'base_price' => 15.00,
        ]);

        Stock::create(['product_id' => $product->id, 'location_id' => $locPlazaNorte->id, 'quantity' => 0, 'min_stock' => 5]);
        Stock::create(['product_id' => $product->id, 'location_id' => $locAlmacen->id, 'quantity' => 20, 'min_stock' => 5]);

        $admin = User::factory()->create(['role' => 'admin']);

        // Vista general muestra todas las sedes
        $response = $this->actingAs($admin)->get(route('inventory.index'));
        $response->assertOk();
        $response->assertSee('Stock por Sede');

        // Filtrar por Sede Plaza Norte muestra scoped location
        $responseScoped = $this->actingAs($admin)->get(route('inventory.index', ['location_id' => $locPlazaNorte->id]));
        $responseScoped->assertOk();
        $responseScoped->assertSee('Stock en Sede Plaza Norte');
    }
}
