<?php
namespace MyskillsBundle\Command;
use MyskillsBundle\DomainManager\VideoClip\VideoClipManager;
use MyskillsBundle\DomainManager\Video\VideoManager;
use MyskillsBundle\Entity\Video;
use MyskillsBundle\Entity\VideoClip;
use MyskillsBundle\Service\OroroService;
use MyskillsBundle\Service\VideoService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
set_time_limit(0);
date_default_timezone_set('Europe/Moscow');
class VideoCutCommand extends Command
{
    const SUBTITLE_TIME_SLIDE_LIMIT_IN_SECONDS = 20;
    /** @var VideoManager */
    private $videoManager;
    /** @var VideoClipManager */
    private $videoClipManager;
    private $webDirectory;
    /** @var VideoService */
    private $videoService;
    /**
     * @var EntityManager
     */
    protected $em;
    public function __construct(
        VideoManager $videoManager,
        VideoClipManager $videoClipManager,
        $appDirectory,
        VideoService $videoService,
        EntityManager $em
    ) {
        parent::__construct();
        $this->videoManager = $videoManager;
        $this->videoClipManager = $videoClipManager;
        $this->webDirectory = $appDirectory;
        $this->videoService = $videoService;
        $this->em = $em;
    }
    protected function configure()
    {
        $this->setName('video:cut')->setDescription('generator of video clips')->addArgument(
            'id_video',
            InputArgument::OPTIONAL
        );
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $idVideo = (int)$input->getArgument('id_video');
        if (!$idVideo) {
            $output->writeln("check running crons");
            exec("ps aux | grep video", $results);
            $i = 0;
            foreach ($results AS $line) {
                if (strpos($line, "video:cut") !== false) {
                    $i++;
                }
            };
            if ($i > 5) {
                $output->writeln("the video:cut is already running...");
                exit;
            }
        }
        if ($idVideo) {
            /** @var Video $video */
            $video = $this->videoManager->getById($idVideo);
        } else {
            /** @var Video $video */
            $video = $this->videoManager->getReadyForCutting();
        }
        if ($video === null) {
            $output->writeln("--- there is no video to cut ---");
            die;
        }
        $idVideo = $video->getId();
        $vtt = $video->getVtt();
        if (null === $vtt) {
            $video->setCutType(3);
            $this->em->flush();
            $output->writeln("--- there is no vtt file for video with id $idVideo ---");
            die;
        }
        $output->writeln("<--start video cutting for $idVideo at ".date('Y-m-d H:i'));
        $output->writeln("1) get video from db...");
        $output->writeln("2) check video cutting flag...");
        if ($video->getCutType() > 1) {
            $output->writeln("--- the video has already cut or in cutting ---");
            die;
        }
        $video->setCutType(2);
        $this->em->flush();
        $output->writeln("3) cut video subtitles...");
        try {
            $this->cutVideoSubtitles($video, $output);
        } catch (\Exception $e) {
            $output->writeln("<error>!!!SERIOUS PROBLEM!!!</error>");
            var_dump($e->getFile());
            var_dump($e->getLine());
            var_dump($e->getMessage());
            $video->setCutType(1);
            $this->em->flush();
        }
        $output->writeln("<--finished at ".date('Y-m-d H:i'));
    }
    private function cutVideoSubtitles(Video $video, $output)
    {
        $subtitleRuArr = [];
        $subtitlesRu = [];
        //
        //        if (isset($video->getVttData()['ru'])) {
        //            $subtitleRuArr = explode("\n", file_get_contents($video->getVttData()['ru']));
        //        }
        $subtitleArr = explode(PHP_EOL, file_get_contents($video->getVtt()->getFilePath()));
        $subtitles = array();
        $patches = array();
        $videoFrame = null;
        foreach ($subtitleArr as $i => $subIteration) {
            if (strpos($subIteration, '-->') !== false) {
                $part1 = !empty($subtitleArr[$i + 1]) ? $subtitleArr[$i + 1] : '';
                $part2 = !empty($subtitleArr[$i + 2]) ? $subtitleArr[$i + 2] : '';
                $subtitles[trim($subIteration)] = trim($part1."\n".$part2);
            }
        }
        if (!empty($subtitleRuArr)) {
            foreach ($subtitleRuArr as $i => $subIteration) {
                if (strpos($subIteration, '-->') !== false) {
                    $part1 = !empty($subtitleRuArr[$i + 1]) ? $subtitleRuArr[$i + 1] : '';
                    $part2 = !empty($subtitleRuArr[$i + 2]) ? $subtitleRuArr[$i + 2] : '';
                    $subtitlesRu[trim($subIteration)] = trim($part1."\n".$part2);
                }
            }
        }
        // patches with subtitles divided by SUBTITLE_TIME_SLIDE_LIMIT_IN_SECONDS
        $sumDelta = 0;
        $patch = array();
        $startVideoTime = strtotime('00:00:00');
        foreach ($subtitles as $time => $subtitle) {
            $periods = explode(" --> ", $time);
            $periods = array_map(
                function ($t) {
                    return strtotime($t);
                },
                $periods
            );
            $delta = $periods[1] - $periods[0];
            $sumDelta += $delta;
            $patch[$time] = $subtitle;
            if ($sumDelta >= self::SUBTITLE_TIME_SLIDE_LIMIT_IN_SECONDS) {
                $patches[] = $patch;
                $patch = array();
                $sumDelta = 0;
                continue;
            }
        }
        $subtitleArr = null;
        $subtitles = null;
        $subtitleRuArr = null;
        if (empty($patches)) {
            return;
        }
        $totalPatches = count($patches);
        $output->writeln("total patches: ".$totalPatches);
        // create subTimes
        foreach ($patches as $num => $patch) {
            $subText = "";
            $i = 1;
            foreach ($patch as $time => $p) {
                $subText .= $p."\n";
                $i++;
            }
            $periods = array_keys($patch);
            $start = explode(' --> ', $periods[0]);
            $start = strtotime($start[0]) - $startVideoTime;
            $finish = explode(' --> ', $periods[count($periods) - 1]);
            $finish = strtotime($finish[1]) - $startVideoTime;
            $hashClip = $video->getId().'__'.$num.'_'.md5($start.'_'.$finish);
            $videoClip = $this->videoClipManager->getByHash($hashClip, false);
            if ($videoClip === null) {
                $videoClip = $this->videoClipManager->createClip(
                    $video,
                    $start,
                    $finish,
                    $video->getYoutubeId(),
                    null,
                    strip_tags($subText),
                    $subText,
                    $video->getThumb(),
                    $hashClip
                );
                $this->em->flush();
                $output->writeln($hashClip." was created");
            } else {
                $output->writeln($hashClip." already exists");
            }
            $readyForGame = false;
            foreach ($patch as $time => $p) {
                if (empty($p)) {
                    continue;
                }
                $periods = explode(' --> ', $time);
                $start = strtotime($periods[0]) - $startVideoTime;
                $finish = strtotime($periods[1]) - $startVideoTime + 1;
                $prefix = md5(strip_tags($p).SALT).'_';
                $hashClip = $video->getId().'__'.$num.'_short_'.md5($prefix.$start.'_'.$finish);
                $vClip = $this->videoClipManager->getByHash($hashClip, false);
                $videoClipExists = null !== $vClip;
                if (!$videoClipExists) {
                    $output->writeln($hashClip." was created");
                    $vClip = $this->videoClipManager->createClip(
                        $video,
                        $start,
                        $finish,
                        $video->getYoutubeId(),
                        $videoClip,
                        strip_tags($p),
                        $subText,
                        $video->getThumb(),
                        $hashClip,
                        !empty($subtitlesRu[$time]) ? trim($subtitlesRu[$time]) : null,
                        $time
                    );
                    $readyForGame = $vClip->isReadyForGame();
                } else {
                    $output->writeln($hashClip." already exists");
                }
            }
            if ($videoClip !== null) {
                $videoClip->setReadyForGame($readyForGame);
                $this->em->flush();
            }
        }
        $video->setCutType(3);
        $connection = $this->em->getConnection();
        $output->writeln("reconnect to mysql...");
        $connection->close();
        $connection->connect();
        $this->checkEMConnection($this->em, $connection);
        $this->em->flush();
        $this->em->clear();
        $output->writeln("<--finish video cutting for {$video->getId()} at ".date('Y-m-d H:i'));
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
                $connection,
                $config
            );
        }
    }
}