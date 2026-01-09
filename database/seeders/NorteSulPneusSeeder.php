<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Company;
use App\Models\User;

class NorteSulPneusSeeder extends Seeder
{
    public function run(): void
    {
        // Create the company
        $company = Company::create([
            'name' => 'Norte Sul Pneus',
            'email' => 'contato@nortesulpneus.com.br',
            'phone' => '+55 11 99999-9999',
            'website' => 'https://nortesulpneus.com.br',
            'subscription_plan' => 'premium',
            'leads_limit' => 25000,
            'users_limit' => 10,
            'storage_limit_gb' => 50,
            'active_deals_limit' => 500,
            'tasks_limit' => 25000,
            'integrations_limit' => 10,
            'products_limit' => 500,
            'product_module' => 'ERP',
            'is_active' => true,
            'settings' => json_encode([
                'timezone' => 'America/Sao_Paulo',
                'language' => 'pt_BR',
            ]),
        ]);

        // Create a user associated with the company
        User::create([
            'name' => 'Breno Norte Sul',
            'email' => 'breno@nortesulpneus.com.br',
            'password' => 'password123', // change default password
            'phone' => '+55 11 98888-8888',
            'role' => 'admin',
            'is_active' => true,
            'company_id' => $company->id, // link user to company
        ]);
    }
}
