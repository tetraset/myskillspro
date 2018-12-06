<?php
namespace MyskillsBundle\Command;

use MyskillsBundle\DomainManager\Dictionary\DictionaryEnRuManager;
use MyskillsBundle\DomainManager\Series\SeriesManager;
use MyskillsBundle\DomainManager\VideoClip\VideoClipManager;
use MyskillsBundle\Entity\DictWordEn;

use MyskillsBundle\Entity\Series;
use MyskillsBundle\Entity\VideoClip;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

set_time_limit(0);
date_default_timezone_set('Europe/Moscow');

class SitemapCommand extends Command {
    private $dictionaryManager;
    private $seriesManager;
    private $webDirectory;
    private $videoClipManager;

    public function __construct(
        DictionaryEnRuManager $dictionaryManager,
        SeriesManager $seriesManager,
        VideoClipManager $videoClipManager,
        $appDirectory
    ) {
        parent::__construct();
        $this->dictionaryManager = $dictionaryManager;
        $this->seriesManager = $seriesManager;
        $this->webDirectory = $appDirectory;
        $this->videoClipManager = $videoClipManager;
    }

    protected function configure()
    {
        $this
            ->setName('generator:sitemap')
            ->setDescription('generator of sitemaps')
            ->addArgument(
                'source',
                InputArgument::REQUIRED
            )
            ->addArgument(
                'offset',
                InputArgument::OPTIONAL
            )
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $offset = empty($input->getArgument('offset')) ? 0 : $input->getArgument('offset');
        $limit = empty($input->getArgument('limit')) ? 100 : $input->getArgument('limit');
        $source = $input->getArgument('source');
        $appDirectory = $this->webDirectory;
        $xmlDirectory = $appDirectory . '/uploads/xml';

        $output->writeln("\n<--start: ".date('Y-m-d H:i'));

        // generate main xml
        if ( $source == 'sitemap' ) {
            $globalXml = "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";

            if ($handle = opendir($xmlDirectory)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != "." && $entry != "..") {
                        $globalXml .= "<sitemap><loc>";
                        $globalXml .= 'http://static.anisub.tv/uploads/xml/'.$entry;
                        $globalXml .= '</loc></sitemap>';
                    }
                }
                closedir($handle);
            }

            $globalXml .= '</sitemapindex>';
            file_put_contents($xmlDirectory . '/site.xml', $globalXml);
            $output->writeln('site.xml');
        }

        // clear xml dir
        if ( $source == 'update' ) {
            $output->writeln("--- clear ---");
            self::delTree($xmlDirectory);
            mkdir($xmlDirectory);
            exec("/usr/bin/php ".$appDirectory."/../app/console generator:sitemap series 0 1000 --env=prod --no-debug >> /var/www/anisub.tv/app/logs/iteration_sitemap_series_0_1000.log &");
            die;
        }

        // series
        if($source == 'series') {
            $output->writeln("\n<--start series: " . date('Y-m-d H:i'));

            $series = $this->seriesManager->findPublicAll($offset, $limit);

            $output->writeln(count($series));

            if (count($series)) {

                $xml = "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
                /** @var Series $s */
                foreach ($series as $s) {
                    $xml .= '<url><loc>';
                    $xml .= 'http://anisub.tv/' . $s->getSlug() . '/' . $s->getCode();
                    $xml .= '</loc></url>';
                }
                $xml .= "</urlset>";

                $fileName = "series_" . $offset . "_" . $limit . "_" . md5(rand(0, $offset)) . ".xml";
                $output->writeln($fileName);
                file_put_contents($xmlDirectory . '/' . $fileName, $xml);
                $offset += $limit;

                $output->writeln("--- iteration ---");
                exec("/usr/bin/php ".$appDirectory."/../app/console generator:sitemap series ".$offset." ".$limit." >> /var/www/anisub.tv/app/logs/iteration_sitemap_series_".$offset."_".$limit.".log &");
                die;

            } else {
                $output->writeln("\n<--finish series: " . date('Y-m-d H:i'));
                exec("/usr/bin/php ".$appDirectory."/../app/console generator:sitemap videoclips 0 1000 --env=prod --no-debug >> /var/www/anisub.tv/app/logs/iteration_sitemap_videoclips_0_1000.log &");
                die;
            }
        }

        // videoclips
        if($source == 'videoclips') {
            $output->writeln("\n<--start videoclips: " . date('Y-m-d H:i'));

            $videoclips = $this->videoClipManager->findPublicAll($offset, $limit);

            $output->writeln(count($videoclips));

            if (count($videoclips)) {

                $xml = "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
                /** @var VideoClip $v */
                foreach ($videoclips as $v) {
                    $xml .= '<url><loc>';
                    $xml .= 'http://anisub.tv/videoclip/' . $v->getHash();
                    $xml .= '</loc></url>';
                }
                $xml .= "</urlset>";

                $fileName = "videoclips_" . $offset . "_" . $limit . "_" . md5(rand(0, $offset)) . ".xml";
                $output->writeln($fileName);
                file_put_contents($xmlDirectory . '/' . $fileName, $xml);
                $offset += $limit;

                $output->writeln("--- iteration ---");
                exec("/usr/bin/php ".$appDirectory."/../app/console generator:sitemap videoclips ".$offset." ".$limit." >> /var/www/anisub.tv/app/logs/iteration_sitemap_videoclips_".$offset."_".$limit.".log &");
                die;

            } else {
                $output->writeln("\n<--finish videoclips: " . date('Y-m-d H:i'));
                exec("/usr/bin/php ".$appDirectory."/../app/console generator:sitemap words 0 10000 --env=prod --no-debug >> /var/www/anisub.tv/app/logs/iteration_sitemap_words_0_10000.log &");
                die;
            }
        }

        // words
        if ( $source == 'words' ) {
            $output->writeln("\n<--start words: " . date('Y-m-d H:i'));

            $words = $this->dictionaryManager->findAll($offset, $limit);

            $output->writeln(count($words));

            if (count($words)) {

                $xml = "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
                /** @var DictWordEn $word */
                foreach ($words as $word) {
                    $xml .= '<url><loc>';
                    $xml .= 'https://myskills.pro/en/' . rawurlencode($word->getWord());
                    $xml .= '</loc></url>';
                }
                $xml .= "</urlset>";

                $fileName = "en_words_" . $offset . "_" . $limit . "_" . md5(rand(0, $offset)) . ".xml";
                $output->writeln($fileName);
                file_put_contents($xmlDirectory . '/' . $fileName, $xml);
                $offset += $limit;

                $output->writeln("--- iteration ---");
                exec("/usr/bin/php ".$appDirectory."/../app/console generator:sitemap words ".$offset." ".$limit." >> /var/www/anisub.tv/app/logs/iteration_sitemap_words_".$offset."_".$limit.".log &");
                die;

            } else {
                $output->writeln("\n<--finish words: " . date('Y-m-d H:i'));
                exec("/usr/bin/php ".$appDirectory."/../app/console generator:sitemap sitemap --env=prod --no-debug >> /var/www/anisub.tv/app/logs/iteration_sitemap.log &");
                die;
            }

        }

        $output->writeln("\n<--finish: ".date('Y-m-d H:i'));
    }

    public static function delTree($dir) {
        if(!file_exists($dir)) return true;
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}
