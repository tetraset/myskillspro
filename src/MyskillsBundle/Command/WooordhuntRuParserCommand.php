<?php
namespace MyskillsBundle\Command;

use MyskillsBundle\DomainManager\Dictionary\DictionaryEnRuManager;
use MyskillsBundle\DomainManager\Dictionary\DictionaryRuEnManager;
use MyskillsBundle\DomainManager\Source\DictSourceManager;
use MyskillsBundle\Entity\AudioClip;
use MyskillsBundle\Entity\DictSource;
use MyskillsBundle\Entity\DictTranslationEnRu;
use MyskillsBundle\Entity\DictTranslationRuEn;
use MyskillsBundle\Entity\DictWord;
use MyskillsBundle\Entity\DictWordEn;
use MyskillsBundle\Entity\DictWordRu;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;

set_time_limit(0);
date_default_timezone_set('Europe/Moscow');

class WooordhuntRuParserCommand extends ParserCommand {
    const ID_SOURCE = 60187;
    const INDEX_PAGE_EN_RU = 'http://wooordhunt.ru/dic/content/en_ru';
    const INDEX_PAGE_RU_EN = 'http://wooordhunt.ru/dic/content/ru_en';
    const DOMAIN = 'http://wooordhunt.ru';
    const AUDIO_PATH = UPLOAD_DIR . '/audio';
    const THREADS_COUNT = 2;

    /**
     * @var EntityManager
     */
    protected $em;

    /** @var DictSourceManager */
    private $dictSourceManager;

    /** @var DictionaryEnRuManager */
    private $wordManagerEnRu;

    /** @var DictionaryRuEnManager */
    private $wordManagerRuEn;

    private $webDirectory;
    
    public function __construct(
        EntityManager $em,
        DictSourceManager $dictSourceManager,
        DictionaryEnRuManager $dictionaryEnRuManager,
        DictionaryRuEnManager $dictionaryRuEnManager,
        $appDirectory
    )
    {
        parent::__construct();
        $this->em = $em;
        $this->dictSourceManager = $dictSourceManager;
        $this->wordManagerEnRu = $dictionaryEnRuManager;
        $this->wordManagerRuEn = $dictionaryRuEnManager;
        $this->webDirectory = $appDirectory;
    }

    protected function configure()
    {
        $this
            ->setName('parser:wooordhunt')
            ->setDescription('parser for wooordhunt.ru')
            ->addArgument(
                'lang',
                InputArgument::OPTIONAL
            )
            ->addArgument(
                'url',
                InputArgument::OPTIONAL
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("check running crons");
        $lang = $input->getArgument('lang');
        if (!$lang) {
            $lang = 'en';
        }
        $url = $input->getArgument('url', null);
        $globalUrl = $url;

        try {
            exec("ps aux | grep parser", $results);

            if (!$url) {
                $i = 0;
                foreach ($results AS $line) {
                    if (strpos($line, "parser:wooordhunt $lang") !== false) {
                        $i++;
                    }
                };
                if ($i > 1) {
                    $output->writeln("the parser:wooordhunt is already running...");
                    exit;
                }
            }

            $output->writeln("\n<--start $url: " . date('Y-m-d H:i'));

            // получение всех урлов для парса
            if (!$url) {
                $indexPage = $this->parse($lang == 'en' ? self::INDEX_PAGE_EN_RU : self::INDEX_PAGE_RU_EN, $output);

                preg_match_all("/<a href='(?P<url>[^\']+)'>.+<\/a>&ensp;/iUm", $indexPage, $matches);

                if (empty($matches['url'])) {
                    $output->writeln("\n<--nothing to parse. Finished at: " . date('Y-m-d H:i'));
                    exit;
                }

                foreach ($matches['url'] as $url) {
                    $urlString = str_replace('/', '_', $url);
                    $elements = explode('/', $url);
                    $elements[count($elements) - 1] = urlencode($elements[count($elements) - 1]);
                    $url = implode('/', $elements);
                    $output->writeln("Parse $url started at: " . date('Y-m-d H:i'));
                    exec("/usr/bin/php " . $this->webDirectory . "/../app/console parser:wooordhunt $lang '" . $url . "' --env=prod --no-debug > " . $this->webDirectory . "/../app/logs/parse_wooordhunt_$urlString.log &");

                    do {
                        sleep(60);
                        $results = [];
                        exec("ps aux | grep '$url'", $results);

                        $i = 0;
                        foreach ($results AS $line) {
                            if (strpos($line, $url) !== false) {
                                $i++;
                            }
                        };
                        if ($i <= self::THREADS_COUNT) {
                            break;
                        }
                        $output->writeln("Waiting vacant threads, now: $i....");
                    } while (true);
                }
                $output->writeln("\n<--finish: " . date('Y-m-d H:i'));
                die;
            }

            $url = self::DOMAIN . $url;
            $indexPage = $this->parse($url, $output);

            preg_match_all("/<p><a href=\"(?P<url>[^\"]+)\">(?P<word>[^<]+)<\/a>(?P<translation>[^<]+)<\/p>/iUm", $indexPage, $wordMatches);

            if (empty($wordMatches['url'])) {
                $output->writeln("\n<--nothing to parse. Finished at: " . date('Y-m-d H:i'));
                exit;
            }

            $wordUrls = $wordMatches['url'];
            var_dump($wordUrls);
            $phrasesUrlPattern = '/<a href="(?P<phraseUrl>\/phrase\/list\/\d+)">посмотрите все<\/a>/iUm';
            $phrasesPattern = '/<div class="(block )?phrases">(?P<phrases>.+)<\/div>/isUm';
            $newWord = 0;

            // обход слов
            foreach ($wordUrls as $key => $u) {
                $wordStr = trim($wordMatches['word'][$key]);
                $translationStr = trim(str_replace('&mdash; ', '', $wordMatches['translation'][$key]));
                $translationStr = trim(strip_tags(str_replace(['&ensp;', '&nbsp;'], ' ', $translationStr), '<a>'));
                $output->writeln("check word: <info>$wordStr</info>");
                $origStr = $wordStr;
                $fullContent = $this->parse(self::DOMAIN . $u, $output);

                if ($lang == 'en') {
                    $word = $this->wordManagerEnRu->findWord($wordStr);
                } else {
                    $word = $this->wordManagerRuEn->findWord($wordStr);
                }

                if (null === $word) {
                    /** @var DictWordEn $word */
                    $word = $lang == 'en' ? new DictWordEn() : new DictWordRu();
                    $word->setWord($wordStr);

                    $this->em->persist($word);
                    $this->em->flush();
                    $newWord++;

                    if ($lang == 'en') {
                        $word = $this->wordManagerEnRu->findWord($wordStr);
                    } else {
                        $word = $this->wordManagerRuEn->findWord($wordStr);
                    }

                    $output->writeln("new word detected: <info>$wordStr</info>");
                } else {
                    $output->writeln("old word detected: <info>$wordStr</info>");
                }

                /** @var DictSource $source */
                $source = $this->dictSourceManager->getById(self::ID_SOURCE);
                if (!$word->isSourceTranslationExist($source->getIdSource())) {

                    /** @var DictTranslationEnRu $translation */
                    $translation = $lang == 'en' ? new DictTranslationEnRu() : new DictTranslationRuEn();
                    $idUser = self::$OUR_USERS_IDS[rand(0, count(self::$OUR_USERS_IDS) - 1)];
                    $translation->setIdUser($idUser);
                    $translation->setLoginUser(self::$OUR_USERS[$idUser]);
                    $translation->setHtmlTranslation($translationStr);
                    $translation->setSource($source);
                    $translation->setIdSource($source->getIdSource());
                    $translation->setIsPublic(true);
                    $translation->setWordContainer($word);
                    $translation->setIdWord($word->getIdWord());
                    $this->em->persist($translation);
                    $this->em->flush();

                    $output->writeln("translation: <info>$translationStr</info>");

                    $mp3Pattern = '/<source src="(?P<mp3>[^\.]+\.mp3)" type="audio\/mpeg" >/iUm';

                    if ($lang == 'en') {
                        $output->writeln("check audio for: <info>$wordStr</info>");
                        // получаем аудио
                        preg_match_all($mp3Pattern, $fullContent, $matches);
                        if (!empty($matches['mp3'])) {
                            foreach ($matches['mp3'] as $mp3) {
                                $hash = 'wh_' . md5($mp3);
                                $path = self::AUDIO_PATH . '/' . $hash . '.mp3';

                                if (!file_exists($path)) {
                                    $mp3 = @file_get_contents(self::DOMAIN . $mp3);

                                    if (!empty($mp3)) {
                                        file_put_contents($path, $mp3);
                                        $output->writeln("add audio: <info>$path</info> for <info>$wordStr</info>");

                                        $word->addAudioClip(new AudioClip($word->getIdWord() . '_' . $hash, UPLOAD_DIR_WEB_RELATIVE . '/audio/' . $hash . '.mp3', true, $word, $wordStr));
                                        $this->em->flush();

                                    } else {
                                        $hash = null;
                                    }
                                }
                            }
                        }
                    }

                } else {
                    $output->writeln("Word <error>$wordStr</error> has already parsed");
                }

                $output->writeln("check phrases for: <info>$wordStr</info>");
                // получаем переводы словосочетаний
                if (preg_match($phrasesUrlPattern, $fullContent, $matches)) {
                    $fullContent = $this->parse(self::DOMAIN . $matches['phraseUrl'], $output);
                    $output->writeln("separate page with phrases for: <info>$wordStr</info>");
                }

                preg_match_all($phrasesPattern, $fullContent, $matches);
                if (!empty($matches['phrases'])) {
                    $phrases = array_map('trim', explode('<br/>', $matches['phrases'][0]));

                    foreach ($phrases as $phrase) {

                        $wordStr = substr($phrase, 0, strpos($phrase, '&ensp;—&ensp;'));
                        $wordStr = trim(strip_tags(str_replace(['&ensp;', '&nbsp;'], ' ', $wordStr)));
                        $translationStr = trim(strip_tags(str_replace(['&ensp;', '&nbsp;'], ' ', $phrase), '<a>'));

                        if (empty($wordStr) || $origStr == $wordStr) {
                            continue;
                        }

                        $output->writeln("check phrase: <info>$wordStr</info>");
                        if ($lang == 'en') {
                            $word = $this->wordManagerEnRu->findWord($wordStr);
                        } else {
                            $word = $this->wordManagerRuEn->findWord($wordStr);
                        }

                        if (null == $word) {
                            /** @var DictWordEn $word */
                            $word = $lang == 'en' ? new DictWordEn() : new DictWordRu();
                            $word->setWord($wordStr);
                            $this->em->persist($word);
                            $this->em->flush();
                            $newWord++;

                            if ($lang == 'en') {
                                $word = $this->wordManagerEnRu->findWord($wordStr);
                            } else {
                                $word = $this->wordManagerRuEn->findWord($wordStr);
                            }

                            $output->writeln("new phrase: <info>$wordStr</info>");
                        } else {
                            $output->writeln("old phrase: <info>$wordStr</info>");
                        }

                        /** @var DictSource $source */
                        $source = $this->dictSourceManager->getById(self::ID_SOURCE);
                        $output->writeln("Source id: " . $source->getIdSource());
                        if ($word->isSourceTranslationExist($source->getIdSource())) {
                            $output->writeln("Phrase <error>$wordStr</error> has already parsed");
                            continue;
                        }

                        /** @var DictTranslationEnRu $translation */
                        $translation = $lang == 'en' ? new DictTranslationEnRu() : new DictTranslationRuEn();
                        $idUser = self::$OUR_USERS_IDS[rand(0, count(self::$OUR_USERS_IDS) - 1)];
                        $translation->setIdUser($idUser);
                        $translation->setLoginUser(self::$OUR_USERS[$idUser]);
                        $translation->setHtmlTranslation($translationStr);
                        $translation->setSource($source);
                        $translation->setIdSource($source->getIdSource());
                        $translation->setIsPublic(true);

                        $translation->setWordContainer($word);
                        $translation->setIdWord($word->getIdWord());
                        $this->em->persist($translation);
                        $this->em->flush();

                        $output->writeln("\n\nadd translation <info>$translationStr</info> for phrase: <info>$wordStr</info>\n\n");

                        $connection = $this->em->getConnection();

                        $output->writeln("reconnect to mysql...");
                        $connection->close();
                        $connection->connect();

                        $this->checkEMConnection($this->em, $connection);
                    }
                }
                $output->writeln("----------------------------");

                $connection = $this->em->getConnection();

                $output->writeln("reconnect to mysql...");
                $connection->close();
                $connection->connect();

                $this->checkEMConnection($this->em, $connection);
            }

            $output->writeln("update words & phrase: " . date('Y-m-d H:i'));
            $output->writeln("count of new words: <info>" . $newWord . "</info>");
            $output->writeln("\n<--finish: " . date('Y-m-d H:i'));
        } catch (\Exception $e) {
            $output->writeln("\n\nERROR: <error>" . $e->getMessage() . "</error>");
            $output->writeln("\n<--reload cron at: " . date('Y-m-d H:i'));
            $urlString = str_replace('/', '_', $globalUrl);
            exec("/usr/bin/php " . $this->webDirectory . "/../app/console parser:wooordhunt $lang '" . $globalUrl . "' --env=prod --no-debug >> " . $this->webDirectory . "/../app/logs/parse_wooordhunt_$urlString.log &");
        }
    }
}