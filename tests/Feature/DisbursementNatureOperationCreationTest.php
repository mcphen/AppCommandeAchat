<?php

use App\Models\Boutique;
use App\Models\NatureOperation;
use App\Models\Role;
use App\Models\User;

test('demandeur can create a nature operation from the disbursement form', function () {
    $role = Role::create(['name' => 'Demandeur', 'slug' => 'demandeur']);
    $boutique = Boutique::create([
        'code' => 'BT001',
        'name' => 'Boutique Test',
        'city' => 'Abidjan',
        'is_active' => true,
    ]);
    /** @var User $user */
    $user = User::factory()->createOne([
        'role_id' => $role->id,
        'boutique_id' => $boutique->id,
    ]);

    $response = $this->actingAs($user)->postJson(route('disbursement-requests.nature-operations.store'), [
        'name' => 'Frais de mission',
        'description' => 'Mission ponctuelle',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('natureOperation.name', 'Frais de mission')
        ->assertJsonPath('natureOperation.slug', 'frais-de-mission');

    $this->assertDatabaseHas('nature_operations', [
        'name' => 'Frais de mission',
        'slug' => 'frais-de-mission',
    ]);
});

test('slug prevents duplicate nature operations on inline creation', function () {
    $role = Role::create(['name' => 'Demandeur', 'slug' => 'demandeur']);
    $boutique = Boutique::create([
        'code' => 'BT001',
        'name' => 'Boutique Test',
        'city' => 'Abidjan',
        'is_active' => true,
    ]);
    /** @var User $user */
    $user = User::factory()->createOne([
        'role_id' => $role->id,
        'boutique_id' => $boutique->id,
    ]);

    NatureOperation::create([
        'name' => 'Frais de mission',
        'description' => 'Initiale',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->postJson(route('disbursement-requests.nature-operations.store'), [
        'name' => 'frais   de mission',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);

    expect(NatureOperation::all())->toHaveCount(1);
});