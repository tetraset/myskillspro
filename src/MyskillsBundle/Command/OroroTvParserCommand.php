<?php
namespace MyskillsBundle\Command;

use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\Common\Collections\ArrayCollection;
use MyskillsBundle\DomainManager\Series\SeriesManager;
use MyskillsBundle\DomainManager\Video\VideoManager;
use MyskillsBundle\Entity\Country;
use MyskillsBundle\Entity\Genre;
use MyskillsBundle\Entity\Series;
use MyskillsBundle\Entity\Video;
use MyskillsBundle\Entity\VttSubtitle;
use MyskillsBundle\Service\OroroService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;

set_time_limit(0);
date_default_timezone_set('Europe/Moscow');
ini_set('memory_limit', '-1');

class OroroTvParserCommand extends ParserCommand
{
    const INDEX_PAGE_SERIES = 'https://ororo.tv/en';
    const INDEX_PAGE_MOVIE = 'https://ororo.tv/en/movies';
    const DOMAIN = 'https://ororo.tv';
    const THREADS_COUNT = 8;
    const SERIES_DELAY_IN_MIN = 1;

    /**
     * @var EntityManager
     */
    protected $em;

    /** @var SeriesManager */
    private $seriesManager;

    /** @var VideoManager */
    private $videoManager;

    private $webDirectory;

    public function __construct(
        EntityManager $em,
        SeriesManager $seriesManager,
        VideoManager $videoManager,
        $appDirectory
    ) {
        parent::__construct();
        $this->em = $em;
        $this->seriesManager = $seriesManager;
        $this->videoManager = $videoManager;
        $this->webDirectory = $appDirectory;
    }

    protected function configure()
    {
        $this->setName('parser:ororo')->setDescription('parser for ororo.tv media content')
            ->addArgument('type', InputArgument::OPTIONAL)
            ->addArgument('url', InputArgument::OPTIONAL)
             ->addArgument('lastupdated', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("check running crons");
        $type = $input->getArgument('type');
        $url = $input->getArgument('url');
        $lastupdated = $input->getArgument('lastupdated');
        if(!$type) {
            $type = 'series';
        }

        $output->writeln("\n<--start $type: " . date('Y-m-d H:i'));

        do {

            if($url) {
                $indexPage = null;
            } else {
                $indexPage = $this->parse($type == 'series' ? self::INDEX_PAGE_SERIES : self::INDEX_PAGE_MOVIE, $output, ['cookie'=>OroroService::COOKIE], OroroService::$USER_IP, OroroService::$USER_BROWSER);
            }

            if($type == 'series') {
                $result = $this->parseContent($indexPage, $output, $url, $lastupdated);
            } else {
                $result = $this->parseContent($indexPage, $output, $url, $lastupdated, 'movie');
            }
        } while(!$result);

        $output->writeln("\n<--finish: " . date('Y-m-d H:i'));
    }

    public function parseContent($html, OutputInterface $output, $url = null, $lastupdated = null, $type = 'series')
    {

        // получение всех урлов для парса
        if(!$url) {
            /**
             * complexity - сложность 90.3056127625056
             * lastupdated - последнее обнволение 2016-12-05 09:08:11 +0300
             * newest - дата добавления 2016-10-03 14:05:51 +0300
             * popularity - популярность 5311
             */

            // 1. Получаем все урлы с главной страницы с кое-какими данными
            preg_match_all('/data-complexity=\'(?P<complexity>[^\']+)\'.+data-lastupdated=\'(?P<lastupdated>[^\']+)?\' data-newest=\'(?P<newest>[^\']+)?\' data-popularity=\'(?P<popularity>[^\']+)?\'.+<a class="name" href="(?P<url>[^\"]+)">(?P<title>[^<]+)<\/a>/isUm',
                $html, $matches);

            if(empty($matches['url'])) {
                return null;
            }

            $output->writeln("urls count: " . count($matches['url']));

            // 2. Разбираем урлы
            foreach($matches['url'] as $i => $url) {
                $urlString = str_replace('/', '_', $url);
                $enTitle = htmlspecialchars_decode(trim($matches['title'][$i]));
                $code = trim(substr($url, strrpos($url, '/') + 1));

                $series = $this->seriesManager->getSeriesByCode($code);
                if(null === $series) {
                    $series = new Series();
                }
                $series->setCode($code);

                // 3. Обновлялся ли сериал
                $oroDateUpdatedStr = trim($matches['lastupdated'][$i]);
                $oroDateUpdated = new \DateTime($oroDateUpdatedStr);

                if($series->getOroLastUpdated() && $series->getOroLastUpdated() == $oroDateUpdated) {
                    // сериал не обновлялся
                    $output->writeln("The $type: " . $enTitle . " is not updated");
                    continue;
                } elseif(empty($oroDateUpdatedStr) && $series->getOroLastAdded()) {
                    $output->writeln("The $type: " . $enTitle . " is not updated");
                    continue;
                }

                // 4. Устанавливаем данные с главной страницы
                $oroDateAdded = new \DateTime(trim($matches['newest'][$i]));
                $series->setComplexity(doubleval(trim($matches['complexity'][$i])));
                $series->setOroLastAdded($oroDateAdded ?: new \DateTime());
                $series->setOroLastUpdated($oroDateUpdated);

                $series->setPopularity((int) trim($matches['popularity'][$i]));
                $series->setEnTitle($enTitle);
                $series->setType($type);

                $this->em->persist($series);

                $connection = $this->em->getConnection();

                $output->writeln("reconnect to mysql...");
                $connection->close();
                $connection->connect();

                $this->checkEMConnection($this->em, $connection);

                $output->writeln("Parse $url started at: " . date('Y-m-d H:i'));

                $this->em->flush();
                $this->em->clear();
                $series = null;

                // local version
                // $php = '/Applications/MAMP/bin/php/php5.6.10/bin/php';
                // remote version
                $php = '/usr/bin/php';
                exec("$php " . $this->webDirectory . "/../app/console parser:ororo $type '" . $url . "' '" . trim($matches['lastupdated'][$i]) . "' --env=prod > " . $this->webDirectory .
                     "/../app/logs/parse_ororo_$urlString.log &");

                do {
                    sleep(60 * (int) self::SERIES_DELAY_IN_MIN);
                    $results = [];
                    exec("ps aux | grep 'parser:ororo $type'", $results);

                    $i = 0;
                    foreach($results AS $line) {
                        if(strpos($line, $type) !== false) {
                            $i++;
                        }
                    };
                    $output->writeln("threads, now: $i....");
                    if($i <= self::THREADS_COUNT) {
                        break;
                    }
                    $output->writeln("Waiting vacant threads, now: $i....");
                } while(true);
            }
            $output->writeln("\n<--finish: " . date('Y-m-d H:i'));
            die;
        }

        exec("ps aux | grep '$url'", $results);

        $i = 0;
        foreach ($results AS $line) {
            if (strpos($line, $url) !== false) {
                $i++;
            }
        };
        if ($i > 4) {
            $output->writeln("the parser:ororo series '$url' is already running...");
            exit;
        }

        $url = self::DOMAIN . $url;
        $code = trim(substr($url, strrpos($url, '/') + 1));

        $series = $this->seriesManager->getSeriesByCode($code);
        $seriesPage = null;

        // 5. Получаем html страницы сериала
        do {
            try {
                $seriesPage = $this->parse($url, $output, ['cookie'=>OroroService::COOKIE], OroroService::$USER_IP, OroroService::$USER_BROWSER);
            } catch (\RuntimeException $e) {
                $output->writeln("<error>!!!PROBLEM!!!</error>");
                var_dump($e->getFile());
                var_dump($e->getLine());
                var_dump($e->getMessage());
                $output->writeln("<error>Let's again</error>");

                OroroService::$USER_IP = self::$PROXY_IPS[rand(0, count(self::$PROXY_IPS)-1)];
                OroroService::$USER_BROWSER = $this->random_uagent();

                continue;
            } catch(\Symfony\Component\Debug\Exception\ContextErrorException $e) {
                $output->writeln("<error>!!!SERIOUS PROBLEM!!!</error>");
                var_dump($e->getFile());
                var_dump($e->getLine());
                var_dump($e->getMessage());
                $output->writeln("<error>Let's again</error>");

                OroroService::$USER_IP = self::$PROXY_IPS[rand(0, count(self::$PROXY_IPS)-1)];
                OroroService::$USER_BROWSER = $this->random_uagent();

                continue;
            }
        } while(empty($seriesPage));

        // 6. Парсим данные со страницы
        $contentRegexp = '/<meta name="csrf-token" content="(?P<token>[^\"]+)" \/>.+(<img.+id="poster".+src="(?P<poster>[^\"]+)" \/>.+)?(data-trailer=\'(?P<trailerId>[^\']+)\'>.+)?(<span class=\'field-name\'>Rating:<\/span>(?P<rating>[^<]+)<\/div>.+)?(<div id=\'year\'.+<\/span>(?P<year>[^<+]+)<\/div>.+)?(<div id=\'genres\'>.+<\/span>(?P<genres>[^<]+)<\/div>.+)?(<div id=\'countries\'>.+<\/span>(?P<country>[^<]+)<\/div>.+)?(<div id=\'length\'>.+<\/span>(?P<time>[^<]+)<\/div>.+)?<div class=\'show-content__title\'>(?P<title>[^<]+)<\/div>.+<div class=\'show-content__description\'>(?P<description>[^<]+)<\/div>/iUms';

        preg_match($contentRegexp, $seriesPage, $sMatches);

        if(empty($sMatches['token'])) {
            $output->writeln("<error>Fail getting token for series: $url</error>");
            return false;
        }

        if(!empty($sMatches['genres']) && $series->getGenres()->isEmpty()) {
            $genreArr = new ArrayCollection();
            $genres = array_map('strtolower', array_map('trim', explode(',', trim($sMatches['genres']))));
            $genres = array_unique($genres);

            foreach($genres as $genre) {
                $newGenre = $this->seriesManager->getEnGenre($genre);
                if(null === $newGenre) {
                    $newGenre = new Genre();
                    $newGenre->setEnTitle($genre);
                }
                $genreArr->add($newGenre);
            }
            $series->setGenres($genreArr);
        }

        if(!empty($sMatches['country']) && $series->getCountries()->isEmpty()) {
            $countryArr = new ArrayCollection();
            $countries = array_map('strtolower', array_map('trim', explode(',', trim($sMatches['country']))));
            $countries = array_unique($countries);

            foreach($countries as $country) {
                $newCountry = $this->seriesManager->getEnCountry($country);
                if(null === $newCountry) {
                    $newCountry = new Country();
                    $newCountry->setEnTitle($country);
                }
                $countryArr->add($newCountry);
            }
            $series->setCountries($countryArr);
        }

        $title = trim($sMatches['title']);
        // ToDo: парсить отдельно русскую версию ororo с заполнением ruTitle
        //        $ruTitle = trim(str_replace([$series->getEnTitle(), '(', ')'], '', $title));
        //        if($ruTitle) {
        //            $series->setRuTitle($ruTitle);
        //        }

        if(!$series->getPosterUrl()) {
            $posterFile = $this->webDirectory . '/uploads/poster/' . $code . '.jpg';

            if(!file_exists($posterFile)) {
                $output->writeln("download poster");
                file_put_contents($posterFile, file_get_contents(trim($sMatches['poster'])));
            }
            $series->setPosterUrl( str_replace($this->webDirectory, '', $posterFile) );
        }

        if(!empty($sMatches['trailerId'])) {
            $series->setTrailerId(trim($sMatches['trailerId']));
        }
        if(!empty($sMatches['rating'])) {
            $series->setRating(doubleval(trim($sMatches['rating'])));
        }
        $series->setStartYear((int) trim($sMatches['year']));
        $series->setDuration((int) trim($sMatches['time']));
        $series->setEnDescription(trim($sMatches['description']));
        $series->setEnTitle($title);

        if($type == 'series') {
            // 7. Парсим данные об эпизодах
            $regExpEpisodes = '/<a data-href="(?P<url>[^\"]+)" data-id="(?P<oroId>\d+)".+class="show-content__episode-link js-episode" href="#(?P<season>\d+)-(?P<episode>\d+)">(?P<title>[^<]+)<\/a>/iUms';
            $plotRegExp = '/<a.+data-id="(?P<oroId>\d+)".+<div class=\'episode-plot__text\'>(?P<plot>[^<]+)<\/div>/Uism';
        } else {
            // 7. Парсим данные о фильме
            $regExpEpisodes = '/<div class=\'show-content__title\'>(?P<title>[^<]+)<\/div>.+<a data-href="(?P<url>[^\"]+)" id="(?P<oroId>\d+)" class="js-episode" href="#video">/iUms';
            $plotRegExp = '/<div class=\'show-content__description\'>(?P<plot>[^<]+)<\/div>/Uism';
        }

        preg_match_all($plotRegExp, $seriesPage, $plotDataArr);
        preg_match_all($regExpEpisodes, $seriesPage, $episodeData);
        $plotData = [];

        if (!empty($plotDataArr['plot'])) {
            foreach ($plotDataArr['plot'] as $key => $plot) {
                if($type == 'series') {
                    $plotData[$plotDataArr['oroId'][$key]] = $plot;
                } else {
                    $plotData[$episodeData['oroId'][$key]] = $plot;
                }

            }
        }

        if(empty($episodeData['url'])) {
            $output->writeln("<error>No episodes for series: $url</error>");
            return false;
        }

        $this->em->persist($series);

        $connection = $this->em->getConnection();

        $output->writeln("reconnect to mysql...");
        $connection->close();
        $connection->connect();

        $this->checkEMConnection($this->em, $connection);

        $this->em->flush();
        $series = $this->seriesManager->getSeriesByCode($code);

        // 8. Обработка эпизодов
        foreach($episodeData['url'] as $episodeKey => $episodeUrl) {

            $plotDataStr = null;
            if(!empty($plotData[$episodeData['oroId'][$episodeKey]])) {
                $plotDataStr = $plotData[$episodeData['oroId'][$episodeKey]];
            }

            try {
                $this->parseEpisode($series, $episodeUrl, [
                        'oroId' => $episodeData['oroId'][$episodeKey],
                        'season' => $type == 'series' ? $episodeData['season'][$episodeKey] : 1,
                        'episode' => $type == 'series' ? $episodeData['episode'][$episodeKey] : 1,
                        'title' => $type == 'series' ? $episodeData['title'][$episodeKey] : $series->getEnTitle(),
                    ], $plotDataStr, [
                        'cookie' => OroroService::COOKIE,
                        'x-csrf-token' => $sMatches['token'],
                        'x-requested-with' => 'XMLHttpRequest',
                        'referer' => $url
                    ], $output);
            } catch (\RuntimeException $e) {
                $output->writeln("<error>!!!PROBLEM!!!</error>");
                var_dump($e->getFile());
                var_dump($e->getLine());
                var_dump($e->getMessage());
                $output->writeln("<error>Let's again</error>");
                return false;
            } catch(\Symfony\Component\Debug\Exception\ContextErrorException $e) {
                $output->writeln("<error>!!!SERIOUS PROBLEM!!!</error>");
                var_dump($e->getFile());
                var_dump($e->getLine());
                var_dump($e->getMessage());
                $output->writeln("<error>Let's again</error>");
                return false;
            }
        }

        $connection = $this->em->getConnection();

        $output->writeln("reconnect to mysql...");
        $connection->close();
        $connection->connect();

        $this->checkEMConnection($this->em, $connection);
        $this->em->flush();
        $output->writeln("<info>Activate series</info>");

        $series = $this->seriesManager->getSeriesByCode($code);

        if(!empty($lastupdated)) {
            $oroDateUpdated = new \DateTime($lastupdated);
            $series->setOroLastUpdated($oroDateUpdated);
        }
        $series->setIsPublic(true);

        $this->em->flush();

        return true;
    }

    public function parseEpisode(Series $series, $url, $contentData, $plotData, $headers, $output)
    {
        $oldEpisode = false;
        $url = self::DOMAIN . $url;

        $episode = $this->videoManager->getBySeriesIdEpisodeAndSeason($series, (int)$contentData['season'], (int)$contentData['episode']);

        if(null !== $episode) {
            $oldEpisode = true;
            $output->writeln("<info>The episode $url is already added</info>");
        } else {
            $episode = new Video();
        }

        $episode->setSeason($contentData['season']);
        $episode->setNumber($contentData['episode']);
        $episode->setTitle(trim($contentData['title']));
        $episode->setOroId($contentData['oroId']);
        $episode->setPlot($plotData);
        $episode->setIsPublic(true);

        if($oldEpisode) {

            $connection = $this->em->getConnection();

            $output->writeln("reconnect to mysql...");
            $connection->close();
            $connection->connect();

            $this->checkEMConnection($this->em, $connection);

            $this->em->flush();

            return true;
        }

        $episode->setStaticServer(STATIC_SERVER);
        $episode->setSeries($series);
        $series->addEpisode($episode);

        $this->em->persist($episode);

        $connection = $this->em->getConnection();

        $output->writeln("reconnect to mysql...");
        $connection->close();
        $connection->connect();

        $this->checkEMConnection($this->em, $connection);

        $this->em->flush();

        $output->writeln("next...");
    }
}