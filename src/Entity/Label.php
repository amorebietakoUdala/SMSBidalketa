<?php

namespace App\Entity;

use App\Repository\LabelRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation as SFSerializer;

/**
 * Label.
 */
#[ORM\Table(name: 'label')]
#[ORM\Entity(repositoryClass: LabelRepository::class)]
class Label implements \Stringable
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[SFSerializer\Groups(['show'])]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, unique: true)]
    #[SFSerializer\Groups(['show'])]
    private $name;

    /**
     * Labels for the contacts.
     */
    #[ORM\ManyToMany(targetEntity: 'Contact', mappedBy: 'labels')]
    #[SFSerializer\Groups(['none'])]
    private $contacts;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $id
     *
     * @return Label
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Label
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return ArrayCollection|Contact[]
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    public function setContacts($contacts)
    {
        $this->contacts = $contacts;

        return $this;
    }

    public function addContact(Contact $contact)
    {
        if ($this->contacts->contains($contact)) {
            return;
        }
        $this->contacts[] = $contact;
    }

    public function removeContact(Contact $contact)
    {
        if (!$this->contacts->contains($contact)) {
            return;
        }
        $this->contacts->removeElement($contact);
        // not needed for persistence, just keeping both sides in sync
        $contact->removeLabel($this);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
