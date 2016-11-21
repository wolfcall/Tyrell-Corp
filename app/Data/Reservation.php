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
     * Room constructor.
     * @param int $userId
     * @param string $roomName
     * @param \DateTime $timeslot
     * @param string $description
     * @param int $id
     */
    public function __construct(int $userId, string $roomName, \DateTime $timeslot, string $description = null, $id = null)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->roomName = $roomName;
        $this->description = $description;
        $this->timeslot = $timeslot;
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
}
