<?php

namespace Tests\Unit;

use App\Models\AppField;
use App\Models\AppRecord;
use App\Models\AppTable;
use App\Services\RecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecordServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecordService $service;
    private AppTable $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecordService();
        $this->table   = AppTable::create(['name' => 'items', 'label' => 'Items']);
        $this->table->fields()->create(['name' => 'title', 'label' => 'Title', 'type' => 'string']);
    }

    public function test_create_stores_record(): void
    {
        $record = $this->service->create($this->table, ['title' => 'Hello']);
        $this->assertInstanceOf(AppRecord::class, $record);
        $this->assertEquals('Hello', $record->data['title']);
    }

    public function test_create_applies_default_value(): void
    {
        $this->table->fields()->create(['name' => 'status', 'label' => 'Status', 'type' => 'string', 'default_value' => 'active']);
        $record = $this->service->create($this->table, ['title' => 'Test']);
        $this->assertEquals('active', $record->data['status']);
    }

    public function test_create_throws_on_missing_mandatory_field(): void
    {
        $this->table->fields()->create(['name' => 'code', 'label' => 'Code', 'type' => 'string', 'mandatory' => true]);
        $this->expectException(ValidationException::class);
        $this->service->create($this->table, ['title' => 'No code']);
    }

    public function test_update_changes_data(): void
    {
        $record = AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'Old']]);
        $updated = $this->service->update($record, $this->table, ['title' => 'New']);
        $this->assertEquals('New', $updated->data['title']);
    }

    public function test_update_preserves_readonly_field(): void
    {
        $this->table->fields()->create(['name' => 'ref', 'label' => 'Ref', 'type' => 'string', 'readonly' => true]);
        $record = AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'T', 'ref' => 'ORIG']]);

        $updated = $this->service->update($record, $this->table, ['title' => 'T', 'ref' => 'TAMPERED']);
        $this->assertEquals('ORIG', $updated->data['ref']);
    }

    public function test_delete_removes_record(): void
    {
        $record = AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'Del']]);
        $this->service->delete($record);
        $this->assertDatabaseMissing('app_records', ['id' => $record->id]);
    }

    public function test_lookup_returns_label_from_first_field(): void
    {
        AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'Widget']]);
        $results = $this->service->lookup($this->table);
        $this->assertEquals('Widget', $results->first()['label']);
    }

    public function test_for_table_returns_all_records(): void
    {
        AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'A']]);
        AppRecord::create(['app_table_id' => $this->table->id, 'data' => ['title' => 'B']]);
        $this->assertCount(2, $this->service->forTable($this->table));
    }
}
