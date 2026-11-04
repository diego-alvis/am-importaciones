<?php

namespace App\Services;

use App\Enums\CashSessionStatus;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;

class PosService
{
    /**
     * Obtiene el catálogo, categorías, sedes y sesión de caja activa para el terminal POS.
     *
     * @return array<string, mixed>
     */
    public function getPosData(User $user): array
    {
        $activeStand = $user->getActiveStand();
        $availableStands = $user->getAvailableStands();
        $categories = Category::orderBy('name')->get();

        $locationId = $activeStand?->location_id ?? $user->location_id;

        $products = Product::with(['category', 'stocks' => function ($query) use ($locationId) {
            if ($locationId) {
                $query->where('location_id', $locationId);
            }
        }])->orderBy('name')->get();

        $activeSession = CashSession::with('stand.location')
            ->where('user_id', $user->id)
            ->where('status', CashSessionStatus::Abierta)
            ->first();

        return compact('products', 'categories', 'activeStand', 'availableStands', 'activeSession');
    }
}
