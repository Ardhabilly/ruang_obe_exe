<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DosenSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'dosen@ruangobe.test'],
            [
                'name' => 'Dosen RuangOBE',
                'password' => Hash::make('password123'),
                'role' => 'dosen',
            ]
        );
    }
}