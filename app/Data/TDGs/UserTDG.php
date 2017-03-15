<?php

namespace App\Data\TDGs;

use App\Data\User;
use App\Singleton;
use DB;

/**
 * @method static UserTDG getInstance()
 */
class UserTDG extends Singleton {

    /**
     * SQL statement to find a User by its id
     *
     * @param int $id
     * @return \stdClass|null
     */
    public function find(int $id) {
        $users = DB::select('SELECT * FROM users WHERE id = ?', [$id]);

        if (empty($users)) {
            return null;
        }

        return $users[0];
    }
    
    /**
     * Returns the check to see if a student is part of Capstone or not
     *
     * @param int $userId
     * @return int
     */
    public function capstone(int $user_id): int {
        $status = DB::select('SELECT capstone FROM users WHERE id = ?', [$user_id]);

        return $status[0]->capstone;
    }

}
