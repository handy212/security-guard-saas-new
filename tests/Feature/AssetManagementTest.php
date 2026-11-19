<?php

namespace Tests\Feature;

use App\Livewire\Assets\AssetIndex;
use App\Livewire\Assets\CategoryIndex;
use App\Livewire\Assets\InventoryIndex;
use App\Livewire\Assets\PurchaseOrderIndex;
use App\Livewire\Assets\VendorIndex;
use App\Models\AssetCategory;
use App\Models\AssetPurchaseOrder;
use App\Models\AssetPurchaseOrderItem;
use App\Models\AssetVendor;
use App\Models\EquipmentAsset;
use App\Models\EquipmentAssignment;
use App\Models\Guard;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AssetManagementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        setPermissionsTeamId($this->admin->tenant_id);
    }

    public function test_assets_overview_loads(): void
    {
        $this->actingAs($this->admin)
            ->get(route('assets.overview'))
            ->assertOk()
            ->assertSee('Availability at a glance')
            ->assertSee('Workspace summary')
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
        $service->markOrdered($po);
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

    public function test_asset_form_rejects_related_records_from_another_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Reference Tenant',
            'slug' => 'other-reference-tenant',
            'status' => 'active',
        ]);
        $otherCategory = AssetCategory::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Radios',
            'type' => 'serialized',
            'is_active' => true,
        ]);
        $otherVendor = AssetVendor::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Vendor',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);
        setPermissionsTeamId($this->admin->tenant_id);
        $component = new AssetIndex;
        $component->form['name'] = 'Cross-tenant radio';
        $component->form['asset_category_id'] = $otherCategory->id;
        $component->form['vendor_id'] = $otherVendor->id;

        try {
            $component->save();
            $this->fail('Cross-tenant asset references should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('form.asset_category_id', $exception->errors());
            $this->assertArrayHasKey('form.vendor_id', $exception->errors());
        }

        $this->assertDatabaseMissing('equipment_assets', ['name' => 'Cross-tenant radio']);
    }

    public function test_purchase_order_form_rejects_vendor_and_category_from_another_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Procurement Tenant',
            'slug' => 'other-procurement-tenant',
            'status' => 'active',
        ]);
        $otherCategory = AssetCategory::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Uniforms',
            'type' => 'consumable',
            'is_active' => true,
        ]);
        $otherVendor = AssetVendor::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Supplier',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);
        $component = new PurchaseOrderIndex;
        $component->form['vendor_id'] = $otherVendor->id;
        $component->items[0]['asset_category_id'] = $otherCategory->id;
        $component->items[0]['description'] = 'Cross-tenant uniforms';

        try {
            $component->save(app(AssetManagementService::class));
            $this->fail('Cross-tenant purchase-order references should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('form.vendor_id', $exception->errors());
            $this->assertArrayHasKey('items.0.asset_category_id', $exception->errors());
        }

        $this->assertDatabaseMissing('asset_purchase_order_items', ['description' => 'Cross-tenant uniforms']);
    }

    public function test_referenced_assets_categories_and_vendors_cannot_be_deleted(): void
    {
        $assignment = EquipmentAssignment::query()
            ->where('tenant_id', $this->admin->tenant_id)
            ->where('status', 'issued')
            ->firstOrFail();
        $asset = $assignment->asset;
        $category = $asset->assetCategory;
        $vendor = $asset->vendor;

        Livewire::actingAs($this->admin)
            ->test(AssetIndex::class)
            ->call('delete', $asset->id)
            ->assertSee('This asset has assignment history and cannot be deleted. Set it to retired instead.');

        Livewire::actingAs($this->admin)
            ->test(CategoryIndex::class)
            ->call('delete', $category->id)
            ->assertSee('Reassign or remove its assets and purchase-order lines before deletion.');

        Livewire::actingAs($this->admin)
            ->test(VendorIndex::class)
            ->call('delete', $vendor->id)
            ->assertSee('Reassign its assets and remove its purchase orders before deletion.');

        $this->assertDatabaseHas('equipment_assets', ['id' => $asset->id]);
        $this->assertDatabaseHas('asset_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('asset_vendors', ['id' => $vendor->id]);
    }

    public function test_consumable_issue_and_return_update_unit_quantities_and_inventory(): void
    {
        $category = AssetCategory::create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Issueable Uniforms',
            'type' => 'consumable',
            'min_stock_level' => 2,
            'is_active' => true,
        ]);
        $asset = EquipmentAsset::create([
            'tenant_id' => $this->admin->tenant_id,
            'asset_category_id' => $category->id,
            'name' => 'Duty shirts',
            'category' => $category->name,
            'quantity_on_hand' => 10,
            'condition' => 'good',
            'status' => 'available',
        ]);

        $assignment = app(AssetManagementService::class)->issue($asset, [
            'guard_id' => Guard::where('tenant_id', $this->admin->tenant_id)->value('id'),
            'site_id' => Site::where('tenant_id', $this->admin->tenant_id)->value('id'),
            'quantity' => 3,
        ]);

        $this->assertSame(3, $assignment->quantity);
        $this->assertSame(7, $asset->fresh()->quantity_on_hand);
        Livewire::actingAs($this->admin)
            ->test(InventoryIndex::class)
            ->assertSeeInOrder(['Issueable Uniforms', '10', '7', '3']);

        app(AssetManagementService::class)->returnAsset($assignment);

        $this->assertSame(10, $asset->fresh()->quantity_on_hand);
        $this->assertSame('available', $asset->fresh()->status->value);
    }

    public function test_a_fully_issued_consumable_can_be_edited_at_zero_available_units(): void
    {
        $category = AssetCategory::create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Fully issued consumables',
            'type' => 'consumable',
            'is_active' => true,
        ]);
        $asset = EquipmentAsset::create([
            'tenant_id' => $this->admin->tenant_id,
            'asset_category_id' => $category->id,
            'name' => 'All issued shirts',
            'category' => $category->name,
            'quantity_on_hand' => 0,
            'condition' => 'good',
            'status' => 'issued',
        ]);

        Livewire::actingAs($this->admin)
            ->test(AssetIndex::class)
            ->call('edit', $asset->id)
            ->set('form.location', 'Uniform store')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('equipment_assets', [
            'id' => $asset->id,
            'quantity_on_hand' => 0,
            'location' => 'Uniform store',
        ]);
    }

    public function test_purchase_orders_only_advance_through_valid_states(): void
    {
        $vendor = AssetVendor::where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        $po = AssetPurchaseOrder::create([
            'tenant_id' => $this->admin->tenant_id,
            'vendor_id' => $vendor->id,
            'po_number' => 'PO-TEST-STATES',
            'status' => 'draft',
        ]);
        $item = AssetPurchaseOrderItem::create([
            'tenant_id' => $this->admin->tenant_id,
            'purchase_order_id' => $po->id,
            'description' => 'Test radio',
            'quantity' => 1,
            'quantity_received' => 0,
            'unit_cost' => 100,
            'line_total' => 100,
        ]);
        $service = app(AssetManagementService::class);

        $this->expectException(\RuntimeException::class);
        $service->receiveItems($po, $item->id, 1);
    }

    public function test_purchase_order_drawer_can_close_and_partial_lines_can_be_received(): void
    {
        $vendor = AssetVendor::where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        $po = AssetPurchaseOrder::create([
            'tenant_id' => $this->admin->tenant_id,
            'vendor_id' => $vendor->id,
            'po_number' => 'PO-TEST-PARTIAL',
            'status' => 'ordered',
        ]);
        $item = AssetPurchaseOrderItem::create([
            'tenant_id' => $this->admin->tenant_id,
            'purchase_order_id' => $po->id,
            'description' => 'Partial radio delivery',
            'quantity' => 4,
            'quantity_received' => 0,
            'unit_cost' => 100,
            'line_total' => 400,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PurchaseOrderIndex::class)
            ->call('openForm')
            ->assertSet('showForm', true)
            ->assertSeeHtml('for="field-form-notes"')
            ->assertSeeHtml('id="field-form-notes"')
            ->call('closeDrawer')
            ->assertSet('showForm', false);

        Livewire::actingAs($this->admin)
            ->test(PurchaseOrderIndex::class)
            ->call('selectPo', $po->id)
            ->set('receiveQuantities.'.$item->id, 2)
            ->call('receiveLine', $po->id, $item->id)
            ->assertSee('Items received into inventory.');

        $this->assertDatabaseHas('asset_purchase_order_items', [
            'id' => $item->id,
            'quantity_received' => 2,
        ]);
        $this->assertSame('partial', $po->fresh()->status->value);
    }

    public function test_purchase_order_form_can_remove_extra_line_items(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PurchaseOrderIndex::class)
            ->call('openForm')
            ->call('addLine')
            ->assertCount('items', 2)
            ->assertSeeHtml('aria-label="Remove line 2"')
            ->call('removeLine', 1)
            ->assertCount('items', 1);
    }

    public function test_warranty_alerts_and_filter_include_expired_and_upcoming_dates(): void
    {
        $expired = EquipmentAsset::create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Expired warranty radio',
            'condition' => 'good',
            'status' => 'available',
            'quantity_on_hand' => 1,
            'warranty_expires_at' => today()->subDay(),
        ]);
        $upcoming = EquipmentAsset::create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'Upcoming warranty radio',
            'condition' => 'good',
            'status' => 'available',
            'quantity_on_hand' => 1,
            'warranty_expires_at' => today()->addDays(30),
        ]);

        $this->actingAs($this->admin)
            ->get(route('assets.overview'))
            ->assertOk()
            ->assertSee($upcoming->name)
            ->assertSee($expired->name);

        $this->actingAs($this->admin)
            ->get(route('assets.index', ['warranty' => 'expiring']))
            ->assertOk()
            ->assertSee($upcoming->name)
            ->assertSee($expired->name);
    }

    public function test_purchase_order_rows_have_a_keyboard_action(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PurchaseOrderIndex::class)
            ->assertSeeHtml('aria-label="Open purchase order PO-2026-004"');
    }

    public function test_asset_filters_have_accessible_names(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AssetIndex::class)
            ->assertSeeHtml('aria-label="Filter by category"')
            ->assertSeeHtml('aria-label="Filter by warranty"');
    }

    public function test_return_drawer_shows_assignment_details_and_confirms_the_existing_return_action(): void
    {
        $assignment = EquipmentAssignment::query()
            ->where('tenant_id', $this->admin->tenant_id)
            ->where('status', 'issued')
            ->firstOrFail()
            ->load(['asset', 'assignedGuard', 'site']);

        Livewire::actingAs($this->admin)
            ->test(AssetIndex::class)
            ->call('openReturn', $assignment->id)
            ->assertSet('showReturnForm', true)
            ->assertSee('Assignment details')
            ->assertSee($assignment->asset->name)
            ->assertSee($assignment->assignedGuard->full_name)
            ->set('returnNotes', 'Radio checked and returned.')
            ->call('returnAssignment')
            ->assertSee('Asset returned.');

        $this->assertDatabaseHas('equipment_assignments', [
            'id' => $assignment->id,
            'status' => 'returned',
            'return_notes' => 'Radio checked and returned.',
        ]);
    }

    public function test_asset_list_does_not_expose_an_assignment_scoped_to_another_tenant(): void
    {
        $assignment = EquipmentAssignment::query()
            ->where('tenant_id', $this->admin->tenant_id)
            ->where('status', 'issued')
            ->firstOrFail();
        $otherTenant = Tenant::create([
            'name' => 'Other Asset Tenant',
            'slug' => 'other-asset-tenant',
            'status' => 'active',
        ]);
        DB::table('equipment_assignments')
            ->where('id', $assignment->id)
            ->update(['tenant_id' => $otherTenant->id]);

        Livewire::actingAs($this->admin)
            ->test(AssetIndex::class)
            ->assertDontSee('Return');
    }

    public function test_return_action_cannot_open_an_assignment_from_another_tenant(): void
    {
        $assignment = EquipmentAssignment::query()
            ->where('tenant_id', $this->admin->tenant_id)
            ->where('status', 'issued')
            ->firstOrFail();
        $otherTenant = Tenant::create([
            'name' => 'Other Return Tenant',
            'slug' => 'other-return-tenant',
            'status' => 'active',
        ]);
        DB::table('equipment_assignments')
            ->where('id', $assignment->id)
            ->update(['tenant_id' => $otherTenant->id]);

        $this->actingAs($this->admin);
        setPermissionsTeamId($this->admin->tenant_id);
        $this->expectException(ModelNotFoundException::class);
        (new AssetIndex)->openReturn($assignment->id);
    }
}
