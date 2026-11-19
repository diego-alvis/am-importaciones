<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\TransferStatus;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Stand;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Datos de demostración para desarrollo: usuarios por rol, clientes,
 * catálogo, stock, turnos de caja, ventas y transferencias.
 *
 * Requiere los seeders base (sedes, stands, categorías y admin).
 * NO debe ejecutarse en producción. Si ya existen productos, no hace nada.
 */
class DemoSeeder extends Seeder
{
    /**
     * Semilla fija para que los datos aleatorios sean reproducibles.
     */
    private const RANDOM_SEED = 2026;

    /**
     * @var list<string>
     */
    private const DEVICE_MODELS = ['iPhone 13', 'iPhone 14 Pro', 'Samsung S23', 'Xiaomi Redmi Note 12', 'Samsung A54', 'Motorola G52'];

    /**
     * Catálogo de productos agrupado por categoría.
     *
     * @var array<string, list<array{sku: string, name: string, base_price: float}>>
     */
    private const PRODUCTS = [
        CategorySeeder::CARCASAS => [
            ['sku' => 'AM-CAR-001', 'name' => 'Funda Silicona Líquida Antigolpes', 'base_price' => 20.00],
            ['sku' => 'AM-CAR-002', 'name' => 'Case Acrílico Transparente Shockproof', 'base_price' => 18.00],
            ['sku' => 'AM-CAR-003', 'name' => 'Funda MagSafe Magnética Premium', 'base_price' => 35.00],
            ['sku' => 'AM-CAR-004', 'name' => 'Case Armor Uso Rudo con Anillo', 'base_price' => 25.00],
            ['sku' => 'AM-CAR-005', 'name' => 'Funda Cuero Flip Cover Billetera', 'base_price' => 28.00],
            ['sku' => 'AM-CAR-006', 'name' => 'Funda Degradé Glitter Brillo', 'base_price' => 22.00],
        ],
        CategorySeeder::MICAS => [
            ['sku' => 'AM-MIC-001', 'name' => 'Mica Cerámica Flexible Full Glue 9D', 'base_price' => 15.00],
            ['sku' => 'AM-MIC-002', 'name' => 'Vidrio Templado 21D Antihuellas', 'base_price' => 12.00],
            ['sku' => 'AM-MIC-003', 'name' => 'Mica Privacidad Anti-Espía Premium', 'base_price' => 25.00],
            ['sku' => 'AM-MIC-004', 'name' => 'Mica Hidrogel Mate Autocurable HD', 'base_price' => 20.00],
            ['sku' => 'AM-MIC-005', 'name' => 'Protector Lente Cámara Cristal Zafiro', 'base_price' => 10.00],
            ['sku' => 'AM-MIC-006', 'name' => 'Mica Trasera Fibra de Carbono', 'base_price' => 8.00],
        ],
        CategorySeeder::CARGADORES => [
            ['sku' => 'AM-CRG-001', 'name' => 'Cargador Rápido 20W PD Tipo C', 'base_price' => 35.00],
            ['sku' => 'AM-CRG-002', 'name' => 'Cargador Carga Rápida 33W Turbo', 'base_price' => 45.00],
            ['sku' => 'AM-CRG-003', 'name' => 'Adaptador Auto Doble Puerto USB/C 38W', 'base_price' => 25.00],
            ['sku' => 'AM-CRG-004', 'name' => 'Cargador Inalámbrico Fast Charge 15W', 'base_price' => 50.00],
            ['sku' => 'AM-CRG-005', 'name' => 'Power Bank Batería Portátil 10000mAh', 'base_price' => 65.00],
            ['sku' => 'AM-CRG-006', 'name' => 'Cargador 65W GaN Multi-dispositivo', 'base_price' => 85.00],
        ],
        CategorySeeder::CABLES => [
            ['sku' => 'AM-CAB-001', 'name' => 'Cable Tipo C a Lightning Reforzado 1M', 'base_price' => 18.00],
            ['sku' => 'AM-CAB-002', 'name' => 'Cable Tipo C a Tipo C 60W Nailon', 'base_price' => 20.00],
            ['sku' => 'AM-CAB-003', 'name' => 'Cable USB a Tipo C Carga Rápida 3A', 'base_price' => 12.00],
            ['sku' => 'AM-CAB-004', 'name' => 'Cable Micro USB Carga y Datos 2M', 'base_price' => 10.00],
            ['sku' => 'AM-CAB-005', 'name' => 'Cable 3 en 1 Universal Retráctil', 'base_price' => 22.00],
            ['sku' => 'AM-CAB-006', 'name' => 'Cable Magnético 360 Carga Rápida', 'base_price' => 25.00],
        ],
        CategorySeeder::AUDIO => [
            ['sku' => 'AM-AUD-001', 'name' => 'Auriculares Bluetooth TWS True Wireless', 'base_price' => 55.00],
            ['sku' => 'AM-AUD-002', 'name' => 'Auriculares Manos Libres Cable Jack 3.5mm', 'base_price' => 12.00],
            ['sku' => 'AM-AUD-003', 'name' => 'Audífonos Cable Entrada Tipo C con Micro', 'base_price' => 18.00],
            ['sku' => 'AM-AUD-004', 'name' => 'Parlante Mini Bluetooth Resistente Agua', 'base_price' => 45.00],
            ['sku' => 'AM-AUD-005', 'name' => 'Adaptador Lightning a Jack 3.5mm Audio', 'base_price' => 15.00],
            ['sku' => 'AM-AUD-006', 'name' => 'Adaptador Tipo C a Jack 3.5mm con DAC', 'base_price' => 16.00],
        ],
        CategorySeeder::ACCESORIOS => [
            ['sku' => 'AM-ACC-001', 'name' => 'Soporte Celular Magnético para Auto Rejilla', 'base_price' => 15.00],
            ['sku' => 'AM-ACC-002', 'name' => 'Soporte de Mesa Ajustable Plegable Aluminio', 'base_price' => 22.00],
            ['sku' => 'AM-ACC-003', 'name' => 'PopSocket Sujetador Agarre Diseños Variados', 'base_price' => 8.00],
            ['sku' => 'AM-ACC-004', 'name' => 'Aro de Luz LED para Selfie Recargable', 'base_price' => 14.00],
            ['sku' => 'AM-ACC-005', 'name' => 'Lanyard Correa para Cuello Anticaídas', 'base_price' => 10.00],
            ['sku' => 'AM-ACC-006', 'name' => 'Kit Limpieza Pantallas y Audífonos Multifunción', 'base_price' => 15.00],
        ],
    ];

    public function run(): void
    {
        if (Product::exists()) {
            $this->command?->info('DemoSeeder omitido: ya existen productos en la base de datos.');

            return;
        }

        mt_srand(self::RANDOM_SEED);

        $almacen = $this->location(LocationSeeder::ALMACEN_CENTRAL);
        $plazaNorte = $this->location(LocationSeeder::SEDE_PLAZA_NORTE);
        $laMuela = $this->location(LocationSeeder::SEDE_LA_MUELA);
        $av54 = $this->location(LocationSeeder::SEDE_AV_54);

        $this->call(StandAccountsSeeder::class);
        $users = $this->loadAccounts();
        $customers = $this->createCustomers();
        $products = $this->createProducts();

        $this->createStock($products, [$almacen, $plazaNorte, $laMuela, $av54]);
        $this->createCashSessionsWithSales($users, $customers, $products, $plazaNorte, $laMuela, $av54);
        $this->createTransfers($users, $products, $almacen, $plazaNorte, $laMuela, $av54);
    }

    private function location(string $name): Location
    {
        return Location::where('name', $name)->firstOrFail();
    }

    private function stand(Location $location, string $name): Stand
    {
        return Stand::where('location_id', $location->id)->where('name', $name)->firstOrFail();
    }

    /**
     * Carga las cuentas oficiales por rol y stand sembradas por StandAccountsSeeder.
     *
     * @return array<string, User>
     */
    private function loadAccounts(): array
    {
        return [
            'admin' => User::where('email', AdminUserSeeder::ADMIN_EMAIL)->firstOrFail(),
            'sup_plaza_norte' => User::where('email', 'supervisor.plazanorte@amimportaciones.com')->firstOrFail(),
            'sup_la_muela' => User::where('email', 'supervisor.lamuela@amimportaciones.com')->firstOrFail(),
            'sup_av54' => User::where('email', 'supervisor.av54@amimportaciones.com')->firstOrFail(),
            'vend_plaza_norte' => User::where('email', 'plazanorte.a5@amimportaciones.com')->firstOrFail(),
            'vend_la_muela' => User::where('email', 'lamuela.a1@amimportaciones.com')->firstOrFail(),
            'vend_av54' => User::where('email', 'supervisor.av54@amimportaciones.com')->firstOrFail(),
            'almacenero' => User::where('email', 'almacen@amimportaciones.com')->firstOrFail(),
        ];
    }

    /**
     * @return list<Customer>
     */
    private function createCustomers(): array
    {
        return [
            Customer::create(['document_type' => 'DNI', 'document_number' => '47895612', 'name' => 'Juan Carlos Pérez', 'phone' => '987654321']),
            Customer::create(['document_type' => 'DNI', 'document_number' => '71234568', 'name' => 'María Fernanda Ruiz', 'phone' => '976543210']),
            Customer::create(['document_type' => 'RUC', 'document_number' => '20601234567', 'name' => 'Inversiones Tecnológicas SAC', 'phone' => '014567890']),
            Customer::create(['document_type' => 'DNI', 'document_number' => '45678912', 'name' => 'Renzo Morales Silva', 'phone' => '912345678']),
            Customer::create(['document_type' => 'DNI', 'document_number' => '78451296', 'name' => 'Valeria Castillo Paz', 'phone' => '955443322']),
        ];
    }

    /**
     * @return list<Product>
     */
    private function createProducts(): array
    {
        $products = [];

        foreach (self::PRODUCTS as $categoryName => $items) {
            $category = Category::where('name', $categoryName)->firstOrFail();

            foreach ($items as $item) {
                $products[] = Product::factory()->for($category)->create($item);
            }
        }

        return $products;
    }

    /**
     * Stock alto en el almacén central y operativo en las sedes. Los primeros
     * productos quedan con stock bajo para mostrar las alertas del dashboard.
     *
     * @param  list<Product>  $products
     * @param  list<Location>  $locations
     */
    private function createStock(array $products, array $locations): void
    {
        foreach ($products as $index => $product) {
            foreach ($locations as $location) {
                $quantity = match (true) {
                    $location->type === LocationType::Almacen->value => rand(80, 200),
                    $index < 4 => rand(2, 4),
                    default => rand(12, 40),
                };

                Stock::factory()->for($location)->for($product)->create([
                    'quantity' => $quantity,
                    'min_stock' => 5,
                ]);
            }
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  list<Customer>  $customers
     * @param  list<Product>  $products
     */
    private function createCashSessionsWithSales(array $users, array $customers, array $products, Location $plazaNorte, Location $laMuela, Location $av54): void
    {
        $now = Carbon::now();

        $sessions = [
            // Abierta hoy en Plaza Norte (Tienda A-5): lista para usar en el POS.
            [
                'session' => CashSession::factory()->abierta()->create([
                    'user_id' => $users['vend_plaza_norte']->id,
                    'stand_id' => $this->stand($plazaNorte, 'Tienda A-5')->id,
                    'operator_name' => 'María Torres',
                    'opening_time' => $now->copy()->setTime(9, 0),
                ]),
                'seller' => $users['vend_plaza_norte'],
                'count' => 8,
                'day' => $now->copy(),
                'first_hour' => 9,
            ],
            // En precierre en La Muela (Tienda A-1): pendiente de aprobación.
            [
                'session' => CashSession::factory()->precierre()->create([
                    'user_id' => $users['vend_la_muela']->id,
                    'stand_id' => $this->stand($laMuela, 'Tienda A-1')->id,
                    'operator_name' => 'Carlos Mendoza',
                    'opening_time' => $now->copy()->setTime(10, 0),
                    'closing_time' => $now->copy()->setTime(21, 0),
                    'declared_cash' => 380.00,
                ]),
                'seller' => $users['vend_la_muela'],
                'count' => 10,
                'day' => $now->copy(),
                'first_hour' => 10,
            ],
            // Aprobada ayer en Av 54.
            [
                'session' => CashSession::factory()->aprobada()->create([
                    'user_id' => $users['vend_av54']->id,
                    'stand_id' => $this->stand($av54, 'Tienda Av 54')->id,
                    'operator_name' => 'Lucía Ramos',
                    'opening_time' => $now->copy()->subDay()->setTime(9, 30),
                    'closing_time' => $now->copy()->subDay()->setTime(21, 30),
                    'declared_cash' => 415.00,
                    'approved_by' => $users['sup_av54']->id,
                ]),
                'seller' => $users['vend_av54'],
                'count' => 12,
                'day' => $now->copy()->subDay(),
                'first_hour' => 10,
            ],
            // Aprobada hace 2 días en Plaza Norte (Tienda A-10).
            [
                'session' => CashSession::factory()->aprobada()->create([
                    'user_id' => $users['sup_plaza_norte']->id,
                    'stand_id' => $this->stand($plazaNorte, 'Tienda A-10')->id,
                    'operator_name' => 'Jorge Valdivia',
                    'opening_time' => $now->copy()->subDays(2)->setTime(9, 0),
                    'closing_time' => $now->copy()->subDays(2)->setTime(21, 0),
                    'declared_cash' => 520.00,
                    'approved_by' => $users['admin']->id,
                ]),
                'seller' => $users['sup_plaza_norte'],
                'count' => 14,
                'day' => $now->copy()->subDays(2),
                'first_hour' => 10,
            ],
        ];

        foreach ($sessions as $config) {
            for ($i = 0; $i < $config['count']; $i++) {
                $customer = rand(0, 1) ? $customers[array_rand($customers)] : null;
                $soldAt = $config['day']->copy()->setTime($config['first_hour'] + $i, rand(5, 55));

                $this->createSale($config['session'], $config['seller'], $customer, $soldAt, $products);
            }
        }
    }

    /**
     * @param  list<Product>  $products
     */
    private function createSale(CashSession $session, User $seller, ?Customer $customer, Carbon $soldAt, array $products): Sale
    {
        /** @var Collection<int, Product> $selectedProducts */
        $selectedProducts = collect($products)->random(rand(1, 3));

        $total = 0.0;
        $items = [];

        foreach ($selectedProducts as $product) {
            $quantity = rand(1, 2);
            $total += $quantity * (float) $product->base_price;

            $items[] = [
                'product_id' => $product->id,
                'device_model' => $product->requiresDeviceModel() ? self::DEVICE_MODELS[array_rand(self::DEVICE_MODELS)] : null,
                'quantity' => $quantity,
                'unit_price' => $product->base_price,
            ];
        }

        $sale = Sale::factory()->for($session)->create([
            'user_id' => $seller->id,
            'operator_name' => $session->operator_name ?: $seller->name,
            'customer_id' => $customer?->id,
            'total_amount' => $total,
            'status' => SaleStatus::Completada->value,
            'created_at' => $soldAt,
            'updated_at' => $soldAt,
        ]);

        foreach ($items as $item) {
            SaleItem::factory()->for($sale)->create([...$item, 'created_at' => $soldAt, 'updated_at' => $soldAt]);
        }

        $this->createPayments($sale, $total, $soldAt);

        return $sale;
    }

    private function createPayments(Sale $sale, float $total, Carbon $paidAt): void
    {
        $methods = ['efectivo', 'yape', 'plin', 'tarjeta', 'mixto'];
        $method = $methods[array_rand($methods)];

        if ($method === 'mixto' && $total > 20) {
            $cashPart = round($total / 2, 2);

            SalePayment::factory()->for($sale)->method(PaymentMethod::Efectivo)->create(['amount' => $cashPart, 'created_at' => $paidAt]);
            SalePayment::factory()->for($sale)->method(PaymentMethod::Yape)->create(['amount' => $total - $cashPart, 'created_at' => $paidAt]);

            return;
        }

        $paymentMethod = $method === 'mixto' ? PaymentMethod::Efectivo : PaymentMethod::from($method);

        SalePayment::factory()->for($sale)->method($paymentMethod)->create(['amount' => $total, 'created_at' => $paidAt]);
    }

    /**
     * Pedidos del almacén central hacia las sedes en distintos estados.
     *
     * @param  array<string, User>  $users
     * @param  list<Product>  $products
     */
    private function createTransfers(array $users, array $products, Location $almacen, Location $plazaNorte, Location $laMuela, Location $av54): void
    {
        $transfers = [
            [$plazaNorte, $users['vend_plaza_norte'], TransferStatus::Recibido, Carbon::now()->subDays(2), [[0, 20], [6, 30]]],
            [$laMuela, $users['vend_la_muela'], TransferStatus::Recibido, Carbon::now()->subDay(), [[12, 15], [18, 25]]],
            [$av54, $users['vend_av54'], TransferStatus::Solicitado, Carbon::now()->subHours(4), [[2, 10], [8, 15]]],
        ];

        foreach ($transfers as [$destination, $requester, $status, $createdAt, $items]) {
            $transfer = Transfer::factory()->create([
                'from_location_id' => $almacen->id,
                'to_location_id' => $destination->id,
                'requested_by' => $requester->id,
                'dispatched_by' => $status === TransferStatus::Solicitado ? null : $users['almacenero']->id,
                'status' => $status->value,
                'created_at' => $createdAt,
            ]);

            foreach ($items as [$productIndex, $quantity]) {
                TransferItem::factory()->for($transfer)->create([
                    'product_id' => $products[$productIndex]->id,
                    'quantity' => $quantity,
                ]);
            }
        }
    }
}
