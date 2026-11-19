<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\CashSession;
use App\Models\Location;
use App\Models\Sale;
use App\Models\Stand;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Reestructura y garantiza las 14 cuentas oficiales del sistema:
 * - 1 Administrador general
 * - 1 Almacenero central
 * - 1 Supervisor por sede con stand fijo (A-10, D-18, Tienda Av 54)
 * - 1 Cuenta fija por cada stand restante de ventas
 *
 * Es idempotente y reasigna registros históricos de cuentas genéricas antiguas si existieran.
 */
class StandAccountsSeeder extends Seeder
{
    public const LOCAL_PASSWORD = 'password123';

    public function run(): void
    {
        $isLocal = app()->environment(['local', 'testing']);
        $defaultPasswordHash = $isLocal ? Hash::make(self::LOCAL_PASSWORD) : Hash::make(Str::password(16));

        $almacen = Location::where('name', LocationSeeder::ALMACEN_CENTRAL)->firstOrFail();
        $plazaNorte = Location::where('name', LocationSeeder::SEDE_PLAZA_NORTE)->firstOrFail();
        $laMuela = Location::where('name', LocationSeeder::SEDE_LA_MUELA)->firstOrFail();
        $av54 = Location::where('name', LocationSeeder::SEDE_AV_54)->firstOrFail();

        $accounts = [
            // Almacén Central
            [
                'email' => 'almacen@amimportaciones.com',
                'name' => 'Encargado de Almacén',
                'role' => UserRole::Almacenero->value,
                'location_id' => $almacen->id,
                'stand_id' => null,
            ],

            // Sede Plaza Norte (Supervisor en A-10 + 5 stands)
            [
                'email' => 'supervisor.plazanorte@amimportaciones.com',
                'name' => 'Supervisor Plaza Norte',
                'role' => UserRole::Encargado->value,
                'location_id' => $plazaNorte->id,
                'stand_id' => $this->standId($plazaNorte, 'Tienda A-10'),
            ],
            [
                'email' => 'plazanorte.a5@amimportaciones.com',
                'name' => 'Vendedor Stand A-5',
                'role' => UserRole::Vendedor->value,
                'location_id' => $plazaNorte->id,
                'stand_id' => $this->standId($plazaNorte, 'Tienda A-5'),
            ],
            [
                'email' => 'plazanorte.mi7@amimportaciones.com',
                'name' => 'Vendedor Stand Mi-7',
                'role' => UserRole::Vendedor->value,
                'location_id' => $plazaNorte->id,
                'stand_id' => $this->standId($plazaNorte, 'Tienda Mi-7'),
            ],
            [
                'email' => 'plazanorte.l11@amimportaciones.com',
                'name' => 'Vendedor Stand L-11',
                'role' => UserRole::Vendedor->value,
                'location_id' => $plazaNorte->id,
                'stand_id' => $this->standId($plazaNorte, 'Tienda L-11'),
            ],
            [
                'email' => 'plazanorte.l13@amimportaciones.com',
                'name' => 'Vendedor Stand L-13',
                'role' => UserRole::Vendedor->value,
                'location_id' => $plazaNorte->id,
                'stand_id' => $this->standId($plazaNorte, 'Tienda L-13'),
            ],
            [
                'email' => 'plazanorte.j14@amimportaciones.com',
                'name' => 'Vendedor Stand J-14',
                'role' => UserRole::Vendedor->value,
                'location_id' => $plazaNorte->id,
                'stand_id' => $this->standId($plazaNorte, 'Tienda J-14'),
            ],

            // Sede La Muela (Supervisor en D-18 + 4 stands)
            [
                'email' => 'supervisor.lamuela@amimportaciones.com',
                'name' => 'Supervisor La Muela',
                'role' => UserRole::Encargado->value,
                'location_id' => $laMuela->id,
                'stand_id' => $this->standId($laMuela, 'Tienda D-18'),
            ],
            [
                'email' => 'lamuela.a1@amimportaciones.com',
                'name' => 'Vendedor Stand A-1',
                'role' => UserRole::Vendedor->value,
                'location_id' => $laMuela->id,
                'stand_id' => $this->standId($laMuela, 'Tienda A-1'),
            ],
            [
                'email' => 'lamuela.b1@amimportaciones.com',
                'name' => 'Vendedor Stand B-1',
                'role' => UserRole::Vendedor->value,
                'location_id' => $laMuela->id,
                'stand_id' => $this->standId($laMuela, 'Tienda B-1'),
            ],
            [
                'email' => 'lamuela.b2@amimportaciones.com',
                'name' => 'Vendedor Stand B-2',
                'role' => UserRole::Vendedor->value,
                'location_id' => $laMuela->id,
                'stand_id' => $this->standId($laMuela, 'Tienda B-2'),
            ],
            [
                'email' => 'lamuela.c2@amimportaciones.com',
                'name' => 'Vendedor Stand C-2',
                'role' => UserRole::Vendedor->value,
                'location_id' => $laMuela->id,
                'stand_id' => $this->standId($laMuela, 'Tienda C-2'),
            ],

            // Sede Av 54 (única tienda: su propio supervisor)
            [
                'email' => 'supervisor.av54@amimportaciones.com',
                'name' => 'Supervisor Av 54',
                'role' => UserRole::Encargado->value,
                'location_id' => $av54->id,
                'stand_id' => $this->standId($av54, 'Tienda Av 54'),
            ],
        ];

        DB::transaction(function () use ($accounts, $defaultPasswordHash): void {
            $usersByStandId = [];

            foreach ($accounts as $account) {
                $user = User::withTrashed()->firstWhere('email', $account['email']);

                if ($user) {
                    if ($user->trashed()) {
                        $user->restore();
                    }
                    $user->update([
                        'name' => $account['name'],
                        'role' => $account['role'],
                        'location_id' => $account['location_id'],
                        'stand_id' => $account['stand_id'],
                    ]);
                } else {
                    $user = User::create([
                        'name' => $account['name'],
                        'email' => $account['email'],
                        'password' => $defaultPasswordHash,
                        'role' => $account['role'],
                        'location_id' => $account['location_id'],
                        'stand_id' => $account['stand_id'],
                    ]);
                }

                if ($account['stand_id']) {
                    $usersByStandId[(int) $account['stand_id']] = $user;
                }
            }

            $this->migrateLegacyGenericAccounts($usersByStandId);
        });
    }

    private function standId(Location $location, string $name): int
    {
        return Stand::where('location_id', $location->id)
            ->where('name', $name)
            ->firstOrFail()
            ->id;
    }

    /**
     * Reasigna turnos, ventas y transferencias de las cuentas genéricas antiguas hacia las cuentas fijas por stand.
     *
     * @param  array<int, User>  $usersByStandId
     */
    private function migrateLegacyGenericAccounts(array $usersByStandId): void
    {
        $legacyMap = [
            'vendedor.plazanorte@amimportaciones.com' => 'plazanorte.a5@amimportaciones.com',
            'vendedor.lamuela@amimportaciones.com' => 'lamuela.a1@amimportaciones.com',
            'vendedor.av54@amimportaciones.com' => 'supervisor.av54@amimportaciones.com',
        ];

        foreach ($legacyMap as $legacyEmail => $fallbackEmail) {
            $legacyUser = User::withTrashed()->where('email', $legacyEmail)->first();
            if (! $legacyUser) {
                continue;
            }

            $fallbackUser = User::where('email', $fallbackEmail)->firstOrFail();

            $sessions = CashSession::where('user_id', $legacyUser->id)->get();
            foreach ($sessions as $session) {
                $targetUser = $usersByStandId[(int) $session->stand_id] ?? $fallbackUser;
                $session->update(['user_id' => $targetUser->id]);
                Sale::withTrashed()
                    ->where('cash_session_id', $session->id)
                    ->where('user_id', $legacyUser->id)
                    ->update(['user_id' => $targetUser->id]);
            }

            Sale::withTrashed()
                ->where('user_id', $legacyUser->id)
                ->update(['user_id' => $fallbackUser->id]);

            Transfer::where('requested_by', $legacyUser->id)
                ->update(['requested_by' => $fallbackUser->id]);

            Transfer::where('dispatched_by', $legacyUser->id)
                ->update(['dispatched_by' => $fallbackUser->id]);

            CashSession::where('approved_by', $legacyUser->id)
                ->update(['approved_by' => $fallbackUser->id]);

            $legacyUser->forceDelete();
        }
    }
}
