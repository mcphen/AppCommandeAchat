<?php

use App\Models\Article;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseOrderReception;
use App\Models\PurchaseOrderReceptionLine;
use App\Models\ReceptionTransfer;
use App\Models\Role;
use App\Models\User;

function transferScenario(): array
{
    $role = Role::create(['name' => 'Administrateur', 'slug' => 'admin']);
    $user = User::factory()->create(['role_id' => $role->id]);
    $article = Article::create(['name' => 'Article test', 'reference' => 'ART-TEST', 'unit' => 'pièce', 'is_active' => true]);
    $order = PurchaseOrder::create(['user_id' => $user->id, 'title' => 'BC test', 'description' => 'Test', 'amount' => 1000, 'status' => 'approved']);
    $orderLine = PurchaseOrderLine::create(['purchase_order_id' => $order->id, 'article_id' => $article->id, 'quantity' => 10, 'unit_price' => 100]);
    $reception = PurchaseOrderReception::create(['purchase_order_id' => $order->id, 'received_by' => $user->id, 'received_at' => '2026-08-27', 'type' => 'complete']);
    $line = PurchaseOrderReceptionLine::create(['reception_id' => $reception->id, 'purchase_order_line_id' => $orderLine->id, 'quantity_received' => 10]);
    $projects = [
        Project::create(['code' => 'CH-01', 'name' => 'Chantier 1', 'is_active' => true]),
        Project::create(['code' => 'CH-02', 'name' => 'Chantier 2', 'is_active' => true]),
    ];
    return compact('user', 'reception', 'line', 'projects');
}

it('crée atomiquement plusieurs bons numérotés pour une même réception', function () {
    extract(transferScenario());
    $response = $this->actingAs($user)->post(route('transfers.batch.store'), [
        'reception_id' => $reception->id,
        'transfers' => [
            ['project_id' => $projects[0]->id, 'project_responsible_id' => $user->id, 'transferred_at' => '2026-08-27', 'lines' => [['reception_line_id' => $line->id, 'quantity_transferred' => 4]]],
            ['project_id' => $projects[1]->id, 'project_responsible_id' => $user->id, 'transferred_at' => '2026-08-27', 'lines' => [['reception_line_id' => $line->id, 'quantity_transferred' => 6]]],
        ],
    ]);
    $response->assertSessionHasNoErrors();
    expect(ReceptionTransfer::count())->toBe(2)
        ->and(ReceptionTransfer::pluck('transfer_number')->all())->each->toMatch('/^BT-2026-\d{6}$/');
    $this->assertDatabaseCount('reception_transfer_lines', 2);
});

it('refuse tout le lot lorsque le cumul dépasse la quantité disponible', function () {
    extract(transferScenario());
    $response = $this->actingAs($user)->from(route('transfers.create'))->post(route('transfers.batch.store'), [
        'reception_id' => $reception->id,
        'transfers' => [
            ['project_id' => $projects[0]->id, 'project_responsible_id' => $user->id, 'transferred_at' => '2026-08-27', 'lines' => [['reception_line_id' => $line->id, 'quantity_transferred' => 6]]],
            ['project_id' => $projects[1]->id, 'project_responsible_id' => $user->id, 'transferred_at' => '2026-08-27', 'lines' => [['reception_line_id' => $line->id, 'quantity_transferred' => 5]]],
        ],
    ]);
    $response->assertRedirect(route('transfers.create'))->assertSessionHasErrors('transfers');
    $this->assertDatabaseCount('reception_transfers', 0);
    $this->assertDatabaseCount('reception_transfer_lines', 0);
});