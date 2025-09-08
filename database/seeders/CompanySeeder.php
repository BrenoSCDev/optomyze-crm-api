<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('companies')->insert([
            'name' => 'Optomyze', 
            'email' => 'optomyze@optomyze.io', 
            'phone' => '+1-555-0101', 
            'website' => 'https://optomyze.com', 
            'subscription_plan' => 'enterprise', 
            'is_active' => true,
        ]);
    }
}
