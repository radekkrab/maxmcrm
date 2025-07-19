<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\OrderController
 */
final class OrderControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $orders = Order::factory()->count(3)->create();

        $response = $this->get(route('orders.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\OrderController::class,
            'store',
            \App\Http\Requests\OrderControllerStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $customer = fake()->word();
        $status = fake()->randomElement(/** enum_attributes **/);
        $warehouse = Warehouse::factory()->create();

        $response = $this->post(route('orders.store'), [
            'customer' => $customer,
            'status' => $status,
            'warehouse_id' => $warehouse->id,
        ]);

        $orders = Order::query()
            ->where('customer', $customer)
            ->where('status', $status)
            ->where('warehouse_id', $warehouse->id)
            ->get();
        $this->assertCount(1, $orders);
        $order = $orders->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $order = Order::factory()->create();

        $response = $this->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\OrderController::class,
            'update',
            \App\Http\Requests\OrderControllerUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $order = Order::factory()->create();
        $customer = fake()->word();
        $status = fake()->randomElement(/** enum_attributes **/);
        $warehouse = Warehouse::factory()->create();

        $response = $this->put(route('orders.update', $order), [
            'customer' => $customer,
            'status' => $status,
            'warehouse_id' => $warehouse->id,
        ]);

        $order->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($customer, $order->customer);
        $this->assertEquals($status, $order->status);
        $this->assertEquals($warehouse->id, $order->warehouse_id);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $order = Order::factory()->create();

        $response = $this->delete(route('orders.destroy', $order));

        $response->assertNoContent();

        $this->assertModelMissing($order);
    }
}
