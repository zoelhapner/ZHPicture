<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'id' => Str::uuid(),

                'fullname' => 'Super Admin',
                'nickname' => 'Admin',
                'gender' => 1,

                'email_verified_at' => now(),
                'password' => Hash::make('password'),

                'birth_place' => 'Jakarta',
                'identity_number' => '1234567890123456',
                'birth_date' => '1990-01-01',

                'religion_id' => \App\Models\Religion::first()?->id,
                'province_id' => 5,
                'city_id' => 91,
                'district_id' => 1104,
                'sub_district_id' => 15935,
                'postal_code_id' => 15935,

                'address' => 'Jl. Contoh Alamat No. 1',
                'phone' => '08123456789',

                'photo' => null,
                'identity_photo' => null,

                'remember_token' => Str::random(10),
            ]
        );

        // 🔥 Assign role Super-Admin
        $role = Role::where('name', 'Super-Admin')->first();

        if ($role && !$user->hasRole($role->name)) {
            $user->assignRole($role);
        }
    }
}
