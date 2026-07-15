<?php

namespace Database\Seeders;

use App\Models\StructuralLevel;
use Illuminate\Database\Seeder;

class StructuralLevelSeeder extends Seeder
{
    /** Template default (company_id null) — disalin ke tenant lewat tombol "Set Default". */
    public function run(): void
    {
        $levels = [
            ['name' => 'Staff',          'sort_order' => 1],
            ['name' => 'Koordinator',    'sort_order' => 2],
            ['name' => 'Supervisor',     'sort_order' => 3],
            ['name' => 'Manager',        'sort_order' => 4],
            ['name' => 'Senior Manager', 'sort_order' => 5],
            ['name' => 'VP',             'sort_order' => 6],
            ['name' => 'Direktur',       'sort_order' => 7],
            ['name' => 'BOD',            'sort_order' => 8],
        ];

        foreach ($levels as $level) {
            StructuralLevel::firstOrCreate(
                ['company_id' => null, 'name' => $level['name']],
                ['sort_order' => $level['sort_order'], 'is_active' => true]
            );
        }
    }
}