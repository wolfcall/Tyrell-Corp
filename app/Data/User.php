<?php

namespace App\Data;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
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
    protected $attempt;
	
	/**
     * @var int
     */
    protected $capstone;

    /**
     * User constructor.
     * @param int $id
     * @param string $name
     * @param string $password
     */
    public function __construct(int $id, string $name, string $password, int $attempt, int $capstone)
    {
        parent::__construct();

        $this->id = $id;
        $this->name = $name;
        $this->password = $password;
		$this->attempt = $attempt;
		$this->capstone = $capstone;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
	
	 /**
     * @return int
     */
    public function getAttempt(): int
    {
        return $this->attempt;
    }
	
	 /**
     * @return int
     */
    public function getCapstone(): int
    {
        return $this->capstone;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @ignore Unused
     *
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @ignore Unused
     *
     * @return null|string
     */
    public function getPassword(): string
    {
        return $this->password;
    }
}
