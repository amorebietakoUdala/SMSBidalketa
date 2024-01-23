<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contact.
 */
#[ORM\Table(name: 'contact')]
#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact implements \Stringable
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'telephone', type: 'string', length: 13, unique: true, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^(71|72|73|74)\d{7}+$|^6\d{8}+$/', match: 'true', message: "It's not a valid mobile phone")]
    private $telephone;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'surname1', type: 'string', length: 255, nullable: true)]
    private $surname1;

    /**
     * @var string
     */
    #[ORM\Column(name: 'surname2', type: 'string', length: 255, nullable: true)]
    private $surname2;

    /**
     * Labels for the contacts.
     */
    #[ORM\JoinTable(name: 'labels_contacts')]
    #[ORM\ManyToMany(targetEntity: 'Label', inversedBy: 'contacts', cascade: ['persist'])]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $labels;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
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
     * Set telephone.
     *
     * @param string $telephone
     *
     * @return Contact
     */
    public function setTelephone($telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Get telephone.
     *
     * @return string
     */
    public function getTelephone(): string
    {
        return $this->telephone;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Contact
     */
    public function setName($name): ?self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set surname1.
     *
     * @param string $surname1
     *
     * @return Contact
     */
    public function setSurname1($surname1): self
    {
        $this->surname1 = $surname1;

        return $this;
    }

    /**
     * Get surname1.
     *
     * @return string
     */
    public function getSurname1(): ?string
    {
        return $this->surname1;
    }

    /**
     * Set surname2.
     *
     * @param string $surname2
     *
     * @return Contact
     */
    public function setSurname2($surname2): self
    {
        $this->surname2 = $surname2;

        return $this;
    }

    /**
     * Get surname2.
     *
     * @return string
     */
    public function getSurname2(): ?string
    {
        return $this->surname2;
    }

    /**
     * Set labels.
     *
     * @param array $labels
     *
     * @return Contact
     */
    public function setLabels($labels): self
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * Get labels.
     *
     * @return ArrayCollection|Label[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    public function addLabel(Label $label)
    {
        if ($this->labels->contains($label)) {
            return;
        }
        $this->labels[] = $label;
    }

    public function removeLabel(Label $label)
    {
        if (!$this->labels->contains($label)) {
            return;
        }
        $this->labels->removeElement($label);
        // not needed for persistence, just keeping both sides in sync
        $label->removeContact($this);
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->getFullName();
    }

    public function getFullName()
    {
        return $this->name . ' ' . $this->surname1 . ' ' . $this->surname2;
    }

    public function hasLabel($label)
    {
        return $this->labels->contains($label);
    }

    public function __toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'telephone' => $this->telephone,
            'surname1' => $this->surname1,
            'surname2' => $this->surname2,
            'labels' => $this->labels->toArray(),
        ];
    }
}
