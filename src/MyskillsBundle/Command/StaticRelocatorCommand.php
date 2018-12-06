<?php
namespace MyskillsBundle\Command;

use Doctrine\ORM\EntityManager;

use MyskillsBundle\DomainManager\VideoClip\VideoClipManager;
use MyskillsBundle\Entity\Series;
use MyskillsBundle\Entity\Video;
use MyskillsBundle\Entity\VideoClip;
use MyskillsBundle\Service\YandexDiskService;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

set_time_limit(0);
date_default_timezone_set('Europe/Moscow');

// Команда для перемещения медиа на яндекс диск
class StaticRelocatorCommand extends Command {
    /** @var VideoClipManager */
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
            ->setName('static:relocate')
            ->setDescription('media relocator to static servers')
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = $input->getArgument('limit', 50);
        $output->writeln("<--started at: ".date('Y-m-d H:i'));

        $output->writeln("check running crons");

        exec("ps aux | grep 'static:relocate'", $results);
        $i = 0;
        foreach ($results AS $line) {
            if(strpos($line, "static:relocate") !== false) {
                $i++;
            }
        };
        if($i > 4) {
            $output->writeln("the static:relocate is already running...");
            exit;
        }

        $videoList = $this->videoClipManager->getByLocalStaticServer($limit);

        if(empty($videoList)) {
            $output->writeln("<info>Nothing to relocate....</info>");
            die;
        }

        $i = 0;
        /** @var VideoClip $video */
        foreach($videoList as $video) {
            $output->writeln("\n\nStarted relocation of video clip: " . $video->getId());
            $withNoHardSubLink = false;
            $toDeleteFiles = [];

            do {
                $videoDirectory = $this->webDirectory . str_replace(STATIC_SERVER, '', $video->getVideoLink($withNoHardSubLink));

                $series = $video->getSeries();
                $videoExists = $this->videoClipManager->isYandexVideoExists($video, $withNoHardSubLink);
                $thumbExists = $this->videoClipManager->isYandexThumbExists($video);

                if (!$videoExists && !file_exists($videoDirectory)) {
                    $output->writeln("<error>File is not found: $videoDirectory</error>");
                    $video->setYandexDiskNumber(null);
                    $this->reConnect();
                    continue;
                }

                if($videoExists) {
                    $output->writeln("<info>$videoDirectory is already relocated</info>");
                    $video->setYandexDiskNumber(1);
                    $result = true;
                } else {
                    $output->writeln("Relocating: " . $videoDirectory);

                    $result = $this->videoClipManager->uploadVideoToYandexDisk($video, $withNoHardSubLink);
                }

                $posterPath = null;

                if ($video->getThumb()) {
                    $posterPath = $this->webDirectory . str_replace(STATIC_SERVER, '', $video->getThumb());
                }

                if ($result === true) {
                    if(!$videoExists) {
                        $toDeleteFiles[] = $videoDirectory;
                    }

                    $output->writeln("<info>Relocated</info>: " . $videoDirectory);
                } elseif ($result === false) {
                    $output->writeln("<error>Not relocated</error>: " . $videoDirectory);

                    $toDeleteFiles[] = $videoDirectory;
                    $video->setYandexDiskNumber(null);
                    $this->reConnect();
                    continue 2;
                }

                if(!$withNoHardSubLink) {
                    $withNoHardSubLink = !empty($video->getNoHardsubVideoUrl());
                } else {
                    $withNoHardSubLink = false;
                }
            } while($withNoHardSubLink);

            if($posterPath) {
                $output->writeln("Relocating: " . $posterPath);

                if(!$thumbExists) {
                    $result = $this->videoClipManager->uploadThumbToYandexDisk($video);
                } else {
                    $result = true;
                }

                if($result === true) {
                    if(!$thumbExists) {
                        $toDeleteFiles[] = $posterPath;
                    }
                    $output->writeln("<info>Relocated</info>: " . $posterPath);
                } elseif ($result === false) {
                    $output->writeln("<error>Not relocated</error>: " . $posterPath);

                    $toDeleteFiles[] = $posterPath;
                    $video->setThumb(null);
                    $this->reConnect();
                    continue;
                }
            }

            $connection = $this->em->getConnection();

            $output->writeln("reconnect to mysql...");
            $connection->close();
            $connection->connect();

            $this->checkEMConnection($this->em, $connection);

            $this->em->flush();

            if(!empty($toDeleteFiles)) {
                $output->writeln("Delete files count: " . count($toDeleteFiles));
                foreach ($toDeleteFiles as $dFile) {
                    @unlink($dFile);
                }
            }

            $seriesDir = $this->webDirectory . '/uploads/video/clips/' . $series->getCode();
            if($this->isDirEmpty($seriesDir)) {
                $output->writeln("Delete directory: " . $seriesDir);
                self::delTree($seriesDir);
            }

            $i++;
        }

        $output->writeln("<info>Total relocated videos: $i</info>");

        $this->clearEmptyDirs();

        $output->writeln("\n<--finish at: ".date('Y-m-d H:i'));
    }

    private function clearEmptyDirs() {
        $dir = $this->webDirectory . '/uploads/video/clips';
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            if(!is_dir("$dir/$file")) {
                continue;
            }
            $dirFiles = array_diff(scandir("$dir/$file"), array('.','..'));

            foreach ($dirFiles as $f) {
                if(!is_dir("$dir/$file/$f")) {
                    continue;
                }
                $dirF2 = array_diff(scandir("$dir/$file/$f"), array('.','..'));

                if(empty($dirF2)) {
                    self::delTree("$dir/$file");
                }
            }
        }
    }

    public static function delTree($dir) {
        if(!file_exists($dir)) return true;
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : @unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    private function isDirEmpty($dir) {
        if (!is_readable($dir)) return false;
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                closedir($handle);
                return FALSE;
            }
        }
        closedir($handle);
        return TRUE;
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
}
