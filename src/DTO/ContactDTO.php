<?php

namespace App\DTO;

use App\Entity\Contact;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ContactDTO
{
    private $id;
    private $telephone;
    private $name;
    private $surname1;
    private $surname2;
    private Collection $labels;

    public function __construct(Contact $contact = null)
    {
        if ($contact) {
            $this->extract($contact);
        } else {
            $this->labels = new ArrayCollection();
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTelephone()
    {
        return $this->telephone;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSurname1()
    {
        return $this->surname1;
    }

    public function getSurname2()
    {
        return $this->surname2;
    }

    public function getLabels()
    {
        return $this->labels;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setSurname1($surname1)
    {
        $this->surname1 = $surname1;

        return $this;
    }

    public function setSurname2($surname2)
    {
        $this->surname2 = $surname2;

        return $this;
    }

    public function setLabels($labels)
    {
        $this->labels = $labels;

        return $this;
    }

    public function fill(Contact $contact)
    {
        $contact->setId($this->getId());
        $contact->setTelephone($this->getTelephone());
        $contact->setName($this->getName());
        $contact->setSurname1($this->getSurname1());
        $contact->setSurname2($this->getSurname2());
        $contact->setLabels($this->getLabels());
    }

    /* Assumed:
     * Field[0] = telephone
     * Field[1] = name
     * Field[2] = surname1
     * Field[3] = surname2
     */

    public function extractFromArray(array $contactArray)
    {
        $this->setTelephone($contactArray[0]);
        $this->setName($contactArray[1]);
        $this->setSurname1($contactArray[2]);
        $this->setSurname2($contactArray[3]);
    }

    public function extract(Contact $contact)
    {
        $this->setId($contact->getId());
        $this->setTelephone($contact->getTelephone());
        $this->setName($contact->getName());
        $this->setSurname1($contact->getSurname1());
        $this->setSurname2($contact->getSurname2());
        $this->setLabels($contact->getLabels());

        return $this;
    }

    public function extractFromJson($json)
    {
        if (isset($json->{'id'})) {
            $this->setId($json->{'id'});
        }
        if (isset($json->{'telephone'})) {
            $this->setTelephone($json->{'telephone'});
        }
        if (isset($json->{'name'})) {
            $this->setName($json->{'name'});
        }
        if (isset($json->{'surname1'})) {
            $this->setName($json->{'surname1'});
        }
        if (isset($json->{'surname2'})) {
            $this->setName($json->{'surname2'});
        }

        return $this;
    }
}
