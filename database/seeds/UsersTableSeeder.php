<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1, $userId = 10000000; $i < 10; ++$i) {
            DB::table('users')->insert([
                'id' => $userId + $i,
                'name' => 'Test User ' . $i,
                'password' => bcrypt('password'),
            ]);
        }
    }
}
