<?php

namespace App\Message;

/**
 * Class SendMessage
 *
 * This class is responsible for handling the sending of messages.
 *
 * @package App\Message
 */
class SmsNotification
{
   public function __construct(
    private array $telephones, 
    private string $message, 
    private \DateTime $date, 
    private int $userId,
  ) {
  }

    public function getTelephones()
    {
        return $this->telephones;
    }

    public function setTelephones($telephones)
    {
        $this->telephones = $telephones;

        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }
}