<?php

namespace App\Actions\Sales;

use App\Enums\CashSessionStatus;
use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CancelSaleAction
{
    /**
     * Anula una venta, valida permisos y revierte el stock a la sede correspondiente.
     */
    public function execute(User $user, int|string $saleId): Sale
    {
        if (! $user->can('cancel', Sale::class) && ! $user->isAdmin() && ! $user->isEncargado()) {
            throw new DomainException('No tienes autorización para anular comprobantes de venta.');
        }

        $sale = Sale::with(['cashSession.stand', 'items'])->findOrFail($saleId);

        if ($sale->status === SaleStatus::Anulada) {
            throw new DomainException('Esta venta ya se encuentra anulada.');
        }

        if (! $user->can('cancel', $sale)) {
            throw new DomainException('Solo puedes anular ventas correspondientes a tu sede asignada.');
        }

        if ($sale->cashSession && $sale->cashSession->status !== CashSessionStatus::Abierta) {
            throw new DomainException('No se puede anular la venta porque el turno de caja ya fue cerrado o aprobado para arqueo.');
        }

        return DB::transaction(function () use ($sale) {
            $locationId = $sale->cashSession?->stand?->location_id;

            if ($locationId) {
                foreach ($sale->items as $item) {
                    Stock::where('location_id', $locationId)
                        ->where('product_id', $item->product_id)
                        ->increment('quantity', $item->quantity);
                }
            }

            $sale->update(['status' => SaleStatus::Anulada]);

            return $sale;
        });
    }
}
