<?php
namespace MyskillsBundle\Command;

use Doctrine\ORM\EntityManager;
use MyskillsBundle\DomainManager\VideoClip\VideoClipManager;

use MyskillsBundle\Entity\Series;
use MyskillsBundle\Entity\Video;
use MyskillsBundle\Entity\VideoClip;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

set_time_limit(0);
date_default_timezone_set('Europe/Moscow');

// Команда для проверки наличия видео-файлов на серверах
class VideoCheckCommand extends Command {
    private $videoClipManager;
    private $webDirectory;

    /**
     * @var EntityManager
     *
     */
    protected $em;

    public function __construct(
        VideoClipManager $videoClipManager,
        EntityManager $em,
        $webDirectory
    ) {
        parent::__construct();
        $this->webDirectory = $webDirectory;
        $this->videoClipManager = $videoClipManager;
        $this->em = $em;
    }

    protected function configure()
    {
        $this
            ->setName('video:check')
            ->setDescription('video checker for video files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<--started at: ".date('Y-m-d H:i'));

        $output->writeln("check running crons");

        exec("ps aux | grep 'static:relocate'", $results);
        $i = 0;
        foreach ($results AS $line) {
            if(strpos($line, "video:check") !== false) {
                $i++;
            }
        };
        if($i > 4) {
            $output->writeln("the video:check is already running...");
            exit;
        }

        $videoList = $this->videoClipManager->getVideoFileUrlsArr();
        $okCount = 0;
        $failCount = 0;
        $imageFail = 0;
        $imageOk = 0;
        $newThumb = 0;

        if(empty($videoList)) {
            $output->writeln("<info>Nothing to check....</info>");
            $output->writeln("\n<--finish at: ".date('Y-m-d H:i'));
            die;
        }

        foreach($videoList as $videoArr) {
            $video = new VideoClip();
            $series = new Series();
            $series->setCode($videoArr['code']);
            $video->setSeries($series);
            $videoOrigin = null;
            $keys = array_keys($videoArr);
            foreach($keys as $key) {
                if($key == 'code') {
                    continue;
                }
                $video->{'set' . ucfirst($key)}($videoArr[$key]);
            }

            $withNoHardSubLink = false;

            do {

                $videoUrl = $video->getVideoLink($withNoHardSubLink);
                $thumb = $video->getThumb();
                $videoExists = false;
                $thumbExists = false;

                if ($video->getYandexDiskNumber()) {
                    $thumbExists = $this->videoClipManager->isYandexThumbExists($video);
                    $videoExists = $this->videoClipManager->isYandexVideoExists($video, $withNoHardSubLink);
                }

                if ($videoExists || $this->urlExists($videoUrl)) {
                    $output->writeln($video->getId() . " : $videoUrl" . ($videoExists ? " (YANDEX)" : "") . " <info>VIDEO OK</info>");
                    $okCount++;
                    $videoDirectory = $this->webDirectory . str_replace($video->getStaticServer(), '', $videoUrl);

                    if (file_exists($videoDirectory)) {
                        @unlink($videoDirectory);
                    }
                } else {

                    if ($video->getYandexDiskNumber()) {
                        $videoExists = $this->videoClipManager->isYandexVideoExists($video, $withNoHardSubLink);
                    }

                    if (!$videoExists || !$this->urlExists($videoUrl)) {
                        $output->writeln($video->getId() . " : $videoUrl <error>VIDEO FAIL</error>");
                        $failCount++;

                        /**
                         * @var VideoClip $videoClipOrigin
                         */
                        $videoClipOrigin = $this->videoClipManager->getById($video->getId());
                        $videoClipOrigin->setIsPublic(false);
                        $videoClipOrigin->setStaticServer(STATIC_SERVER);
                        $videoClipOrigin->setYandexDiskNumber(null);

                        $videoOrigin = $videoClipOrigin->getVideoOrigin();
                        $videoOrigin->setCutType(1);

                        $this->reConnect();
                    }

                }

                if (!empty($thumb) && !$thumbExists && !$this->urlExists($thumb)) {
                    $imageFail++;
                    $output->writeln($video->getId() . ": " . $video->getThumb() . " <error>IMAGE FAIL</error>");

                    $videoOrigin = $this->videoClipManager->getById($video->getId());
                    $videoOrigin->setThumb(null);
                    $this->reConnect();
                } elseif (!empty($thumb)) {
                    $imageOk++;

                    $posterPath = $this->webDirectory . str_replace($video->getStaticServer(), '', $thumb);
                    if (file_exists($posterPath)) {
                        @unlink($posterPath);
                    }
                }

                $thumbIdeal = str_replace('.mp4', '.jpg', $videoUrl);
                if (!$video->getThumb() && ($thumbExists || $this->urlExists($thumbIdeal))) {
                    $thumbIdeal = str_replace($video->getStaticServer(), '', $thumbIdeal);
                    $videoOrigin = $this->videoClipManager->getById($video->getId());
                    $videoOrigin->setThumb($thumbIdeal);
                    $this->reConnect();
                    $newThumb++;
                    $output->writeln("<info>Set up correct thumb: $thumbIdeal</info>");
                }

                if(!$withNoHardSubLink) {
                    $withNoHardSubLink = !empty($video->getNoHardsubVideoUrl());
                } else {
                    $withNoHardSubLink = false;
                }
            } while($withNoHardSubLink);
        }

        $output->writeln("Video total: " . ($okCount + $failCount));
        $output->writeln("<info>Video ok: $okCount</info>");
        $output->writeln("<error>Video fail: $failCount</error>\n");

        $output->writeln("Thumb total: " . ($imageOk + $imageFail + $newThumb));
        $output->writeln("<info>Image ok: $imageOk</info>");
        $output->writeln("<info>New images: $newThumb</info>");
        $output->writeln("<error>Image fail: $imageFail</error>");

        $output->writeln("\n<--finish at: ".date('Y-m-d H:i'));
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

    private function reConnect() {
        $connection = $this->em->getConnection();

        $connection->close();
        $connection->connect();

        $this->checkEMConnection($this->em, $connection);

        $this->em->flush();
    }

    private function urlExists($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        $is404 = curl_getinfo($ch, CURLINFO_HTTP_CODE) == 404;
        curl_close($ch);
        return !$is404;
    }
}
