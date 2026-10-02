<?php

namespace Database\Seeders;

use App\Models\AppField;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

/**
 * Creates two demo tables with fields and dummy records in the tenant DB.
 * Run: php artisan db:seed --class="Database\Seeders\TenantDemoSeeder" --force
 */
class TenantDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCustomers();
        $this->seedProducts();
        $this->command->info('Tenant demo tables seeded.');
    }

    // ── Table 1: Customers ────────────────────────────────────────────────────

    private function seedCustomers(): void
    {
        $table = AppTable::firstOrCreate(
            ['name' => 'customers'],
            [
                'label'              => 'Customers',
                'table_type'         => 'standard',
                'can_read'           => true,
                'can_create'         => true,
                'can_update'         => true,
                'can_delete'         => true,
                'whatsapp_broadcast' => true,
                'whatsapp_phone_field'=> 'phone',
            ]
        );

        $fields = [
            ['name' => 'name',       'label' => 'Full Name',    'type' => 'string',  'mandatory' => true,  'display' => true,  'order' => 0],
            ['name' => 'email',      'label' => 'Email',        'type' => 'string',  'mandatory' => false, 'display' => true,  'order' => 1],
            ['name' => 'phone',      'label' => 'Phone',        'type' => 'string',  'mandatory' => true,  'display' => true,  'order' => 2],
            ['name' => 'city',       'label' => 'City',         'type' => 'string',  'mandatory' => false, 'display' => true,  'order' => 3],
            ['name' => 'status',     'label' => 'Status',       'type' => 'choice',  'mandatory' => false, 'display' => true,  'order' => 4,
                'choices' => [
                    ['label' => 'Active',   'value' => 'active'],
                    ['label' => 'Inactive', 'value' => 'inactive'],
                    ['label' => 'Lead',     'value' => 'lead'],
                ]
            ],
            ['name' => 'notes',      'label' => 'Notes',        'type' => 'text',    'mandatory' => false, 'display' => false, 'order' => 5],
            ['name' => 'joined_date','label' => 'Joined Date',  'type' => 'date',    'mandatory' => false, 'display' => true,  'order' => 6],
        ];

        foreach ($fields as $f) {
            AppField::firstOrCreate(
                ['app_table_id' => $table->id, 'name' => $f['name']],
                [
                    'label'     => $f['label'],
                    'type'      => $f['type'],
                    'mandatory' => $f['mandatory'],
                    'display'   => $f['display'],
                    'active'    => true,
                    'order'     => $f['order'],
                    'choices'   => $f['choices'] ?? null,
                ]
            );
        }

        if (AppRecord::where('app_table_id', $table->id)->exists()) {
            $this->command->info('Customers records already exist — skipping.');
            return;
        }

        $records = [
            ['name' => 'Rajesh Kumar',   'email' => 'rajesh@gmail.com',  'phone' => '9876543210', 'city' => 'Mumbai',    'status' => 'active',   'joined_date' => '2024-01-15'],
            ['name' => 'Priya Sharma',   'email' => 'priya@gmail.com',   'phone' => '9876543211', 'city' => 'Delhi',     'status' => 'active',   'joined_date' => '2024-02-20'],
            ['name' => 'Amit Patel',     'email' => 'amit@gmail.com',    'phone' => '9876543212', 'city' => 'Ahmedabad', 'status' => 'lead',     'joined_date' => '2024-03-10'],
            ['name' => 'Sunita Reddy',   'email' => 'sunita@gmail.com',  'phone' => '9876543213', 'city' => 'Hyderabad', 'status' => 'active',   'joined_date' => '2024-04-05'],
            ['name' => 'Vikram Singh',   'email' => 'vikram@gmail.com',  'phone' => '9876543214', 'city' => 'Jaipur',    'status' => 'inactive', 'joined_date' => '2023-12-01'],
            ['name' => 'Meena Iyer',     'email' => 'meena@gmail.com',   'phone' => '9876543215', 'city' => 'Chennai',   'status' => 'active',   'joined_date' => '2024-05-18'],
            ['name' => 'Deepak Verma',   'email' => 'deepak@gmail.com',  'phone' => '9876543216', 'city' => 'Pune',      'status' => 'lead',     'joined_date' => '2024-06-22'],
            ['name' => 'Kavitha Nair',   'email' => 'kavitha@gmail.com', 'phone' => '9876543217', 'city' => 'Kochi',     'status' => 'active',   'joined_date' => '2024-07-30'],
        ];

        foreach ($records as $r) {
            AppRecord::create(['app_table_id' => $table->id, 'data' => $r]);
        }

        $this->command->info('Customers table seeded (' . count($records) . ' records).');
    }

    // ── Table 2: Products ─────────────────────────────────────────────────────

    private function seedProducts(): void
    {
        $table = AppTable::firstOrCreate(
            ['name' => 'products'],
            [
                'label'      => 'Products',
                'table_type' => 'standard',
                'can_read'   => true,
                'can_create' => true,
                'can_update' => true,
                'can_delete' => true,
            ]
        );

        $fields = [
            ['name' => 'product_name', 'label' => 'Product Name', 'type' => 'string',   'mandatory' => true,  'display' => true,  'order' => 0],
            ['name' => 'sku',          'label' => 'SKU',           'type' => 'string',   'mandatory' => false, 'display' => true,  'order' => 1],
            ['name' => 'category',     'label' => 'Category',      'type' => 'choice',   'mandatory' => false, 'display' => true,  'order' => 2,
                'choices' => [
                    ['label' => 'Electronics', 'value' => 'electronics'],
                    ['label' => 'Clothing',    'value' => 'clothing'],
                    ['label' => 'Food',        'value' => 'food'],
                    ['label' => 'Books',       'value' => 'books'],
                    ['label' => 'Other',       'value' => 'other'],
                ]
            ],
            ['name' => 'price',        'label' => 'Price (₹)',     'type' => 'currency', 'mandatory' => true,  'display' => true,  'order' => 3],
            ['name' => 'stock',        'label' => 'Stock Qty',     'type' => 'integer',  'mandatory' => false, 'display' => true,  'order' => 4],
            ['name' => 'description',  'label' => 'Description',   'type' => 'text',     'mandatory' => false, 'display' => false, 'order' => 5],
            ['name' => 'is_active',    'label' => 'Active',        'type' => 'boolean',  'mandatory' => false, 'display' => true,  'order' => 6, 'default_value' => 'true'],
        ];

        foreach ($fields as $f) {
            AppField::firstOrCreate(
                ['app_table_id' => $table->id, 'name' => $f['name']],
                [
                    'label'         => $f['label'],
                    'type'          => $f['type'],
                    'mandatory'     => $f['mandatory'],
                    'display'       => $f['display'],
                    'active'        => true,
                    'order'         => $f['order'],
                    'choices'       => $f['choices'] ?? null,
                    'default_value' => $f['default_value'] ?? null,
                ]
            );
        }

        if (AppRecord::where('app_table_id', $table->id)->exists()) {
            $this->command->info('Products records already exist — skipping.');
            return;
        }

        $records = [
            ['product_name' => 'Wireless Headphones', 'sku' => 'ELEC-001', 'category' => 'electronics', 'price' => 2499,  'stock' => 50,  'is_active' => true,  'description' => 'Bluetooth 5.0 wireless headphones with noise cancellation.'],
            ['product_name' => 'Cotton T-Shirt',       'sku' => 'CLO-001',  'category' => 'clothing',    'price' => 499,   'stock' => 200, 'is_active' => true,  'description' => 'Premium cotton round-neck t-shirt, available in all sizes.'],
            ['product_name' => 'Python Programming',   'sku' => 'BOOK-001', 'category' => 'books',       'price' => 799,   'stock' => 30,  'is_active' => true,  'description' => 'Complete guide to Python programming for beginners.'],
            ['product_name' => 'Organic Honey 500g',   'sku' => 'FOOD-001', 'category' => 'food',        'price' => 349,   'stock' => 100, 'is_active' => true,  'description' => 'Pure organic honey sourced from Himalayan beehives.'],
            ['product_name' => 'USB-C Hub 7-in-1',     'sku' => 'ELEC-002', 'category' => 'electronics', 'price' => 1899,  'stock' => 75,  'is_active' => true,  'description' => '7-port USB-C hub with HDMI, USB 3.0, and SD card reader.'],
            ['product_name' => 'Denim Jeans',          'sku' => 'CLO-002',  'category' => 'clothing',    'price' => 1299,  'stock' => 0,   'is_active' => false, 'description' => 'Slim fit denim jeans, stretchable fabric.'],
            ['product_name' => 'Mechanical Keyboard',  'sku' => 'ELEC-003', 'category' => 'electronics', 'price' => 3499,  'stock' => 25,  'is_active' => true,  'description' => 'TKL mechanical keyboard with Cherry MX switches.'],
        ];

        foreach ($records as $r) {
            AppRecord::create(['app_table_id' => $table->id, 'data' => $r]);
        }

        $this->command->info('Products table seeded (' . count($records) . ' records).');
    }
}
