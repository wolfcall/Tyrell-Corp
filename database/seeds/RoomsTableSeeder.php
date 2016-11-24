<?php

use Illuminate\Database\Seeder;

class RoomsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1, $roomNumber = 900; $i < 10; ++$i) {
            DB::table('rooms')->insert([
                'name' => 'H-' . ($roomNumber + $i)
            ]);
        }
    }
}
