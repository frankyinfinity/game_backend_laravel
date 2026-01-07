<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ApiUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('API_USER_EMAIL', 'api@email.it');
        
        $user = User::where('email', $email)->first();

        if (!$user) {
            User::create([
                'name' => 'API User',
                'email' => $email,
                'password' => Hash::make(env('API_USER_PASSWORD', 'api')),
            ]);
        }
    }
}
