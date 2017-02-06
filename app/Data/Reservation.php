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
    public function __construct(int $userId, string $roomName, \DateTime $timeslot, string $description = null, $recurId = null, $id = null, int $position)
    {
        $this->userId = $userId;
		$this->position = $position;
        $this->roomName = $roomName;
        $this->description = $description;
        $this->timeslot = $timeslot;
        $this->recurId = $recurId;

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
