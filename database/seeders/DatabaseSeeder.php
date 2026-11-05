<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::where('username', 'admin')->orWhere('email', 'admin@hospital.local')->first();
        if (! $admin) {
            User::create([
                'name' => 'Hospital Administrator',
                'username' => 'admin',
                'email' => 'admin@hospital.local',
                'password' => 'password123',
                'role' => 'admin',
                'must_change_password' => true,
            ]);
        } elseif (empty($admin->username)) {
            $admin->update(['username' => 'admin']);
        }

        $defaults = [
            'hospital_name' => 'Bethel Baptist Hospital, Inc.',
            'hospital_address' => 'Fortich St., Sumpong, Malaybalay City, Bukidnon 8700',
            'logo_path' => 'images/bethel-logo.png',
            'asset_no_prefix' => 'BBH',
            'tag_width_cm' => '10',
            'tag_height_cm' => '6',
            'show_barcode' => '0',
            'backup_folder' => Setting::defaultBackupFolder(),
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $this->call(CategorySeeder::class);
    }
}
