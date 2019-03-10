<?php
namespace MyskillsBundle\Service;

use Doctrine\Common\Cache\MemcachedCache;
use MyskillsBundle\Command\ParserCommand;
use MyskillsBundle\Entity\Video;
use Symfony\Bridge\Monolog\Logger;

class OroroService extends ParserCommand {
    public static $USER_IP = '188.165.0.128:8080';
    public static $USER_BROWSER = 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)';
    const COOKIE = '__cfduid=d8b9f6b181949c9600213f398eda669731481303431; uid=BAhJIiViYjA3ZDg5NDZiY2ExMDQ3NGJhZjJiZjYxM2Q2NWFlMAY6BkVG--667b92ad885c55c1fc727ef8a8feb14897632d8f; nl=true; remember_user_token=BAhbCFsGaQOL5xNJIhljbXAtenZjVFlaY2Q1dlNUQmkzNgY6BkVGSSIWMTQ4MTYwMjY5MS4xMjU5MDgGOwBG--111e02aff07ce9c033d847056cbb203379e3c95e; check=t; _ga=GA1.2.802756680.1481303434; subtitles=en; locale=en; _ororo_session=ZHllaVVxNWVidUdrdWdOMzE3NVVrYjQ5T3BtZFR0bkxURURJRm9QQy9NV2M5UWFjM2JFQ1BKcUs0LzB4eVFVMnQrN2lGeXRhZVZldUdHbjluZkJkN2M3M1hBSUlzbndoSVczaTNua1lvRmJYOEc4cDZKSkE3SzlxUHNiYjRYM3hnbEZGV0JhUFdrbHNmVmQrcnl1Q0lQdXpyWmNtaDFYREV2R3B4Q3VJMDhMNjJJSmc5eWlFdE1ZeUpnem10UnRKVE1HNmF4dWo2V0RlOFBQZ1FDZUFJUjZNNy8vN1NRVFhNcGRKUitoYjc3TjB0eE41RmtSaTJHTWZlc0dzdjNMMFNVU0VSUHloUUIybkcyMjlmY2NtYUZrYzBxMlFyQWxBa2p6dXpIMTB4OTV0Q1VVaDNVL3FzVnhaaXpXUkhUbHhPelBvakd6UjJRK0NCZ0NUR2F6VDV3PT0tLWlYSnJIQVBieW1vUW0rNW9JK0JtclE9PQ%3D%3D--0fb77767b7bb9b67adde80e286f2bcafa4f8222b';
    const SERIES_URL = 'https://ororo.tv/ru/shows/{series_code}/videos/{id}';
    const MOVIES_URL = 'https://ororo.tv/ru/movies/{series_code}/video';

    /** @var Logger */
    protected $logger;

    /** @var MemcachedCache */
    private $cacheService;

    const CACHE_TIMEOUT = 60 * 60 * 12; // 12 hours

    private $disableIps = [];

    public function __construct(
        Logger $logger,
        MemcachedCache $cacheService
    )
    {
        parent::__construct('OroroService');
        $this->logger = $logger;
        $this->cacheService = $cacheService;
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return MemcachedCache
     */
    private function getCacheService()
    {
        return $this->cacheService;
    }

    public function addVideoUrl(Video $video) {
        if(!$video->getOroId()) {
            return;
        }

        $key = 'video_url_v10_' . $video->getOroId();

        if(($data = $this->getCacheService()->fetch($key)) === false) {
            $data = $this->getOroroDataFromSite($video->getOroId(), $video->getSeries()->getCode(), $video->getSeries()->getType());
            $this->getCacheService()->save($key, $data, self::CACHE_TIMEOUT);
        }

        $video->setVideoLink(!empty($data['link']) ? $data['link'] : null);

        if(!empty($data['vtt'])) {
            uksort(
                $data['vtt'],
                function($k1, $k2) {
                    if($k1 == 'ru' || $k1 == 'en') {
                        return -1;
                    }
                    return 1;
                }
            );
            $video->setVttData($data['vtt']);
        }
    }

    private function getOroroDataFromSite($oroId, $seriesCode, $type = 'series') {
        $link = str_replace(['{id}', '{series_code}'], [$oroId, $seriesCode], $type == 'series' ? self::SERIES_URL : self::MOVIES_URL);
        $headers = ['x-requested-with' => 'XMLHttpRequest'];
        $headers['cookie'] = self::COOKIE;

        $content = null;
        try {
            $content = $this->parse($link, null, $headers, self::$USER_IP, self::$USER_BROWSER);
        } catch (\Exception $e) {
            $this->logger->addError("Problem with parsing: " . $e->getMessage());
            return $this->newRequest($oroId, $seriesCode, $type);
        }

        if (empty($content)) {
            $this->logger->addWarning("Empty ororo content for $link: $oroId ($seriesCode)");
            return $this->newRequest($oroId, $seriesCode, $type);
        }

        $data = [];

        preg_match("/<source src='(?P<link>[^']+)'/iUms", $content, $linkMatch);

        if(!empty($linkMatch['link'])) {
            $data['link'] = trim($linkMatch['link']);
        }

        preg_match_all("/<track (data-default )?kind='subtitles'.+src='(?P<link>[^']+)' srclang='(?P<lang>[^']+)'>/iUms", $content, $vttMatch);

        if(!empty($vttMatch['link'])) {
            foreach($vttMatch['link'] as $i=>$link) {
                $data['vtt'][$vttMatch['lang'][$i]] = trim($link);
            }
        }

        return $data;
    }

    private function newRequest($oroId, $seriesCode, $type) {
        $this->disableIps[] = self::$USER_IP;
        $availableIps = array_diff(self::$PROXY_IPS, $this->disableIps);

        if(empty($availableIps)) {
            $this->logger->addError("No ip for next request");
            return [];
        }

        self::$USER_IP = current($availableIps);

        return $this->getOroroDataFromSite($oroId, $seriesCode, $type);
    }
}
