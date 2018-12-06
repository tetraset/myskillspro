<?php
namespace MyskillsBundle\Command;

use Doctrine\ORM\EntityManager;
use MyskillsBundle\DomainManager\Video\VideoManager;
use MyskillsBundle\Entity\Video;
use MyskillsBundle\Entity\VttSubtitle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

set_time_limit(0);
date_default_timezone_set('Europe/Moscow');

class YoutubeParserCommand extends Command {
    private $webDirectory;
    private $videoManager;
    private $pageToken;
    /**
     * @var EntityManager
     *
     */
    protected $em;
    private $channels = [
        'ted' => 'UCAuUUnT6oDeKwE6v1NGQxug',
        'expedia' => 'UCGaOvAFinZ7BCN_FDmw74fQ',
        'TheEllenShow' => 'UCp0hYYBW6IMayGgR-WeoCvQ',
        'GoodMythicalMorning' => 'UC4PooiX37Pld1T8J5SYT-SQ',
        'CollegeHumor' => 'UCPDXXXJj9nax0fr0Wfc048g',
        'JimmyKimmelLive' => 'UCa6vGFO9ty8v5KZJXQxdhaw',
        'RosannaPansino' => 'UCjwmbv6NE4mOh8Z8VhPUx1Q',
        'AsapSCIENCE' => 'UCC552Sd-3nyi_tk2BudLUzA',
        'CrashCourse' => 'UCX6b17PVsYBQ0ip5gyeme-Q',
        'BadLipReading' => 'UC67f2Qf7FYhtoUIF4Sf29cA',
        'LastWeekTonight' => 'UC3XTzVzaHQEd30rQbuvCtTQ',
        'MatthewSantoro' => 'UCXhSCMRRPyxSoyLSPFxK7VA',
        'ComedyCentral' => 'UCUsN5ZwHx2kILm84-jPDeXw',
        'TED-Ed' => 'UCsooa4yRKGN_zEE8iknghZA',
        'SmarterEveryDay' => 'UC6107grRI4m0o2-emgoDnAA',
        'KurzgesagtInANutshell' => 'UCsXVk37bltHxD1rDPwtNM8Q',
        'SciShow' => 'UCZYTClx2T1of7BRZ86-8fow',
        'Veritasium' => 'UCHnyfMqiRRG1u-2MsSQLbXA',
        'minutephysics' => 'UCUHW94eEFW7hkUMVaZz4eDg',
        'Vsauce2' => 'UCqmugCqELzhIMNYnsjScXXw',
        'Vsauce3' => 'UCwmFOfFuvRPI112vR5DNnrA',
        'TheSchoolOfLife' => 'UC7IcJI8PUf5Z3zKxnZvTBog',
        'CGPGrey' => 'UC2C_jShtL725hvbm1arSV9w',
        'Vox' => 'UCLXo7UDZvByw2ixzpQCufnA',
        'Numberphile' => 'UCoxcjq-8xIDTYp3uz647V5A',
        'LearnEnglishWithLetsTalk' => 'UCicjynhfFw2LiIQFnoS1JTw',
        'BigThink' => 'UCvQECJukTDE2i6aCoMnS-Vg',
        'MinuteEarth' => 'UCeiYXex_fwgYDonaTcSIk6w',
        'NowThisWorld' => 'UCgRvm1yLFoaQKhmaTqXk9SA',
        'WOWENGLISHTV' => 'UCx1xhxQyzR4TT6PmXO0khbQ',
        'TomScott' => 'UCBa659QWEk1AI4Tg--mrJ2A',
        'PeriodicVideos' => 'UCtESv1e7ntJaLJYKIO1FoYw',
        'Computerphile' => 'UC9-y-6csu5WGm29I7JiwpnA',
        'SciShowSpace' => 'UCrMePiHCWG4Vwqv3t7W9EFg',
        'BBCLearningEnglish' => 'UCHaHD477h-FeBbVh9Sh7syA',
        'TopTenz' => 'UCQ-hpFPF4nOKoKPEAZM_THw',
        'LearnEnglishWithEnglishClass101' => 'UCeTVoczn9NOZA9blls3YgUg',
        'TalksAtGoogle' => 'UCbmNph6atAoGfqLoCL_duAg',
        'SecularTalk' => 'UCldfgbzNILYZA4dmDt4Cd6A',
        'vagabrothers' => 'UCa1WbVCkTqd5ecG6G2adIow',
        '3Blue1Brown' => 'UCYO_jab_esuFRV4b17AJtAw',
        'DeepLook' => 'UC-3SbfTPJsL8fJAPKiVqBLg',
        'BrainCraft' => 'UCt_t6FwNsqr3WWoL6dFqG9w',
        'OxfordOnlineEnglish' => 'UCNbeSPp8RYKmHUliYBUDizg',
        'HowToAdult' => 'UCFqaprvZ2K5JOULCvr18NTQ',
        'Reactions' => 'UCdJ9oJ2GUF8Vmb-G63ldGWg',
        'VivaLaDirtLeague' => 'UCchBatdUMZoMfJ3rIzgV84g',
        'BazBattles' => 'UCx-dJoP9hFCBloY9qodykvw',
        'acapellascience' => 'UCTev4RNBiu6lqtx8z1e87fQ',
        'PaulGaleComedy' => 'UCr2dyt3W7U-erQmDazCbfcQ',
        'LearnEnglishWithJulia' => 'UClij8Hk2hGAHLFqNmSEZ-yA',
        'EnglishWithKristina' => 'UChqe8_qxbGA0z4t1wgLNWbg'
    ];

    public function __construct(
        VideoManager $videoManager,
        EntityManager $em,
        $appDirectory
    ) {
        parent::__construct();
        $this->webDirectory = $appDirectory;
        $this->videoManager = $videoManager;
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setName('game:update')
            ->setDescription('youtube parser for new game content')
            ->addArgument(
                'channel',
                InputArgument::OPTIONAL
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("\n<--start: ".date('Y-m-d H:i'));
        $channelKey = $input->getArgument('channel');
        $channelId = null;
        $i = 1;

        if (!$channelKey) {
            $channelKey = array_rand($this->channels);
        }

        $output->writeln("Use channel: $channelKey");
        $channelId = $this->channels[$channelKey];

        require_once $this->webDirectory . '/../libs/google-api-php-client/vendor/autoload.php';
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->webDirectory . '/../app/config/MySkillsPro-0a01cc8f1631.json');

        do {
            $output->writeln("Iteration #: $i");
            $client = new \Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(\Google_Service_YouTube::YOUTUBE_FORCE_SSL);
            $client->setDeveloperKey('AIzaSyAxDigNJ5XQOXK1QqnFgsBriqs1LkFZqzY');
            $youtube = new \Google_Service_YouTube($client);
            $searchResponse = [];

            try {
                $searchResponse = $youtube->search->listSearch('id,snippet', array(
                    'channelId' => $channelId,
                    'maxResults' => 50,
                    'order' => 'date',
                    'pageToken' => $this->pageToken ?: null,
                    'type' => 'video'
                ));
            } catch (\Google_Service_Exception $e) {
                $output->writeln('<error>A service error occurred: <code>%s</code></error>',
                    htmlspecialchars($e->getMessage()));
            } catch (\Google_Exception $e) {
                $output->writeln(sprintf('<error>An client error occurred: <code>%s</code></error>',
                    htmlspecialchars($e->getMessage())));
            }

            if (empty($searchResponse['items'])) {
                $output->writeln("there are no results");
                $output->writeln("\n<--finish: " . date('Y-m-d H:i'));
                die;
            }
            $this->pageToken = $searchResponse['nextPageToken'];

            foreach ($searchResponse['items'] as $item) {
                $videoId = $item['id']['videoId'];

                if (empty($videoId)) {
                    continue;
                }

                $output->writeln("Get video $videoId from youtube");

                $videoDescription = $item['snippet']['description'];
                $videoTitle = $item['snippet']['title'];
                $thumb = empty($item['snippet']['thumbnails']['high']['url']) ? $item['snippet']['thumbnails']['medium']['url'] : $item['snippet']['thumbnails']['high']['url'];
                $publishedAt = new \DateTime($item['snippet']['publishedAt']);
                $video = $this->videoManager->getByYoutubeId($videoId);

                if (null !== $video) {
                    $video->setChannelCode($channelKey);
                    $this->videoManager->saveVideo($video);

                    $output->writeln("The video $videoId is already in db");
                    continue;
                }

                $enCaption = $this->hasEnCaption($youtube, $videoId);

                if (!$enCaption) {
                    $output->writeln("No english subtitles for video $videoId");
                    continue;
                }

                $video = new Video();
                $video->setYoutubeId($videoId);
                $video->setDescription($videoDescription);
                $video->setTitle($videoTitle);
                $video->setThumb($thumb);
                $video->setTimePublish($publishedAt);
                $video->setChannelCode($channelKey);

                $this->videoManager->saveVideo($video);
                /** @var Video $video */
                $video = $this->videoManager->getByYoutubeId($videoId);

                try {
                    $this->downloadCaption($video, $videoId);
                } catch (\RuntimeException $e) {
                    $output->writeln("<error>{$e->getMessage()}</error>");
                }

                $output->writeln("<info>Added new video: $videoId</info>");
            }

            $connection = $this->em->getConnection();

            $output->writeln("reconnect to mysql...");
            $connection->close();
            $connection->connect();

            $this->checkEMConnection($this->em, $connection);
            $i++;

            if (empty($searchResponse['nextPageToken'])) {
                $output->writeln("empty nextPageToken!");
                break;
            }
            if ($i > 100) {
                $output->writeln("Iteration limit!");
                break;
            }

        } while(true);
    }

    private function hasEnCaption(\Google_Service_YouTube $youtube, $videoId) {
        // Call the YouTube Data API's captions.list method to retrieve video caption tracks.
        $captionsQuery = $youtube->captions->listCaptions("snippet", $videoId);

        if (empty($captionsQuery['items'])) {
            return false;
        }

        foreach ($captionsQuery['items'] as $item) {
            if ($item['snippet']['language'] == 'en') {
                $exists = !empty(file_get_contents('http://video.google.com/timedtext?lang=en&v='.$videoId));
                return $exists;
            }
        }

        return false;
    }

    function downloadCaption(Video $video, $videoId) {
        $xmlParse = new \SimpleXMLElement('http://video.google.com/timedtext?lang=en&v='.$videoId, 0, true);
        $i = 1;
        $vttContent = "WEBVTT".PHP_EOL;
        foreach ($xmlParse->text as $element) {
            $vttContent .= PHP_EOL.$i.PHP_EOL;
            if (!$element->attributes()['start'] || !$element->attributes()['dur']) {
                throw new \RuntimeException("Incorrect start or duration values");
            }
            $start = $element->attributes()['start']->__toString();
            $duration = $element->attributes()['dur']->__toString();
            $finish = $start + $duration;
            $text = $element->__toString();

            $vttContent .= gmdate("H:i:s", $start) . (strpos($start, '.') !== false ? substr($start, strpos($start, '.')) : '.00') . " --> " . gmdate("H:i:s", $finish) . (strpos($finish, '.') !==  false ? substr($finish, strpos($finish, '.')) : '.00') . PHP_EOL;
            $vttContent .= $this->convertSpecialSymbols($text) . PHP_EOL;

            $i++;
        }
        $this->parseVtt($vttContent, $video);
    }

    /**
     * @param $vttContent
     * @param Video $video
     */
    public function parseVtt($vttContent, Video $video) {
        $freeVtt = new VttSubtitle();

        $free_target = mb_substr(md5(STATIC_SERVER . time()), 0, 6) . '_t_' . mb_substr(md5($video->getYoutubeId() . time()), 0, 6) . '.vtt';
        @mkdir(VttSubtitle::$SERVER_PATH_TO_IMAGE_FOLDER . '/' . VttSubtitle::generatePath($free_target), 0777, true);

        $freeVtt->setFilename($free_target);
        $free_target = $freeVtt->getFilePath();
        file_put_contents($free_target, $vttContent);

        $freeVtt->calculateHash();
        $video->setVtt($freeVtt);

        $this->em->flush();
    }

    private function convertSpecialSymbols($text) {
        return str_replace(
            ['&quot;'],
            '',
            htmlspecialchars_decode(html_entity_decode(preg_replace_callback("/(&#[0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); }, $text), ENT_QUOTES | ENT_HTML5, "UTF-8"))
        );
    }

    public static function delTree($dir) {
        if(!file_exists($dir)) return true;
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * method checks connection and reconnect if needed
     * MySQL Server has gone away
     *
     * @param $em
     * @param $connection
     * @throws \Doctrine\ORM\ORMException
     */
    protected function checkEMConnection(&$em, $connection)
    {
        if (!$em->isOpen()) {
            $config = $em->getConfiguration();

            $em = $em->create(
                $connection, $config
            );
        }
    }
}
