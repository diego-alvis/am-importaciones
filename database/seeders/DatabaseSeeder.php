<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Siembra los datos base obligatorios en cualquier entorno y, solo en
     * desarrollo local, los datos de demostración.
     *
     * Los seeders base son idempotentes, por lo que `php artisan db:seed`
     * puede ejecutarse de forma segura en producción.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            LocationSeeder::class,
            CategorySeeder::class,
            AdminUserSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
