<?php

namespace App\Http\Controllers;

use App\Actions\StoreSession\SwitchStandAction;
use App\Http\Requests\StoreSession\SetStandRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;

class StoreSessionController extends Controller
{
    public function setStand(SetStandRequest $request, SwitchStandAction $switchStandAction): RedirectResponse
    {
        try {
            $user = auth()->user();
            $stand = $switchStandAction->execute(
                $user,
                $request->validated('stand_id'),
                $request->input('operator_name')
            );

            if ($user->hasFixedStand()) {
                return back()->with('success', "Operador de turno actualizado: {$user->display_name} ({$stand->name}).");
            }

            return back()->with('success', "Ahora estás operando en: {$stand->name} ({$stand->location->name}).");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
