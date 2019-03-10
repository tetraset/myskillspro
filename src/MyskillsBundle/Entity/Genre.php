<?php
namespace MyskillsBundle\Entity;

use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Genre
 *
 * @deprecated
 * @ORM\Table(name="genre", uniqueConstraints={@ORM\UniqueConstraint(name="ru_title", columns={"ru_title"}), @ORM\UniqueConstraint(name="en_title", columns={"en_title"})}))
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\GenreRepository")
 */
class Genre implements DomainObjectInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ruTitle;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $enTitle;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRuTitle()
    {
        return $this->ruTitle;
    }

    /**
     * @param string $ruTitle
     */
    public function setRuTitle($ruTitle)
    {
        $this->ruTitle = $ruTitle;
    }

    /**
     * @return string
     */
    public function getEnTitle()
    {
        return $this->enTitle;
    }

    /**
     * @param string $enTitle
     */
    public function setEnTitle($enTitle)
    {
        $this->enTitle = $enTitle;
    }
    
    public function getObjectIdentifier() {
        return $this->id;
    }

    public function __toString() {
        return $this->ruTitle ?: $this->enTitle;
    }
}