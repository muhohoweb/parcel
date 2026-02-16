<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->truncate();
        User::create([
            'first_name' => 'Lucy',
            'last_name' => 'Manyara',
            'email' => 'lucy@gmail.com',
            'password' => bcrypt('363WAIs7ce6M'),
            'role' => 'admin',
        ]);
    }
}