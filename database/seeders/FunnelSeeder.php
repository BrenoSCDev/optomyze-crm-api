<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FunnelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('funnels')->insert([
            'company_id' => 1,
            'name' => 'Sales Pipeline',
            'description' => 'Main funnel for managing sales opportunities.',
            'is_active' => true,
            'created_by' => 1,
        ]);
    }
}
