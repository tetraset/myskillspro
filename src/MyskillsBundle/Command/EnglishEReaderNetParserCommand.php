<?php
namespace MyskillsBundle\Command;
use MyskillsBundle\DomainManager\Book\BookManager;
use MyskillsBundle\Entity\Book;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
class EnglishEReaderNetParserCommand extends ParserCommand
{
    const INDEX_PAGE = 'http://english-e-reader.net/findbook';
    const TXT_LINK = 'http://english-e-reader.net/download?link={code}&format=txt';
    const DOMAIN = 'http://english-e-reader.net';
    const THREADS_COUNT = 4;
    /**
     * @var EntityManager
     */
    protected $em;
    private $webDirectory;
    private $bookManager;
    public function __construct(
        EntityManager $em,
        BookManager $bookManager,
        $appDirectory
    ) {
        parent::__construct();
        $this->em = $em;
        $this->webDirectory = $appDirectory;
        $this->bookManager = $bookManager;
    }
    protected function configure()
    {
        $this->setName('parser:ebooks')->setDescription('parser for english-e-reader.net');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("check running crons");
        exec("ps aux | grep parser", $results);
        $i = 0;
        foreach ($results AS $line) {
            if (strpos($line, "parser:ebooks") !== false) {
                $i++;
            }
        };
        if ($i > 1) {
            $output->writeln("the parser:ebooks is already running...");
            exit;
        }
        $output->writeln("\n<--started at ".date('Y-m-d H:i'));
        $this->ip = self::$PROXY_IPS[rand(0, count(self::$PROXY_IPS) - 1)];
        $this->browser = $this->random_uagent();
        $content = $this->postParse(
            self::INDEX_PAGE,
            $output,
            [
                'author' => 'any',
                'english' => 'any',
                'genre' => 'any',
                'text' => 'any',
                'audio' => 'false',
                'sortColumn' => 'date',
                'sortType' => 'DESC',
                'length' => 'any',
            ]
        );
        $regExpData = '/<td>\s+<a href="(?P<link>[^"]+)"[^>]+>(?P<title>[^<]+)<\/a>\s+<\/td>\s+<td>\s+<a[^>]+>(?P<author>[^>]+)<\/a>\s+<\/td>\s+<td>\s+<a[^>]+>(?P<english>[^<]+)<\/a>\s+<\/td>\s+<td>\s+<a[^>]+>(?P<genre>[^<]+)<\/a>\s+<\/td>\s+<td>\s+<a[^>]+>(?P<level>[^<]+)<\/a>\s+<\/td>\s+<td>\s+<a[^>]+>(?P<length>[^<]+)<\/a>/ismU';
        preg_match_all($regExpData, $content, $matches);
        if (empty($matches['link'])) {
            $output->writeln("nothing to check");
            exit;
        }
        foreach ($matches['link'] as $i => $link) {
            $code = trim(substr($link, strrpos($link, '/') + 1));
            $bookData = $this->parse(self::DOMAIN.$link, $output);
            preg_match('/<img class="img-thumbnail" src="(?P<poster>[^"]+)".+<p class="text-justify">(?P<description>.+)<\/p>/iUms', $bookData, $matches2);
            $oldBook = $this->bookManager->getByCode($code);
            if ($oldBook !== null) {
                if (null === $oldBook->getPoster() || strpos($oldBook->getPoster(), 'http') !== false) {
                    $posterFile = $this->webDirectory.'/uploads/poster/'.$code.'.jpg';
                    if (!file_exists($posterFile)) {
                        $output->writeln("download poster");
                        copy(self::DOMAIN.$matches2['poster'], $posterFile);
                    }
                    $oldBook->setPosterUrl(str_replace($this->webDirectory, '', $posterFile));
                }
                $output->writeln("<info>".$oldBook." is already in db</info>");
                $this->em->flush();
                continue;
            }
            $book = new Book();
            $book->setCode($code);
            $book->setTitle($matches['title'][$i]);
            $book->setAuthor($matches['author'][$i]);
            $book->setEnglish($matches['english'][$i]);
            $book->setGenre($matches['genre'][$i]);
            $book->setLevel($matches['level'][$i]);
            $book->setLength($matches['length'][$i]);
            $book->setDescription($matches2['description']);
            $book->setContent(trim(file_get_contents(str_replace('{code}', $code, self::TXT_LINK))));
            $book->setIsPublic(true);
            $book->setDatePublish(new \DateTime());
            $posterFile = $this->webDirectory.'/uploads/poster/'.$code.'.jpg';
            if (!file_exists($posterFile)) {
                $output->writeln("download poster");
                copy(self::DOMAIN.$matches2['poster'], $posterFile);
            }
            $book->setPosterUrl(str_replace($this->webDirectory, '', $posterFile));
            $book->setSource(self::DOMAIN);
            $this->em->persist($book);
            $this->em->flush();
            $output->writeln("<info>added ".$book."</info>");
        }
        $output->writeln("\n<--finished at ".date('Y-m-d H:i'));
    }
}