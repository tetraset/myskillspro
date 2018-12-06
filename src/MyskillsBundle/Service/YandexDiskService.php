<?php
namespace MyskillsBundle\Service;

use \Arhitector\Yandex\Disk;
use \Arhitector\Yandex\Disk\Resource\Closed;
use Doctrine\Common\Cache\MemcachedCache;

/**
 * @todo: relocate to composer package
 * Class YandexDiskService
 * @package MyskillsBundle\Service
 */
class YandexDiskService
{
    /** @var array */
    private $tokens;
    /** @var MemcachedCache */
    private $cacheService;
    /** @var Disk[] */
    private $clients;
    const CACHE_TIMEOUT = 60 * 60 * 2; // 2 hours

    public function __construct(
        MemcachedCache $cacheService,
        array $tokens
    ) {
        $this->tokens = $tokens;
        $this->cacheService = $cacheService;
        foreach ($tokens as $token) {
            $this->clients[] = new Disk($token);
        }
    }

    /**
     * @return MemcachedCache
     */
    private function getCacheService()
    {
        return $this->cacheService;
    }

    public function isUrlExists($path, $clientNumber = 1) {
        /**
         * @var Closed $resource
         */
        $resource = $this->clients[$clientNumber-1]->getResource($path);
        return $resource->has();
    }

    public function getDownloadUrl($path, $clientNumber = 1)
    {
        $key = 'download_' . $path . '_' . $clientNumber;

        if(($link = $this->getCacheService()->fetch($key)) !== false) {
            return $link;
        }

        /**
         * @var Closed $resource
         */
        $resource = $this->clients[$clientNumber-1]->getResource($path);

        if(!$resource->has()) {
            $link = null;
        } else {
            $link = $resource->getLink();
        }

        $this->getCacheService()->save($key, $link, self::CACHE_TIMEOUT);
        return $link;
    }

    /**
     * @param $path
     * @param $url
     * @param int $clientNumber
     * @return Disk\Operation|bool
     * @throws \Exception
     */
    public function uploadToSrcByUlr($path, $url, $clientNumber = 1) {
        $resource = $this->clients[$clientNumber - 1]->getResource($path);
        $file = substr($path, strrpos($path, '.'));

        if($resource->has()) {
            return true;
        }

        $pathParts = explode('/', $path);
        $pathOrig = '';

        foreach ($pathParts as $p) {
            $pathOrig .= '/' . $p;

            $resource = $this->clients[$clientNumber - 1]->getResource($pathOrig);

            if(strpos($pathOrig, $file) === false && !$resource->has()) {
                $resource->create();
            } elseif(strpos($pathOrig, $file) !== false && !$resource->has()) {
                return $resource->upload($url);
            }
        }

        return false;
    }

    public function getTokenCount() {
        return count($this->tokens);
    }
}
