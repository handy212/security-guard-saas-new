<?php

namespace Tests\Feature;

use App\Livewire\Assets\AssetIndex;
use App\Livewire\Assets\CategoryIndex;
use App\Livewire\Assets\Overview;
use App\Livewire\Assets\PurchaseOrderIndex;
use App\Livewire\Assets\VendorIndex;
use App\Models\AssetCategory;
use App\Models\AssetVendor;
use App\Models\EquipmentAsset;
use App\Models\User;
use App\Services\AssetManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->first();
        app()->instance('currentTenant', $this->admin->tenant);
    }

    public function test_assets_overview_loads(): void
    {
        $this->actingAs($this->admin)
            ->get(route('assets.overview'))
            ->assertOk()
            ->assertSee('Total assets');
    }

    public function test_category_and_vendor_crud(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CategoryIndex::class)
            ->call('openCreate')
            ->set('form.name', 'Radios')
            ->set('form.type', 'serialized')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asset_categories', ['name' => 'Radios']);

        Livewire::actingAs($this->admin)
            ->test(VendorIndex::class)
            ->call('openCreate')
            ->set('form.name', 'Secure Supply Co')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asset_vendors', ['name' => 'Secure Supply Co']);
    }

    public function test_purchase_order_creates_assets_on_receive(): void
    {
        $tenantId = $this->admin->tenant_id;
        $category = AssetCategory::create([
            'tenant_id' => $tenantId,
            'name' => 'Radios',
            'type' => 'serialized',
            'is_active' => true,
        ]);
        $vendor = AssetVendor::create([
            'tenant_id' => $tenantId,
            'name' => 'Radio Depot',
            'status' => 'active',
        ]);

        $service = app(AssetManagementService::class);
        $po = $service->createPurchaseOrder(
            ['tenant_id' => $tenantId, 'vendor_id' => $vendor->id],
            [['asset_category_id' => $category->id, 'description' => 'Motorola radio', 'quantity' => 2, 'unit_cost' => 350]],
            $this->admin->id,
        );

        $item = $po->items->first();
        $before = EquipmentAsset::count();
        $service->receiveItems($po, $item->id, 2);

        $this->assertSame($before + 2, EquipmentAsset::count());
        $this->assertSame('received', $po->fresh()->status->value);
    }

    public function test_asset_index_create(): void
    {
        $category = AssetCategory::create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Uniforms',
            'type' => 'consumable',
            'min_stock_level' => 5,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(AssetIndex::class)
            ->call('openCreate')
            ->set('form.asset_category_id', (string) $category->id)
            ->set('form.name', 'Duty shirt')
            ->set('form.quantity_on_hand', 10)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('equipment_assets', ['name' => 'Duty shirt', 'quantity_on_hand' => 10]);
    }
}
