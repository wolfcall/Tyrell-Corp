<?php

namespace App\Data;

class Reservation
{
    /**
     * @var int
     */
    protected $id;
	
	 /**
     * @var int
     */
    protected $position;

    /**
     * @var int
     */
    protected $userId;
	
	 /**
     * @var int
     */
    protected $markers;
	
	 /**
     * @var int
     */
    protected $projectors;
	
	 /**
     * @var int
     */
    protected $laptops;
	
	 /**
     * @var int
     */
    protected $cables;

    /**
     * @var string
     */
    protected $roomName;

    /**
     * @var \DateTime
     */
    protected $timeslot;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $recurId;

    /**
     * Room constructor.
     * @param int $userId
     * @param string $roomName
     * @param \DateTime $timeslot
     * @param string $description
     * @param null $recurId
     * @param int $id
     */
    public function __construct(int $userId, string $roomName, \DateTime $timeslot, string $description = null, $recurId = null, $id = null, int $position, int $markers, int $projectors, int $laptops, int $cables)
    {
        $this->userId = $userId;
		$this->position = $position;
        $this->roomName = $roomName;
        $this->description = $description;
        $this->timeslot = $timeslot;
        $this->recurId = $recurId;
		$this->markers = $markers;
		$this->projectors = $projectors;
		$this->cables = $cables;
		$this->laptops = $laptops;

        // key
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;
    }
	
	/**
     * @return int
     */
    public function getMarkers(): int
    {
        return $this->markers;
    }

    /**
     * @param int $markers
     */
    public function setMarkers(int $markers)
    {
        $this->markers = $markers;
    }
	
	/**
     * @return int
     */
    public function getProjectors(): int
    {
        return $this->projectors;
    }

    /**
     * @param int $Projectors
     */
    public function setProjectors(int $projectors)
    {
        $this->projectors = $projectors;
    }
	
	/**
     * @return int
     */
    public function getLaptops(): int
    {
        return $this->laptops;
    }

    /**
     * @param int $Laptops
     */
    public function setLaptops(int $laptops)
    {
        $this->laptops = $laptops;
    }
	
	/**
     * @return int
     */
    public function getCables(): int
    {
        return $this->cables;
    }

    /**
     * @param int $Cables
     */
    public function setCables(int $cables)
    {
        $this->cables = $cables;
    }
	
	  /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $userId
     */
    public function setPosition(int $position)
    {
        $this->position = $position;
    }
	

    /**
     * @return string
     */
    public function getRoomName(): string
    {
        return $this->roomName;
    }

    /**
     * @param string $roomName
     */
    public function setRoomName(string $roomName)
    {
        $this->roomName = $roomName;
    }

    /**
     * @return \DateTime
     */
    public function getTimeslot(): \DateTime
    {
        return $this->timeslot;
    }

    /**
     * @param \DateTime $timeslot
     */
    public function setTimeslot(\DateTime $timeslot)
    {
        $this->timeslot = $timeslot;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getRecurId(): string
    {
        return $this->recurId;
    }

    /**
     * @param string $recurId
     */
    public function setRecurId(string $recurId)
    {
        $this->recurId = $recurId;
    }
}
