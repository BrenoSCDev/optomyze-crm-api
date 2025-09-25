<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            [
                'name' => 'Lead Captured',
                'description' => 'First stage where new leads enter the funnel.',
                'order' => 1,
                'type' => 'entry',
                'is_active' => true,
                'settings' => [
                    'sla_hours' => 24,
                    'auto_assign' => true,
                    'notifications_enabled' => true,
                    'required_fields' => ['email', 'phone'],
                ],
            ],
            [
                'name' => 'Initial Contact',
                'description' => 'Sales team has contacted the lead.',
                'order' => 2,
                'type' => 'normal',
                'is_active' => true,
                'settings' => [
                    'sla_hours' => 48,
                    'auto_assign' => false,
                    'notifications_enabled' => true,
                    'required_fields' => ['email'],
                ],
            ],
            [
                'name' => 'Demo Scheduled',
                'description' => 'The client has scheduled a demo with our team.',
                'order' => 3,
                'type' => 'service',
                'is_active' => true,
                'settings' => [
                    'sla_hours' => 72,
                    'auto_assign' => true,
                    'notifications_enabled' => true,
                ],
            ],
            [
                'name' => 'Proposal Sent',
                'description' => 'A commercial proposal was sent to the client.',
                'order' => 4,
                'type' => 'proposition',
                'is_active' => true,
                'settings' => [
                    'sla_hours' => 120,
                    'auto_assign' => false,
                    'notifications_enabled' => false,
                ],
            ],
            [
                'name' => 'Decision Maker Engaged',
                'description' => 'The lead is qualified and in negotiation.',
                'order' => 5,
                'type' => 'qualified',
                'is_active' => true,
                'settings' => [
                    'sla_hours' => 96,
                    'auto_assign' => true,
                    'notifications_enabled' => true,
                ],
            ],
            [
                'name' => 'Deal Closed - Won',
                'description' => 'Client has signed the contract and is onboarded.',
                'order' => 6,
                'type' => 'conversion',
                'is_active' => true,
                'settings' => [
                    'notifications_enabled' => true,
                ],
            ],
            [
                'name' => 'Deal Lost',
                'description' => 'Lead was disqualified or rejected the offer.',
                'order' => 7,
                'type' => 'lost',
                'is_active' => false,
                'settings' => [
                    'notifications_enabled' => false,
                ],
            ],
        ];

        foreach ($stages as $stage) {
            DB::table('stages')->insert([
                'name' => $stage['name'],
                'description' => $stage['description'],
                'order' => $stage['order'],
                'type' => $stage['type'],
                'is_active' => $stage['is_active'],
                'settings' => json_encode($stage['settings']),
                'funnel_id' => 1,  // adjust to your funnel
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
