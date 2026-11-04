<?php

namespace App\Http\Controllers;

use App\Actions\Transfers\CancelTransferAction;
use App\Actions\Transfers\CreateTransferAction;
use App\Actions\Transfers\DispatchTransferAction;
use App\Actions\Transfers\ReceiveTransferAction;
use App\Enums\LocationType;
use App\Enums\TransferStatus;
use App\Http\Requests\Transfer\StoreTransferRequest;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Transfer;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $query = Transfer::with(['fromLocation', 'toLocation', 'items.product.category', 'requestedBy', 'dispatchedBy'])
            ->latest();

        if ($user->isEncargado() || $user->isVendedor()) {
            $query->where(function ($q) use ($user) {
                $q->where('from_location_id', $user->location_id)
                    ->orWhere('to_location_id', $user->location_id);
            });
        }

        $transfers = $query->paginate(15);
        $locations = Location::orderBy('name')->get();
        $almacen = Location::where('type', LocationType::Almacen)->first();
        $categories = Category::orderBy('name')->get();

        $rawProducts = Product::with(['stocks', 'category'])->orderBy('name')->get();

        $storeLocationId = $user->location_id;
        $almacenId = $almacen?->id;

        $catalogProducts = $rawProducts->map(function ($p) use ($almacenId, $storeLocationId) {
            $almacenStock = $p->stocks->firstWhere('location_id', $almacenId)?->quantity ?? 0;
            $storeStockObj = $storeLocationId ? $p->stocks->firstWhere('location_id', $storeLocationId) : null;
            $storeStock = $storeStockObj?->quantity ?? 0;
            $minStock = $storeStockObj?->min_stock ?? 5;

            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category_id' => $p->category_id,
                'category_name' => $p->category?->name ?? 'General',
                'almacen_stock' => $almacenStock,
                'store_stock' => $storeStock,
                'min_stock' => $minStock,
                'is_low_stock' => $storeStock <= $minStock,
            ];
        });

        $pendingCount = Transfer::where('status', TransferStatus::Solicitado)->count();

        return view('transfers.index', compact('transfers', 'locations', 'catalogProducts', 'categories', 'almacen', 'pendingCount'));
    }

    public function store(StoreTransferRequest $request, CreateTransferAction $createTransferAction): RedirectResponse
    {
        try {
            $transfer = $createTransferAction->execute(auth()->user(), $request->validated());

            $label = $transfer->status === TransferStatus::Solicitado
                ? 'Solicitud de pedido registrada correctamente. El Almacén Central preparará y despachará tu mercadería.'
                : 'Traslado registrado y despachado en estado EN TRÁNSITO.';

            return redirect()->route('transfers.index')->with(
                'success',
                '#TR-'.str_pad($transfer->id, 5, '0', STR_PAD_LEFT).": {$label}"
            );
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Error en la transferencia: '.$e->getMessage());
        }
    }

    public function dispatch(int|string $id, DispatchTransferAction $dispatchTransferAction): RedirectResponse
    {
        $transfer = Transfer::findOrFail($id);

        $this->authorize('dispatch', $transfer);

        try {
            $dispatchTransferAction->execute(auth()->user(), $transfer);

            return back()->with(
                'success',
                'Pedido #TR-'.str_pad($transfer->id, 5, '0', STR_PAD_LEFT).' alistado y despachado. Stock descontado de Almacén Central y en camino a destino.'
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function receive(int|string $id, ReceiveTransferAction $receiveTransferAction): RedirectResponse
    {
        $transfer = Transfer::findOrFail($id);

        $this->authorize('receive', $transfer);

        try {
            $receiveTransferAction->execute(auth()->user(), $transfer);

            return back()->with(
                'success',
                'Transferencia #TR-'.str_pad($transfer->id, 5, '0', STR_PAD_LEFT).' confirmada. Stock ingresado a '.$transfer->toLocation->name.'.'
            );
        } catch (DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }

    public function cancel(int|string $id, CancelTransferAction $cancelTransferAction): RedirectResponse
    {
        $transfer = Transfer::findOrFail($id);

        $this->authorize('cancel', $transfer);

        try {
            $cancelTransferAction->execute(auth()->user(), $transfer);

            return back()->with(
                'success',
                'Transferencia #TR-'.str_pad($transfer->id, 5, '0', STR_PAD_LEFT).' cancelada correctamente.'
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function pickingSheet(int|string $id): View
    {
        $transfer = Transfer::with(['fromLocation', 'toLocation', 'items.product.category', 'requestedBy', 'dispatchedBy'])->findOrFail($id);

        return view('transfers.picking', compact('transfer'));
    }
}
