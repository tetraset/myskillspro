<?php

namespace MyskillsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Acl\Model\DomainObjectInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class VttSubtitle
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="MyskillsBundle\Repository\VttSubtitleRepository")
 * @ORM\Table(name="subtitle_file")
 */
class VttSubtitle implements DomainObjectInterface
{
    public static $SERVER_PATH_TO_IMAGE_FOLDER = UPLOAD_DIR . '/vtt';
    public static $URL_TO_IMAGE_FOLDER = UPLOAD_DIR_WEB_RELATIVE . '/vtt';
    const NESTING_LEVEL = 3;

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
     * @ORM\Column(type="string", length=255, nullable=true)
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

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=4, nullable=true)
     */
    private $lang = 'en';

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $hash;

    public static function getVttPath() {
        return self::$SERVER_PATH_TO_IMAGE_FOLDER;
    }

    public function getFilePath() {
        return self::$SERVER_PATH_TO_IMAGE_FOLDER . '/' . self::generatePath($this->filename) . '/' . $this->filename;
    }

    public function getUrl() {
        return self::$URL_TO_IMAGE_FOLDER .'/' .  self::generatePath($this->filename) . '/' .$this->filename;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param string $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
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
        $filename = md5(STATIC_SERVER . time()) . '_' . $this->getFile()->getClientOriginalName();

        // we use the original file name here but you should
        // sanitize it at least to avoid any security issues
        @mkdir(self::generatePath($filename), 0777, true);

        // move takes the target directory and target filename as params
        $this->getFile()->move(
            self::$SERVER_PATH_TO_IMAGE_FOLDER . '/' . self::generatePath($filename),
            $filename
        );

        // set the path property to the filename where you've saved the file
        $this->filename = $filename;

        // srt file for subscribers
        if(strpos('_free_', $this->filename) === false) {
            $subFile = file_get_contents($this->getFilePath());
            $subFile = trim(str_replace('WEBVTT', '', $subFile));

            $subFile = preg_replace('/(\d)\.(\d)/i', '$1,$2', $subFile);
            file_put_contents(str_replace('.vtt', '.srt', $this->getFilePath()), $subFile);
        }

        if(strrpos($this->filename, '.vtt') === false) {
            $subFile = file_get_contents($this->getFilePath());
            
            $subFile = "WEBVTT\n\n" . $subFile;

            $subFile = preg_replace('/(\d)\,(\d)/i', '$1.$2', $subFile);
            file_put_contents(str_replace('.srt', '.vtt', $this->getFilePath()), $subFile);
        }

        $this->filename = str_replace('.srt', '.vtt', $this->filename);

        // clean up the file property as you won't need it anymore
        $this->setFile(null);
    }

    /**
     * Преобразует название файла в путь, разбирая по символам от начала до указанного.
     * Нужно для правильного пути к файлу в рамках оптимизации количества файлов в одной директории.
     *
     * Пример. 1a7s456 -> /1/a/1a7
     *
     * @param string $filename
     * @return string
     */
    public static function generatePath($filename) {

        $pathParts = [];
        $fileNamePartArr = explode('.', $filename);
        $fileNamePart = array_shift($fileNamePartArr);

        for ($i = 0; $i < self::NESTING_LEVEL; $i++) {
            $symbol =  $i < (self::NESTING_LEVEL-1) ? substr($fileNamePart, $i, 1) : substr($fileNamePart, 0, self::NESTING_LEVEL);
            $pathParts[] = (string)$symbol ;
        }

        $path = implode('/', $pathParts);

        return $path;
    }

    public function calculateHash() {
        $this->hash = md5(file_get_contents($this->getFilePath()));
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
        $this->calculateHash();
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
