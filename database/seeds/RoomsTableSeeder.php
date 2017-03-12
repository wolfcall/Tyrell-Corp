<?php

use Illuminate\Database\Seeder;

class RoomsTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $now = date("Y-m-d G:i:s");

        for ($i = 1, $roomNumber = 900; $i <= 5; ++$i) {
            DB::table('rooms')->insert([
                'name' => 'H-' . ($roomNumber + $i),
                'dateTime' => $now
            ]);
        }
    }

}
