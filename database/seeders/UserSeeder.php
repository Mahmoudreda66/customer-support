<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->create([
            'name' => 'Mahmoud Reda',
            'username' => 'mahmoud',
            'password' => bcrypt('01274385491'),
            'role' => 'manager',
        ]);
    }
}
