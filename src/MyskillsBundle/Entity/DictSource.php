<?php

namespace MyskillsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use JMS\Serializer\Annotation\Exclude;

/**
 * DictSource
 *
 * @ORM\Table(name="dict_source", indexes={@ORM\Index(name="source_priority", columns={"priority"})})
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\DictSourceRepository")
 */
class DictSource implements DomainObjectInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_source", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Exclude
     */
    private $idSource;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="text", length=255, nullable=false)
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255, nullable=true)
     */
    private $link;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @Exclude
     */
    protected $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="priority", type="integer", nullable=false)
     * @Exclude
     */
    private $priority = 10;

    public function getObjectIdentifier() {
        return $this->idSource;
    }

    /**
     * Get idSource
     *
     * @return integer
     */
    public function getIdSource()
    {
        return $this->idSource;
    }

    /**
     * Set source
     *
     * @param string $source
     *
     * @return DictSources
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set link
     *
     * @param string $link
     *
     * @return DictSources
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     *
     * @return DictSources
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
}
