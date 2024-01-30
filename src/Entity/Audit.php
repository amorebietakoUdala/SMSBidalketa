<?php

namespace App\Entity;

use App\Repository\AuditRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'audit')]
#[ORM\Index(name: 'deliveryId_idx', columns: ['deliveryId'])]
#[ORM\Entity(repositoryClass: AuditRepository::class)]
class Audit
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'telephones', type: 'text')]
    private $telephones;
    /**
     * @var DateTime
     */
    #[ORM\Column(name: 'timestamp', type: 'datetime')]
    private $timestamp;
    /**
     * @var string
     */
    #[ORM\Column(name: 'responseCode', type: 'string')]
    private $responseCode;
    /**
     * @var string
     */
    #[ORM\Column(name: 'message', type: 'string')]
    private $message;
    /**
     * @var string
     */
    #[ORM\Column(name: 'response', type: 'text')]
    private $response;

    #[ORM\ManyToOne(targetEntity: 'User', cascade: ['persist'])]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'deliveryId', type: 'bigint')]
    private $deliveryId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'messageContent', type: 'string', nullable: 'true')]
    private $messageContent;

    public function __construct()
    {
        $date = new DateTimeImmutable();
        $milli = (int)$date->format('Uv'); // Timestamp in milliseconds
        $this->deliveryId = $milli;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function setTimestamp(DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function setResponse($response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

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

    public function getResponseCode()
    {
        return $this->responseCode;
    }

    public function setResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;

        return $this;
    }

    public function getTelephones()
    {
        return json_decode($this->telephones);
    }

    public function setTelephones($telephones)
    {
        $this->telephones = $telephones;

        return $this;
    }

    public static function createAudit(array $telephones, $responseCode, $message, $fullResponse, $user, $messageContent = null): Audit
    {
        $audit = new self();
        $audit->setTelephones(json_encode($telephones));
        $audit->setTimestamp(new DateTime());
        $audit->setResponseCode($responseCode);
        $audit->setMessage($message);
        $audit->setResponse(json_encode($fullResponse));
        $audit->setUser($user);
        $audit->setMessageContent($messageContent);

        return $audit;
    }

    public function getDeliveryId()
    {
        return $this->deliveryId;
    }

    public function setDeliveryId(string $deliveryId)
    {
        $this->deliveryId = $deliveryId;

        return $this;
    }

    public function getMessageContent()
    {
        return $this->messageContent;
    }

    public function setMessageContent(string $messageContent)
    {
        $this->messageContent = $messageContent;

        return $this;
    }
}
