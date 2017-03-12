<?php

namespace App\Data;

class Room {

    /**
     * @var string
     */
    protected $name;

    /**
     * Room constructor.
     * @param string $name
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @ignore Unused
     *
     * @param string $name
     */
    public function setName(string $name) {
        $this->name = $name;
    }

}
