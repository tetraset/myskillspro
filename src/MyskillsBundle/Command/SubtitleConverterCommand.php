<?php
namespace MyskillsBundle\Command;

use MyskillsBundle\Entity\VttSubtitle;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Command\Command;
use DOMDocument;
use GuzzleHttp\Client;

set_time_limit(0);
date_default_timezone_set('Europe/Moscow');
ini_set('memory_limit', '-1');

class SubtitleConverterCommand extends Command
{
    private $webDirectory;

    public function __construct(
        $appDirectory
    ) {
        parent::__construct();
        $this->webDirectory = $appDirectory;
    }

    protected function configure()
    {
        $this->setName('converter:subs')
             ->setDescription('converter for subs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("start subs converter: " . date('Y-m-d H:i'));

        $directory = VttSubtitle::$SERVER_PATH_TO_IMAGE_FOLDER;
        $scannedDirectory = array_diff(scandir($directory), array('..', '.'));
        $output->writeln("elements count: " . count($scannedDirectory));

        foreach($scannedDirectory as $i=>$file) {
            $output->writeln("$i. check $file");

            if(strpos($file, '.vtt') === false) {
                if(strpos($file, '.srt') !== false) {
                    $output->writeln("$i. <error>$file is .srt, remove it</error>");
                    @unlink($directory . '/' . $file);
                } else {
                    $output->writeln("$i. $file is directory, skip it");
                }
                continue;
            }

            $newDir = VttSubtitle::generatePath($file);

            if(!file_exists($directory . '/' .$newDir)) {
                mkdir($directory . '/' .$newDir, 0777, true);
            }

            $targetFile = $directory . '/' .$newDir . '/' . $file;
            if(strpos($file, 'free_') === 0) {
                preg_match('/free_(?P<hash>[a-z0-9]+)_/i', $file, $match);
                if(!empty($match['hash'])) {
                    $freeFilename = substr($match['hash'], 0, 5) . '_' . str_replace($match['hash'], '', $file);
                    $newDir = VttSubtitle::generatePath($freeFilename);

                    if(!file_exists($directory . '/' .$newDir)) {
                        mkdir($directory . '/' .$newDir, 0777, true);
                    }

                    $targetFile = $directory . '/' .$newDir . '/' . $freeFilename;
                }
            }

            rename($directory . '/' . $file, $targetFile);
            $output->writeln("$i. $file is already converted, skip it");
            $output->writeln("$i. $file is relocated to: " . $targetFile);
        }

        $output->writeln("finish subs converter: " . date('Y-m-d H:i'));
    }
}