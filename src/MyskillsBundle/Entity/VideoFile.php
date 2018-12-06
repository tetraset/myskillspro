<?php

namespace MyskillsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class VideoFile
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\VideoFileRepository")
 * @ORM\Table(name="video_file")
 */
class VideoFile implements DomainObjectInterface
{
    private static $SERVER_PATH_TO_VIDEO_FOLDER = UPLOAD_DIR . '/video';
    private static $URL_TO_VIDEO_FOLDER = UPLOAD_DIR_WEB . '/video';

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=255, nullable=true)
     */
    protected $filename;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated;

    /**
     * Unmapped property to handle file uploads
     */
    private $file;

    public function getFilePath() {
        return self::$SERVER_PATH_TO_VIDEO_FOLDER . '/' . $this->filename;
    }

    public function getUrl() {
        return self::$URL_TO_VIDEO_FOLDER. '/' .$this->filename;
    }

    public static function getServerPath() {
        return self::$SERVER_PATH_TO_VIDEO_FOLDER;
    }

    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Manages the copying of the file to the relevant place on the server
     */
    public function upload()
    {
        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            return;
        }
        $filename = time().'_'.$this->getFile()->getClientOriginalName();

        // we use the original file name here but you should
        // sanitize it at least to avoid any security issues

        // move takes the target directory and target filename as params
        $this->getFile()->move(
            self::$SERVER_PATH_TO_VIDEO_FOLDER,
            $filename
        );

        // set the path property to the filename where you've saved the file
        $this->filename = $filename;

        // clean up the file property as you won't need it anymore
        $this->setFile(null);
    }

    /**
     * Updates the cash value to force the preUpdate and postUpdate events to fire
     */
    public function refreshUpdated()
    {
        $this->setUpdated(new \DateTime());
    }

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
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpdateTasks() {
        $this->upload();
    }

    public function __toString() {
        return $this->getFilename()."";
    }

    public function getObjectIdentifier() {
        return $this->id;
    }

    public function __construct() {
        $this->updated = new \DateTime();
    }
}
