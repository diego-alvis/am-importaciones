<?php

use App\Models\CashSession;
use App\Models\Location;
use App\Models\Stand;
use App\Models\User;

beforeEach(function () {
    $this->sedeA = Location::create(['name' => 'Sede Plaza Norte', 'type' => 'sede']);
    $this->sedeB = Location::create(['name' => 'Sede La Muela', 'type' => 'sede']);

    $this->standA = Stand::create(['location_id' => $this->sedeA->id, 'name' => 'Stand A-1', 'base_cash' => 100]);
    $this->standB = Stand::create(['location_id' => $this->sedeB->id, 'name' => 'Stand B-1', 'base_cash' => 100]);

    $this->admin = User::factory()->create(['role' => 'admin', 'location_id' => $this->sedeA->id]);
    $this->encargadoA = User::factory()->create(['role' => 'encargado', 'location_id' => $this->sedeA->id]);
    $this->vendedorA = User::factory()->create(['role' => 'vendedor', 'location_id' => $this->sedeA->id, 'stand_id' => $this->standA->id]);
    $this->vendedorB = User::factory()->create(['role' => 'vendedor', 'location_id' => $this->sedeB->id, 'stand_id' => $this->standB->id]);

    $this->sessionA = CashSession::create([
        'user_id' => $this->vendedorA->id,
        'stand_id' => $this->standA->id,
        'operator_name' => 'Cajero A',
        'expected_cash' => 100,
        'declared_cash' => 100,
        'status' => 'precierre',
    ]);

    $this->sessionB = CashSession::create([
        'user_id' => $this->vendedorB->id,
        'stand_id' => $this->standB->id,
        'operator_name' => 'Cajero B',
        'expected_cash' => 100,
        'declared_cash' => 100,
        'status' => 'precierre',
    ]);
});

test('vendedor cannot access categories', function () {
    $this->actingAs($this->vendedorA)
        ->get('/categorias')
        ->assertForbidden();
});

test('vendedor cannot access supervision index', function () {
    $this->actingAs($this->vendedorA)
        ->get('/supervision/cajas')
        ->assertForbidden();
});

test('vendedor can access their own cash report without 403', function () {
    $this->actingAs($this->vendedorA)
        ->get('/supervision/cajas/'.$this->sessionA->id.'/reporte')
        ->assertOk();
});

test('vendedor cannot access another user cash report', function () {
    $this->actingAs($this->vendedorA)
        ->get('/supervision/cajas/'.$this->sessionB->id.'/reporte')
        ->assertForbidden();
});

test('encargado can access reports of their own sede but not another sede', function () {
    $this->actingAs($this->encargadoA)
        ->get('/supervision/cajas/'.$this->sessionA->id.'/reporte')
        ->assertOk();

    $this->actingAs($this->encargadoA)
        ->get('/supervision/cajas/'.$this->sessionB->id.'/reporte')
        ->assertForbidden();
});

test('admin can access categories and any cash report', function () {
    $this->actingAs($this->admin)
        ->get('/categorias')
        ->assertOk();

    $this->actingAs($this->admin)
        ->get('/supervision/cajas/'.$this->sessionB->id.'/reporte')
        ->assertOk();
});
