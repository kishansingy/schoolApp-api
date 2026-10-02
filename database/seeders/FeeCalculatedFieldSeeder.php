<?php
namespace Database\Seeders;
use App\Models\AppField;
use App\Models\AppTable;
use Illuminate\Database\Seeder;

class FeeCalculatedFieldSeeder extends Seeder
{
    public function run(): void
    {
        $fp = AppTable::where('name', 'fee_payments')->first();
        if (!$fp) return;

        $order = AppField::where('app_table_id', $fp->id)->max('order') + 1;

        AppField::firstOrCreate(
            ['app_table_id' => $fp->id, 'name' => 'total_amount'],
            [
                'label'            => 'Total Amount',
                'type'             => 'currency',
                'mandatory'        => false,
                'readonly'         => true,
                'display'          => true,
                'active'           => true,
                'order'            => $order,
                'calculated'       => true,
                'calculated_value' => '{amount_paid} + {fine} - {discount}',
            ]
        );

        $this->command->info('Fee calculated field seeded.');
    }
}
