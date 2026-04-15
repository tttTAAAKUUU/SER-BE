<?php

namespace Tests\Feature\Business;

use App\Models\User\User;
use App\Models\Business\Business;
use App\Models\Business\Store;
use App\Models\Business\Store\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreEmployeesTest extends TestCase
{
    use RefreshDatabase;

    private User $businessUser;
    private Store $store;

    private function actingAsBusiness(): void
    {
        $this->businessUser = User::factory()->create();
        $business = Business::factory()->for($this->businessUser, 'owner')->create();
        $this->store = Store::factory()->for($business)->create();
        Sanctum::actingAs($this->businessUser);
    }

    public function test_business_can_list_store_employees(): void
    {
        $this->actingAsBusiness();
        Employee::factory()->for($this->store)->count(2)->create();

        $response = $this->getJson("/api/businesses/stores/{$this->store->id}/employees/");

        $response->assertStatus(200);
    }

    public function test_business_can_create_store_employee(): void
    {
        $this->actingAsBusiness();

        $response = $this->postJson("/api/businesses/stores/{$this->store->id}/employees/", [
            'store_id' => $this->store->id,
            'first_name' => 'John',
            'last_name' => 'Employee',
            'phone' => '555-0000',
            'dob' => '1990-01-01',
            'gender' => 'male',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('employees', [
            'first_name' => 'John',
            'store_id' => $this->store->id,
        ]);
    }

    public function test_business_cannot_create_store_employee_with_missing_fields(): void
    {
        $this->actingAsBusiness();

        $response = $this->postJson("/api/businesses/stores/{$this->store->id}/employees/", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['store_id', 'first_name', 'last_name', 'phone', 'dob', 'gender']);
    }

    public function test_business_can_show_store_employee(): void
    {
        $this->actingAsBusiness();
        $employee = Employee::factory()->for($this->store)->create();

        $response = $this->getJson("/api/businesses/stores/{$this->store->id}/employees/{$employee->id}");

        $response->assertStatus(200);
    }

    public function test_business_can_update_store_employee(): void
    {
        $this->actingAsBusiness();
        $employee = Employee::factory()->for($this->store)->create(['first_name' => 'Old Name']);

        $response = $this->putJson("/api/businesses/stores/{$this->store->id}/employees/{$employee->id}", [
            'first_name' => 'New Name',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'first_name' => 'New Name']);
    }

    public function test_business_can_delete_store_employee(): void
    {
        $this->actingAsBusiness();
        $employee = Employee::factory()->for($this->store)->create();

        $response = $this->deleteJson("/api/businesses/stores/{$this->store->id}/employees/{$employee->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_unauthenticated_user_cannot_access_store_employees(): void
    {
        $response = $this->getJson('/api/businesses/stores/1/employees/');

        $response->assertStatus(401);
    }
}