<?php

namespace App\Http\Controllers;

use App\Actions\Transfers\CancelTransferAction;
use App\Actions\Transfers\CreateTransferAction;
use App\Actions\Transfers\DispatchTransferAction;
use App\Actions\Transfers\ReceiveTransferAction;
use App\Enums\TransferStatus;
use App\Http\Requests\Transfer\StoreTransferRequest;
use App\Models\Transfer;
use App\Services\TransferService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function index(TransferService $transferService): View
    {
        return view('transfers.index', $transferService->getTransfersIndexData(auth()->user()));
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
