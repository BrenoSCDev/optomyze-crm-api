<?php

// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin User',
            'email' => 'admin@optomyze.io',
            'password' => Hash::make('password'),
            'phone' => '+1-555-1111',
            'role' => 'admin',
            'is_active' => true,
            'company_id' => 1,
        ]);
    }
}