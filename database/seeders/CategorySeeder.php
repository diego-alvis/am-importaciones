<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Categorías del catálogo de productos.
 *
 * Los nombres que contienen "Carcasas" o "Micas" activan la exigencia
 * del modelo de celular en el POS (ver Product::requiresDeviceModel()).
 */
class CategorySeeder extends Seeder
{
    public const CARCASAS = 'Carcasas & Fundas';

    public const MICAS = 'Vidrio Templado & Micas';

    public const CARGADORES = 'Cargadores & Adaptadores';

    public const CABLES = 'Cables USB & Tipo C';

    public const AUDIO = 'Auriculares & Audio';

    public const ACCESORIOS = 'Soportes & Accesorios';

    public function run(): void
    {
        foreach ([self::CARCASAS, self::MICAS, self::CARGADORES, self::CABLES, self::AUDIO, self::ACCESORIOS] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
