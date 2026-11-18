<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Parámetros de negocio iniciales (viáticos y comisiones).
 *
 * Solo crea las claves que faltan: nunca sobrescribe valores que el
 * administrador haya modificado después de la instalación.
 */
class SettingSeeder extends Seeder
{
    /**
     * @var list<array{key: string, value: string, description: string}>
     */
    private const SETTINGS = [
        ['key' => 'viatico_diario', 'value' => '11.00', 'description' => 'Monto fijo diario de viáticos por turno de stand'],
        ['key' => 'meta_comision', 'value' => '400.00', 'description' => 'Meta de ventas acumuladas en el turno para obtener comisión'],
        ['key' => 'bono_comision', 'value' => '10.00', 'description' => 'Bono entregado en efectivo por cada meta alcanzada (cada S/ 400)'],
    ];

    public function run(): void
    {
        foreach (self::SETTINGS as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'description' => $setting['description']],
            );
        }
    }
}
