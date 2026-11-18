<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Cuenta inicial del administrador general.
 *
 * En entornos locales usa la contraseña de desarrollo conocida. En cualquier
 * otro entorno genera una contraseña aleatoria y la muestra una sola vez en
 * la consola. Si el administrador ya existe, no se modifica.
 */
class AdminUserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@amimportaciones.com';

    public const LOCAL_PASSWORD = 'password123';

    public function run(): void
    {
        if (User::withTrashed()->where('email', self::ADMIN_EMAIL)->exists()) {
            return;
        }

        $isLocal = app()->environment(['local', 'testing']);
        $password = $isLocal ? self::LOCAL_PASSWORD : Str::password(16);

        User::create([
            'name' => 'Administrador Central',
            'email' => self::ADMIN_EMAIL,
            'password' => $password,
            'role' => UserRole::Admin->value,
            'location_id' => Location::where('name', LocationSeeder::ALMACEN_CENTRAL)->value('id'),
            'stand_id' => null,
        ]);

        if (! $isLocal) {
            $this->command?->warn('Administrador creado: '.self::ADMIN_EMAIL);
            $this->command?->warn("Contraseña generada (guárdala y cámbiala al ingresar): {$password}");
        }
    }
}
