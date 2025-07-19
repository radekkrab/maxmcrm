<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\WarehouseController
 */
final class WarehouseControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $warehouses = Warehouse::factory()->count(3)->create();

        $response = $this->get(route('warehouses.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\WarehouseController::class,
            'store',
            \App\Http\Requests\WarehouseControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $name = fake()->name();

        $response = $this->post(route('warehouses.store'), [
            'name' => $name,
        ]);

        $warehouses = Warehouse::query()
            ->where('name', $name)
            ->get();
        $this->assertCount(1, $warehouses);
        $warehouse = $warehouses->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->get(route('warehouses.show', $warehouse));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\WarehouseController::class,
            'update',
            \App\Http\Requests\WarehouseControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $warehouse = Warehouse::factory()->create();
        $name = fake()->name();

        $response = $this->put(route('warehouses.update', $warehouse), [
            'name' => $name,
        ]);

        $warehouse->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($name, $warehouse->name);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->delete(route('warehouses.destroy', $warehouse));

        $response->assertNoContent();

        $this->assertModelMissing($warehouse);
    }
}
