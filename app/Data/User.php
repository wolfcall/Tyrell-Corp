<?php

namespace App\Data;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable {

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var int
     */
    protected $capstone;

    /**
     * User constructor.
     * @param int $id
     * @param string $name
     * @param string $password
     * @param int $attempt
     * @param int $capstone
     */
    public function __construct(int $id, string $name, string $password, int $capstone) {
        parent::__construct();

        $this->id = $id;
        $this->name = $name;
        $this->password = $password;
        $this->capstone = $capstone;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCapstone(): int {
        return $this->capstone;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

}
