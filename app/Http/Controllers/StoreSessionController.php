<?php

namespace App\Http\Controllers;

use App\Models\Stand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreSessionController extends Controller
{
    public function setStand(Request $request): RedirectResponse
    {
        $request->validate([
            'stand_id' => 'required|exists:stands,id',
            'operator_name' => 'nullable|string|max:100',
        ]);

        $stand = Stand::with('location')->findOrFail($request->stand_id);
        $user = auth()->user();

        // Validar que el vendedor o supervisor solo elija tiendas de su sede asignada
        if ($user->role !== 'admin' && $user->location_id && (int) $stand->location_id !== (int) $user->location_id) {
            return back()->with('error', 'Solo puedes operar tiendas asignadas a tu sede (' . ($user->location->name ?? '') . ').');
        }

        session(['active_stand_id' => $stand->id]);

        $updateData = ['stand_id' => $stand->id];
        if (!empty($request->operator_name)) {
            $updateData['name'] = trim($request->operator_name);
        }

        $user->update($updateData);

        return back()->with('success', "Ahora estás operando en: {$stand->name} ({$stand->location->name}).");
    }
}
