<?php

use App\Models\CashSession;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Stand;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Hash;

test('base seeders can run repeatedly without duplicating records', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Setting::count())->toBe(3)
        ->and(Location::count())->toBe(4)
        ->and(Stand::count())->toBe(12)
        ->and(Category::count())->toBe(6)
        ->and(User::count())->toBe(1);
});

test('base seeders do not load demo data outside local environment', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Product::count())->toBe(0)
        ->and(Sale::count())->toBe(0);
});

test('reseeding keeps setting values changed by the administrator', function () {
    $this->seed(DatabaseSeeder::class);
    Setting::where('key', 'viatico_diario')->update(['value' => '15.00']);

    $this->seed(DatabaseSeeder::class);

    expect(Setting::where('key', 'viatico_diario')->value('value'))->toBe('15.00');
});

test('admin created outside local environment gets a random password', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $admin = User::where('email', AdminUserSeeder::ADMIN_EMAIL)->firstOrFail();
    expect($admin->role)->toBe('admin')
        ->and(Hash::check(AdminUserSeeder::LOCAL_PASSWORD, $admin->password))->toBeFalse();
});

test('demo seeder builds a usable dataset once', function () {
    $this->seed(DatabaseSeeder::class);

    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Product::count())->toBe(36)
        ->and(Stock::count())->toBe(36 * 4)
        ->and(CashSession::where('status', 'abierta')->count())->toBe(1)
        ->and(Sale::count())->toBe(8 + 10 + 12 + 14)
        ->and(Transfer::where('status', 'solicitado')->count())->toBe(1);
});
