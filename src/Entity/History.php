<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="history",indexes={@ORM\Index(name="providerId_idx", columns={"providerId"})})
 * @ORM\Entity(repositoryClass="App\Repository\HistoryRepository")
 */
class History
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="provider", type="string", length=20, nullable=true)
     */
    private $provider;

    /**
     * @var string
     *
     * @ORM\Column(name="providerId", type="bigint", length=50, nullable=true)
     */
    private $providerId;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;
    /**
     * @var string
     *
     * @ORM\Column(name="senderAccount", type="string", length=50, nullable=true)
     */
    private $senderAccount;
    /**
     * @var string
     *
     * @ORM\Column(name="rctp_name_number", type="string", length=50, nullable=true)
     */
    private $rctpNameNumber;
    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string", length=400, nullable=true)
     */
    private $text;
    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=50, nullable=true)
     */
    private $ip;
    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=50, nullable=true)
     */
    private $status;
    /**
     * @var string
     *
     * @ORM\Column(name="es_unicode", type="string", length=50, nullable=true)
     */
    private $esUnicode;

    public function __construct($history = null, $provider = null)
    {
        if ($history) {
            if (is_array($history)) {
                $this->loadFromArray($history, $provider);
            } else {
                $this->loadFromJson($history, $provider);
            }
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getEsUnicode(): string
    {
        return $this->esUnicode;
    }

    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    public function setText(string $text)
    {
        $this->text = $text;

        return $this;
    }

    public function setIp(string $ip)
    {
        $this->ip = $ip;

        return $this;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;

        return $this;
    }

    public function setEsUnicode(string $esUnicode)
    {
        $this->esUnicode = $esUnicode;

        return $this;
    }

    public function getRctpNameNumber(): string
    {
        return $this->rctpNameNumber;
    }

    public function setRctpNameNumber(string $rctpNameNumber)
    {
        $this->rctpNameNumber = $rctpNameNumber;

        return $this;
    }

    public function getSenderAccount()
    {
        return $this->senderAccount;
    }

    public function setSenderAccount($senderAccount)
    {
        $this->senderAccount = $senderAccount;

        return $this;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function getProviderId()
    {
        return $this->providerId;
    }

    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    public function setProviderId($providerId)
    {
        $this->providerId = $providerId;

        return $this;
    }

    public function loadFromJson($json, $provider)
    {
        if ('Dinahosting' === $provider) {
            $this->setProviderId($json->{'id'});
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $json->{'date'});
            $this->setDate($date);
            $this->setSenderAccount($json->{'from'});
            $this->setRctpNameNumber($json->{'rctp_name_number'});
            $this->setText($json->{'text'});
            $this->setIp($json->{'ip'});
            $this->setStatus($json->{'status'});
            $this->setEsUnicode($json->{'es_unicode'});
        }
        if ('Acumbamail' === $provider) {
            $this->setProviderId($json->{'sms_id'});
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $json->{'sent_at'});
            $this->setDate($date);
            $this->setSenderAccount($json->{'sender'});
            $this->setRctpNameNumber($json->{'phone'});
            $this->setText($json->{'sms_content'});
            if ('DELIVERED' === $json->{'status'}) {
                $this->setStatus('SENT');
            } else {
                $this->setStatus($json->{'status'});
            }
        }
        $this->setProvider($provider);
    }

    public function loadFromArray($array, $provider)
    {
        if ('Dinahosting' === $provider) {
            $this->setProviderId($array['id']);
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $array['date']);
            $this->setDate($date);
            $this->setSenderAccount($array['from']);
            $this->setRctpNameNumber($array['rctp_name_number']);
            $this->setText($array['text']);
            $this->setIp($array['ip']);
            $this->setStatus($array['status']);
            $this->setEsUnicode($array['es_unicode']);
        }
        if ('Acumbamail' === $provider) {
            $this->setProviderId($array['sms_id']);
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $array['sent_at']);
            $this->setDate($date);
            $this->setSenderAccount($array['sender']);
            $this->setRctpNameNumber($array['phone']);
            $this->setText($array['sms_content']);
            if ('DELIVERED' === $array['status']) {
                $this->setStatus('SENT');
            } else {
                $this->setStatus($array['status']);
            }
        }
        $this->setProvider($provider);
    }
}
