<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\Stand;
use Illuminate\Database\Seeder;

/**
 * Sedes, almacén central y stands (tiendas) de cada sede.
 *
 * Es idempotente: puede ejecutarse varias veces sin duplicar registros.
 */
class LocationSeeder extends Seeder
{
    public const ALMACEN_CENTRAL = 'Almacén Central';

    public const SEDE_PLAZA_NORTE = 'Sede Plaza Norte';

    public const SEDE_LA_MUELA = 'Sede La Muela';

    public const SEDE_AV_54 = 'Sede Av 54';

    /**
     * Stands de cada sede de venta.
     *
     * @var array<string, list<string>>
     */
    private const STANDS = [
        self::SEDE_PLAZA_NORTE => ['Tienda A-10', 'Tienda A-5', 'Tienda Mi-7', 'Tienda L-11', 'Tienda L-13', 'Tienda J-14'],
        self::SEDE_LA_MUELA => ['Tienda A-1', 'Tienda B-1', 'Tienda D-18', 'Tienda B-2', 'Tienda C-2'],
        self::SEDE_AV_54 => ['Tienda Av 54'],
    ];

    private const BASE_CASH = 100.00;

    public function run(): void
    {
        Location::firstOrCreate(
            ['name' => self::ALMACEN_CENTRAL],
            ['type' => LocationType::Almacen->value],
        );

        foreach (self::STANDS as $locationName => $standNames) {
            $location = Location::firstOrCreate(
                ['name' => $locationName],
                ['type' => LocationType::Sede->value],
            );

            foreach ($standNames as $standName) {
                Stand::firstOrCreate(
                    ['location_id' => $location->id, 'name' => $standName],
                    ['base_cash' => self::BASE_CASH],
                );
            }
        }
    }
}
