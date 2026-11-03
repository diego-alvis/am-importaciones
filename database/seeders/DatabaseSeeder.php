<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CashSession;
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
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Configuraciones de viáticos y metas
        Setting::truncate();
        Setting::insert([
            ['key' => 'viatico_diario', 'value' => '11.00', 'description' => 'Monto fijo diario de viáticos por turno de stand'],
            ['key' => 'meta_comision', 'value' => '400.00', 'description' => 'Meta de ventas acumuladas en el turno para obtener comisión'],
            ['key' => 'bono_comision', 'value' => '10.00', 'description' => 'Bono entregado en efectivo por cada meta alcanzada (cada S/ 400)'],
        ]);

        // 2. Sedes / Ubicaciones (3 Sedes de Venta + 1 Almacén Central)
        $locAlmacen = Location::create([
            'name' => 'Almacén Central',
            'type' => 'almacen',
        ]);

        $locPlazaNorte = Location::create([
            'name' => 'Sede Plaza Norte',
            'type' => 'sede',
        ]);

        $locLaMuela = Location::create([
            'name' => 'Sede La Muela',
            'type' => 'sede',
        ]);

        $locAv54 = Location::create([
            'name' => 'Sede Av 54',
            'type' => 'sede',
        ]);

        // 3. Tiendas (Stands) por cada Sede
        // Plaza Norte: A-10, A-5, Mi-7, L-11, L-13, J-14
        $tiendasPlazaNorte = [
            'pn_a10' => Stand::create(['location_id' => $locPlazaNorte->id, 'name' => 'Tienda A-10', 'base_cash' => 100.00]),
            'pn_a5'  => Stand::create(['location_id' => $locPlazaNorte->id, 'name' => 'Tienda A-5', 'base_cash' => 100.00]),
            'pn_mi7' => Stand::create(['location_id' => $locPlazaNorte->id, 'name' => 'Tienda Mi-7', 'base_cash' => 100.00]),
            'pn_l11' => Stand::create(['location_id' => $locPlazaNorte->id, 'name' => 'Tienda L-11', 'base_cash' => 100.00]),
            'pn_l13' => Stand::create(['location_id' => $locPlazaNorte->id, 'name' => 'Tienda L-13', 'base_cash' => 100.00]),
            'pn_j14' => Stand::create(['location_id' => $locPlazaNorte->id, 'name' => 'Tienda J-14', 'base_cash' => 100.00]),
        ];

        // La Muela: A-1, B-1, D-18, B-2, C-2
        $tiendasLaMuela = [
            'lm_a1'  => Stand::create(['location_id' => $locLaMuela->id, 'name' => 'Tienda A-1', 'base_cash' => 100.00]),
            'lm_b1'  => Stand::create(['location_id' => $locLaMuela->id, 'name' => 'Tienda B-1', 'base_cash' => 100.00]),
            'lm_d18' => Stand::create(['location_id' => $locLaMuela->id, 'name' => 'Tienda D-18', 'base_cash' => 100.00]),
            'lm_b2'  => Stand::create(['location_id' => $locLaMuela->id, 'name' => 'Tienda B-2', 'base_cash' => 100.00]),
            'lm_c2'  => Stand::create(['location_id' => $locLaMuela->id, 'name' => 'Tienda C-2', 'base_cash' => 100.00]),
        ];

        // Av 54: 1 tienda
        $tiendaAv54 = Stand::create([
            'location_id' => $locAv54->id,
            'name' => 'Tienda Av 54',
            'base_cash' => 100.00,
        ]);

        // 4. Cuentas de Acceso del Sistema (Genéricas por rol y sede, sin nombres de personas fijos)
        $password = Hash::make('password123');

        // Admin General
        $userAdmin = User::create([
            'name' => 'Administrador Central',
            'email' => 'admin@amimportaciones.com',
            'password' => $password,
            'role' => 'admin',
            'location_id' => $locAlmacen->id,
            'stand_id' => null,
        ]);

        // Supervisores de Sede
        $userSupPlazaNorte = User::create([
            'name' => 'Supervisor Plaza Norte',
            'email' => 'supervisor.plazanorte@amimportaciones.com',
            'password' => $password,
            'role' => 'encargado',
            'location_id' => $locPlazaNorte->id,
            'stand_id' => $tiendasPlazaNorte['pn_a10']->id,
        ]);

        $userSupLaMuela = User::create([
            'name' => 'Supervisor La Muela',
            'email' => 'supervisor.lamuela@amimportaciones.com',
            'password' => $password,
            'role' => 'encargado',
            'location_id' => $locLaMuela->id,
            'stand_id' => $tiendasLaMuela['lm_a1']->id,
        ]);

        $userSupAv54 = User::create([
            'name' => 'Supervisor Av 54',
            'email' => 'supervisor.av54@amimportaciones.com',
            'password' => $password,
            'role' => 'encargado',
            'location_id' => $locAv54->id,
            'stand_id' => $tiendaAv54->id,
        ]);

        // Cuentas de Vendedor por Sede
        $userVendPlazaNorte = User::create([
            'name' => 'Vendedor Plaza Norte',
            'email' => 'vendedor.plazanorte@amimportaciones.com',
            'password' => $password,
            'role' => 'vendedor',
            'location_id' => $locPlazaNorte->id,
            'stand_id' => $tiendasPlazaNorte['pn_a5']->id,
        ]);

        $userVendLaMuela = User::create([
            'name' => 'Vendedor La Muela',
            'email' => 'vendedor.lamuela@amimportaciones.com',
            'password' => $password,
            'role' => 'vendedor',
            'location_id' => $locLaMuela->id,
            'stand_id' => $tiendasLaMuela['lm_b1']->id,
        ]);

        $userVendAv54 = User::create([
            'name' => 'Vendedor Av 54',
            'email' => 'vendedor.av54@amimportaciones.com',
            'password' => $password,
            'role' => 'vendedor',
            'location_id' => $locAv54->id,
            'stand_id' => $tiendaAv54->id,
        ]);

        // Almacén Central
        $userAlmacenero = User::create([
            'name' => 'Encargado de Almacén',
            'email' => 'almacen@amimportaciones.com',
            'password' => $password,
            'role' => 'almacenero',
            'location_id' => $locAlmacen->id,
            'stand_id' => null,
        ]);

        // 5. Clientes
        $customers = [
            Customer::create(['document_type' => 'DNI', 'document_number' => '47895612', 'name' => 'Juan Carlos Pérez', 'phone' => '987654321']),
            Customer::create(['document_type' => 'DNI', 'document_number' => '71234568', 'name' => 'María Fernanda Ruiz', 'phone' => '976543210']),
            Customer::create(['document_type' => 'RUC', 'document_number' => '20601234567', 'name' => 'Inversiones Tecnológicas SAC', 'phone' => '014567890']),
            Customer::create(['document_type' => 'DNI', 'document_number' => '45678912', 'name' => 'Renzo Morales Silva', 'phone' => '912345678']),
            Customer::create(['document_type' => 'DNI', 'document_number' => '78451296', 'name' => 'Valeria Castillo Paz', 'phone' => '955443322']),
        ];

        // 6. Categorías
        $catCarcasas = Category::create(['name' => 'Carcasas & Fundas']);
        $catMicas = Category::create(['name' => 'Vidrio Templado & Micas']);
        $catCargadores = Category::create(['name' => 'Cargadores & Adaptadores']);
        $catCables = Category::create(['name' => 'Cables USB & Tipo C']);
        $catAudio = Category::create(['name' => 'Auriculares & Audio']);
        $catAccesorios = Category::create(['name' => 'Soportes & Accesorios']);

        // 7. Catálogo de Productos
        $productsData = [
            // Carcasas (Requieren modelo de celular)
            ['category_id' => $catCarcasas->id, 'sku' => 'AM-CAR-001', 'name' => 'Funda Silicona Líquida Antigolpes', 'base_price' => 20.00],
            ['category_id' => $catCarcasas->id, 'sku' => 'AM-CAR-002', 'name' => 'Case Acrílico Transparente Shockproof', 'base_price' => 18.00],
            ['category_id' => $catCarcasas->id, 'sku' => 'AM-CAR-003', 'name' => 'Funda MagSafe Magnética Premium', 'base_price' => 35.00],
            ['category_id' => $catCarcasas->id, 'sku' => 'AM-CAR-004', 'name' => 'Case Armor Uso Rudo con Anillo', 'base_price' => 25.00],
            ['category_id' => $catCarcasas->id, 'sku' => 'AM-CAR-005', 'name' => 'Funda Cuero Flip Cover Billetera', 'base_price' => 28.00],
            ['category_id' => $catCarcasas->id, 'sku' => 'AM-CAR-006', 'name' => 'Funda Degradé Glitter Brillo', 'base_price' => 22.00],

            // Micas (Requieren modelo de celular)
            ['category_id' => $catMicas->id, 'sku' => 'AM-MIC-001', 'name' => 'Mica Cerámica Flexible Full Glue 9D', 'base_price' => 15.00],
            ['category_id' => $catMicas->id, 'sku' => 'AM-MIC-002', 'name' => 'Vidrio Templado 21D Antihuellas', 'base_price' => 12.00],
            ['category_id' => $catMicas->id, 'sku' => 'AM-MIC-003', 'name' => 'Mica Privacidad Anti-Espía Premium', 'base_price' => 25.00],
            ['category_id' => $catMicas->id, 'sku' => 'AM-MIC-004', 'name' => 'Mica Hidrogel Mate Autocurable HD', 'base_price' => 20.00],
            ['category_id' => $catMicas->id, 'sku' => 'AM-MIC-005', 'name' => 'Protector Lente Cámara Cristal Zafiro', 'base_price' => 10.00],
            ['category_id' => $catMicas->id, 'sku' => 'AM-MIC-006', 'name' => 'Mica Trasera Fibra de Carbono', 'base_price' => 8.00],

            // Cargadores (Sin modelo)
            ['category_id' => $catCargadores->id, 'sku' => 'AM-CRG-001', 'name' => 'Cargador Rápido 20W PD Tipo C', 'base_price' => 35.00],
            ['category_id' => $catCargadores->id, 'sku' => 'AM-CRG-002', 'name' => 'Cargador Carga Rápida 33W Turbo', 'base_price' => 45.00],
            ['category_id' => $catCargadores->id, 'sku' => 'AM-CRG-003', 'name' => 'Adaptador Auto Doble Puerto USB/C 38W', 'base_price' => 25.00],
            ['category_id' => $catCargadores->id, 'sku' => 'AM-CRG-004', 'name' => 'Cargador Inalámbrico Fast Charge 15W', 'base_price' => 50.00],
            ['category_id' => $catCargadores->id, 'sku' => 'AM-CRG-005', 'name' => 'Power Bank Batería Portátil 10000mAh', 'base_price' => 65.00],
            ['category_id' => $catCargadores->id, 'sku' => 'AM-CRG-006', 'name' => 'Cargador 65W GaN Multi-dispositivo', 'base_price' => 85.00],

            // Cables (Sin modelo)
            ['category_id' => $catCables->id, 'sku' => 'AM-CAB-001', 'name' => 'Cable Tipo C a Lightning Reforzado 1M', 'base_price' => 18.00],
            ['category_id' => $catCables->id, 'sku' => 'AM-CAB-002', 'name' => 'Cable Tipo C a Tipo C 60W Nailon', 'base_price' => 20.00],
            ['category_id' => $catCables->id, 'sku' => 'AM-CAB-003', 'name' => 'Cable USB a Tipo C Carga Rápida 3A', 'base_price' => 12.00],
            ['category_id' => $catCables->id, 'sku' => 'AM-CAB-004', 'name' => 'Cable Micro USB Carga y Datos 2M', 'base_price' => 10.00],
            ['category_id' => $catCables->id, 'sku' => 'AM-CAB-005', 'name' => 'Cable 3 en 1 Universal Retráctil', 'base_price' => 22.00],
            ['category_id' => $catCables->id, 'sku' => 'AM-CAB-006', 'name' => 'Cable Magnético 360 Carga Rápida', 'base_price' => 25.00],

            // Audio (Sin modelo)
            ['category_id' => $catAudio->id, 'sku' => 'AM-AUD-001', 'name' => 'Auriculares Bluetooth TWS True Wireless', 'base_price' => 55.00],
            ['category_id' => $catAudio->id, 'sku' => 'AM-AUD-002', 'name' => 'Auriculares Manos Libres Cable Jack 3.5mm', 'base_price' => 12.00],
            ['category_id' => $catAudio->id, 'sku' => 'AM-AUD-003', 'name' => 'Audífonos Cable Entrada Tipo C con Micro', 'base_price' => 18.00],
            ['category_id' => $catAudio->id, 'sku' => 'AM-AUD-004', 'name' => 'Parlante Mini Bluetooth Resistente Agua', 'base_price' => 45.00],
            ['category_id' => $catAudio->id, 'sku' => 'AM-AUD-005', 'name' => 'Adaptador Lightning a Jack 3.5mm Audio', 'base_price' => 15.00],
            ['category_id' => $catAudio->id, 'sku' => 'AM-AUD-006', 'name' => 'Adaptador Tipo C a Jack 3.5mm con DAC', 'base_price' => 16.00],

            // Soportes y Accesorios (Sin modelo)
            ['category_id' => $catAccesorios->id, 'sku' => 'AM-ACC-001', 'name' => 'Soporte Celular Magnético para Auto Rejilla', 'base_price' => 15.00],
            ['category_id' => $catAccesorios->id, 'sku' => 'AM-ACC-002', 'name' => 'Soporte de Mesa Ajustable Plegable Aluminio', 'base_price' => 22.00],
            ['category_id' => $catAccesorios->id, 'sku' => 'AM-ACC-003', 'name' => 'PopSocket Sujetador Agarre Diseños Variados', 'base_price' => 8.00],
            ['category_id' => $catAccesorios->id, 'sku' => 'AM-ACC-004', 'name' => 'Aro de Luz LED para Selfie Recargable', 'base_price' => 14.00],
            ['category_id' => $catAccesorios->id, 'sku' => 'AM-ACC-005', 'name' => 'Lanyard Correa para Cuello Anticaídas', 'base_price' => 10.00],
            ['category_id' => $catAccesorios->id, 'sku' => 'AM-ACC-006', 'name' => 'Kit Limpieza Pantallas y Audífonos Multifunción', 'base_price' => 15.00],
        ];

        $createdProducts = [];
        foreach ($productsData as $data) {
            $createdProducts[] = Product::create($data);
        }

        // 8. Stock distribuido en Almacén Central y las 3 Sedes
        $sedes = [$locAlmacen, $locPlazaNorte, $locLaMuela, $locAv54];

        foreach ($createdProducts as $index => $prod) {
            foreach ($sedes as $sede) {
                // El almacén central tiene stock alto; las sedes tienen stock operativo
                if ($sede->type === 'almacen') {
                    $qty = rand(80, 200);
                } else {
                    // Productos con stock bajo deliberado para demostrar alertas en dashboard
                    $qty = ($index < 4) ? rand(2, 4) : rand(12, 40);
                }

                Stock::create([
                    'location_id' => $sede->id,
                    'product_id' => $prod->id,
                    'quantity' => $qty,
                    'min_stock' => 5,
                ]);
            }
        }

        // 9. Sesiones de Caja
        $now = Carbon::now();

        // Sesión 1: ABIERTA HOY en Plaza Norte (Tienda A-5) -> Lista para usar en POS
        $sessionAbiertaPN = CashSession::create([
            'user_id' => $userVendPlazaNorte->id,
            'stand_id' => $tiendasPlazaNorte['pn_a5']->id,
            'opening_time' => (clone $now)->setTime(9, 0),
            'closing_time' => null,
            'expected_cash' => 100.00,
            'declared_cash' => null,
            'approved_by' => null,
            'status' => 'abierta',
        ]);

        // Sesión 2: EN PRECIERRE en La Muela (Tienda A-1) -> Pendiente de aprobación por Supervisor / Admin
        $sessionPrecierreLM = CashSession::create([
            'user_id' => $userVendLaMuela->id,
            'stand_id' => $tiendasLaMuela['lm_a1']->id,
            'opening_time' => (clone $now)->setTime(10, 0),
            'closing_time' => (clone $now)->setTime(21, 0),
            'expected_cash' => 100.00,
            'declared_cash' => 380.00,
            'approved_by' => null,
            'status' => 'precierre',
        ]);

        // Sesión 3: APROBADA (Ayer en Av 54) -> Con reporte final listo para el Admin
        $sessionAprobadaAv54 = CashSession::create([
            'user_id' => $userVendAv54->id,
            'stand_id' => $tiendaAv54->id,
            'opening_time' => (clone $now)->subDay()->setTime(9, 30),
            'closing_time' => (clone $now)->subDay()->setTime(21, 30),
            'expected_cash' => 100.00,
            'declared_cash' => 415.00,
            'approved_by' => $userSupAv54->id,
            'status' => 'aprobada',
        ]);

        // Sesión 4: APROBADA (Hace 2 días en Plaza Norte A-10)
        $sessionAprobadaPN10 = CashSession::create([
            'user_id' => $userSupPlazaNorte->id,
            'stand_id' => $tiendasPlazaNorte['pn_a10']->id,
            'opening_time' => (clone $now)->subDays(2)->setTime(9, 0),
            'closing_time' => (clone $now)->subDays(2)->setTime(21, 0),
            'expected_cash' => 100.00,
            'declared_cash' => 520.00,
            'approved_by' => $userAdmin->id,
            'status' => 'aprobada',
        ]);

        // 10. Ventas realistas asociadas a cada sesión
        $deviceModels = ['iPhone 13', 'iPhone 14 Pro', 'Samsung S23', 'Xiaomi Redmi Note 12', 'Samsung A54', 'Motorola G52'];

        $crearVenta = function ($session, $user, $customer, $fecha, $status = 'completada') use ($createdProducts, $deviceModels) {
            $cantItems = rand(1, 3);
            $selectedProducts = collect($createdProducts)->random($cantItems);
            
            $totalVenta = 0.0;
            $itemsData = [];

            foreach ($selectedProducts as $prod) {
                $qty = rand(1, 2);
                $subtotal = $qty * (float) $prod->base_price;
                $totalVenta += $subtotal;
                
                // Solo asignar modelo de celular a Cases y Micas
                $model = $prod->requiresDeviceModel() ? $deviceModels[array_rand($deviceModels)] : null;

                $itemsData[] = [
                    'product_id' => $prod->id,
                    'device_model' => $model,
                    'quantity' => $qty,
                    'unit_price' => $prod->base_price,
                ];
            }

            $sale = Sale::create([
                'cash_session_id' => $session->id,
                'user_id' => $user->id,
                'customer_id' => $customer ? $customer->id : null,
                'total_amount' => $totalVenta,
                'status' => $status,
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ]);

            foreach ($itemsData as $item) {
                SaleItem::create(array_merge($item, [
                    'sale_id' => $sale->id,
                    'created_at' => $fecha,
                    'updated_at' => $fecha,
                ]));
            }

            // Métodos de pago
            $metodos = ['efectivo', 'yape', 'plin', 'tarjeta', 'mixto'];
            $metodo = $metodos[array_rand($metodos)];

            if ($metodo === 'mixto' && $totalVenta > 20) {
                $parteEfectivo = round($totalVenta / 2, 2);
                $parteYape = $totalVenta - $parteEfectivo;
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => 'efectivo',
                    'amount' => $parteEfectivo,
                    'reference_code' => null,
                    'created_at' => $fecha,
                ]);
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => 'yape',
                    'amount' => $parteYape,
                    'reference_code' => 'OP-' . rand(100000, 999999),
                    'created_at' => $fecha,
                ]);
            } else {
                $pMethod = ($metodo === 'mixto') ? 'efectivo' : $metodo;
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => $pMethod,
                    'amount' => $totalVenta,
                    'reference_code' => in_array($pMethod, ['yape', 'plin', 'tarjeta']) ? 'TX-' . rand(100000, 999999) : null,
                    'created_at' => $fecha,
                ]);
            }

            return $sale;
        };

        // Ventas Sesión Abierta (Hoy en Plaza Norte A-5) - 8 ventas
        for ($i = 0; $i < 8; $i++) {
            $cust = rand(0, 1) ? $customers[array_rand($customers)] : null;
            $crearVenta($sessionAbiertaPN, $userVendPlazaNorte, $cust, (clone $now)->setTime(9 + $i, rand(5, 55)));
        }

        // Ventas Sesión Precierre (Hoy en La Muela A-1) - 10 ventas
        for ($i = 0; $i < 10; $i++) {
            $cust = rand(0, 1) ? $customers[array_rand($customers)] : null;
            $crearVenta($sessionPrecierreLM, $userVendLaMuela, $cust, (clone $now)->setTime(10 + $i, rand(5, 55)));
        }

        // Ventas Sesión Aprobada Av 54 (Ayer) - 12 ventas
        for ($i = 0; $i < 12; $i++) {
            $cust = rand(0, 1) ? $customers[array_rand($customers)] : null;
            $crearVenta($sessionAprobadaAv54, $userVendAv54, $cust, (clone $now)->subDay()->setTime(10 + $i, rand(5, 55)));
        }

        // Ventas Sesión Aprobada Plaza Norte A-10 (Hace 2 días) - 14 ventas
        for ($i = 0; $i < 14; $i++) {
            $cust = rand(0, 1) ? $customers[array_rand($customers)] : null;
            $crearVenta($sessionAprobadaPN10, $userSupPlazaNorte, $cust, (clone $now)->subDays(2)->setTime(10 + $i, rand(5, 55)));
        }

        // 11. Transferencias desde Almacén Central hacia las Sedes
        $transfer1 = Transfer::create([
            'from_location_id' => $locAlmacen->id,
            'to_location_id' => $locPlazaNorte->id,
            'status' => 'recibido',
            'created_at' => Carbon::now()->subDays(2),
        ]);
        TransferItem::create(['transfer_id' => $transfer1->id, 'product_id' => $createdProducts[0]->id, 'quantity' => 20]);
        TransferItem::create(['transfer_id' => $transfer1->id, 'product_id' => $createdProducts[6]->id, 'quantity' => 30]);

        $transfer2 = Transfer::create([
            'from_location_id' => $locAlmacen->id,
            'to_location_id' => $locLaMuela->id,
            'status' => 'recibido',
            'created_at' => Carbon::now()->subDay(),
        ]);
        TransferItem::create(['transfer_id' => $transfer2->id, 'product_id' => $createdProducts[12]->id, 'quantity' => 15]);
        TransferItem::create(['transfer_id' => $transfer2->id, 'product_id' => $createdProducts[18]->id, 'quantity' => 25]);

        $transfer3 = Transfer::create([
            'from_location_id' => $locAlmacen->id,
            'to_location_id' => $locAv54->id,
            'status' => 'pendiente',
            'created_at' => Carbon::now()->subHours(4),
        ]);
        TransferItem::create(['transfer_id' => $transfer3->id, 'product_id' => $createdProducts[2]->id, 'quantity' => 10]);
        TransferItem::create(['transfer_id' => $transfer3->id, 'product_id' => $createdProducts[8]->id, 'quantity' => 15]);
    }
}
