<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        if (Vendor::count() > 0) {
            return;
        }

        $vendors = [
            ['name' => '3 J MEDICAL', 'is_donation' => false],
            ['name' => 'BBH CARPENTRY', 'is_donation' => false],
            ['name' => 'CITI HARDWARE', 'is_donation' => false],
            ['name' => 'DONATION', 'is_donation' => true],
            ['name' => 'DONATION FROM UNILAB', 'is_donation' => true],
            ['name' => 'DRAKE MARKETING', 'is_donation' => false],
            ['name' => 'GAISANO', 'is_donation' => false],
            ['name' => 'SOLIDMARK', 'is_donation' => false],
            ['name' => 'U.S DONATION', 'is_donation' => true],
            ['name' => 'VALENCIA COPY', 'is_donation' => false],
        ];

        $seen = [];
        foreach ($vendors as $data) {
            $normalizedName = Vendor::normalizeName($data['name']);
            $key = Vendor::normalizedKey($normalizedName);

            if ($key === '' || isset($seen[$key]) || Vendor::findByNormalizedName($normalizedName)) {
                continue;
            }

            $seen[$key] = true;
            Vendor::create([
                'name' => $normalizedName,
                'is_donation' => (bool) $data['is_donation'],
                'active' => true,
            ]);
        }
    }
}
