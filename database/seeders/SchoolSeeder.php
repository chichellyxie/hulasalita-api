<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

/**
 * Secondary and integrated schools in Urbiztondo, Pangasinan.
 * NOTE: confirm each barangay with the Urbiztondo Schools District Office;
 * the admin can correct entries through Manage Schools.
 */
class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $schools = [
            ['name' => 'Urbiztondo National High School', 'barangay' => 'Poblacion'],
            ['name' => 'Urbiztondo Integrated School', 'barangay' => 'Poblacion'],
            ['name' => 'Urbiztondo Catholic School', 'barangay' => 'Poblacion'],
            ['name' => 'Urbiztondo Private High School', 'barangay' => 'Poblacion'],
            ['name' => 'Balangay National High School', 'barangay' => 'Balangay'],
            ['name' => 'Bayaoas Integrated School', 'barangay' => 'Bayaoas'],
            ['name' => 'Dalanguiring Integrated School', 'barangay' => 'Dalanguiring'],
            ['name' => 'Galarin National High School', 'barangay' => 'Galarin'],
            ['name' => 'Real National High School', 'barangay' => 'Real'],
        ];

        foreach ($schools as $school) {
            School::updateOrCreate(
                ['name' => $school['name']],
                ['barangay' => $school['barangay'], 'is_active' => true],
            );
        }
    }
}
