<?php

namespace App\Data\TDGs;

use App\Data\User;
use App\Singleton;
use DB;

/**
 * @method static UserTDG getInstance()
 */
class UserTDG extends Singleton
{
    /**
     * Adds a list of Users to the database
     *
     * @ignore Unused
     *
     * @param array $newList
     */
    public function addMany(array $newList)
    {
        foreach ($newList as $user) {
            $this->create($user);
        }
    }

    /**
     * Updates a list of Users in the database
     *
     * @ignore Unused
     *
     * @param array $updateList
     */
    public function updateMany(array $updateList)
    {
        foreach ($updateList as $user) {
            $this->update($user);
        }
    }

    /**
     * Removes a list of Users in the database
     *
     * @ignore Unused
     *
     * @param array $deleteList
     */
    public function deleteMany(array $deleteList)
    {
        foreach ($deleteList as $user) {
            $this->remove($user);
        }
    }

    /**
     * SQL statement to create a new User row
     *
     * @ignore Unused
     *
     * @param User $user
     */
    public function create(User $user)
    {
        DB::insert('INSERT INTO users (id, name, password) VALUES (:id, :name, :password)', [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'password' => $user->getPassword()
        ]);
    }

    /**
     * SQL statement to update a new User row
     *
     * @ignore Unused
     *
     * @param User $user
     */
    public function update(User $user)
    {
        DB::update('UPDATE users SET name = :name WHERE id = :id', [
            'id' => $user->getId(),
            'name' => $user->getName()
        ]);
    }

    /**
     * SQL statement to delete a new User row
     *
     * @ignore Unused
     *
     * @param User $user
     */
    public function remove(User $user)
    {
        DB::delete('DELETE FROM users WHERE id = :id', [
            'id' => $user->getId()
        ]);
    }

    /**
     * SQL statement to find a User by its id
     *
     * @param int $id
     * @return \stdClass|null
     */
    public function find(int $id)
    {
        $users = DB::select('SELECT * FROM users WHERE id = ?', [$id]);

        if (empty($users)) {
            return null;
        }

        return $users[0];
    }
}
