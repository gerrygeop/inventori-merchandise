<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // DB::table('roles')->insert([
        //     ['name' => 'Admin'],
        //     ['name' => 'Manager'],
        // ]);

        // DB::table('role_user')->insert([
        //     [
        //         'role_id' => 1,
        //         'user_id' => 1,
        //     ],
        // ]);

        // \App\Models\Supplier::factory(2)->create();
        \App\Models\Product::factory(10)->create();
        \App\Models\Category::factory(10)->create();
    }
}
