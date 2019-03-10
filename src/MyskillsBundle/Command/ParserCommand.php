<?php
namespace MyskillsBundle\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
set_time_limit(0);
date_default_timezone_set('Europe/Moscow');
abstract class ParserCommand extends Command
{
    protected static $OUR_USERS = [
        1 => 'tetraset',
        48 => 'ya_flomaster',
        75 => 'sensei',
        76 => 'cat',
        77 => 'thedenisovs',
    ];
    protected static $OUR_USERS_IDS = [1, 48, 75, 76, 77];
    public static $CONNECT_TIMEOUT = 5;
    public static $PARSER_URL_BASE = 'http://yakuru.net/search.aspx?w=';
    public static $LIMIT_FOR_PARSING = 100;
    public static $RUSSIA_PROXY_IPS = [
        '37.230.117.223:8080',
        '46.17.46.192:8080',
        '46.29.160.108:8080',
    ];
    public static $PROXY_IPS = [
        "37.230.117.223:8080",
        "46.17.46.192:8080",
        "46.29.160.108:8080",
        "46.38.63.2:8080",
        "109.68.191.190:8080",
        "154.127.61.72:8080",
        "178.32.207.175:8080",
        "178.33.163.110:8080",
        "188.165.0.128:8080",
        "191.96.4.192:8080",
    ];
    protected $ip;
    protected $browser;
    protected $newWords = [];
    private $user;
    private $pass;
    /**
     * @param $url
     * @param null $output
     * @param array $headersArr
     * @param null $myIp
     * @param null $browser
     * @param int $tryLimit
     * @param int $iteration
     * @return Response|null|string
     * @throws \Exception
     */
    protected function parse($url, $output = null, $headersArr = [], $myIp = null, $browser = null, $tryLimit = 10, $iteration = 0)
    {
        $client = new Client();
        $this->ip = $myIp ? $myIp : self::$PROXY_IPS[rand(0, count(self::$PROXY_IPS) - 1)];
        $this->browser = $browser ? $browser : $this->random_uagent();
        $user = $this->user;
        $pass = $this->pass;
        $headers = array_merge($headersArr, ['User-Agent' => $this->browser]);
        if ($output) {
            $output->writeln("ip: ".$this->ip);
            $output->writeln("user_agent: ".$this->browser);
            $output->writeln("url: ".$url);
        }
        $result = null;
        try {
            /** @var Response $result */
            $result = $client->get(
                $url,
                [
                    'connect_timeout' => self::$CONNECT_TIMEOUT,
                    'proxy' => 'http://'.$user.':'.$pass.'@'.$this->ip,
                    'headers' => $headers,
                    'http_errors' => false,
                    'stream' => !empty($headersArr['stream']),
                ]
            );
        } catch (\Exception $e) {
            if ($output) {
                $output->writeln("!!!! Problem: ".$e->getMessage());
            } else {
                throw $e;
            }
        }
        if (empty($result) || $result->getStatusCode() != 200) {
            if (!empty($result) && $result->getStatusCode() == 404) {
                $output && $output->writeln("404");
                return null;
            }
            if ($iteration > $tryLimit) {
                $message = printf('Problem with request %s%s', $url, null === $result ? "" : ": ".$result->getStatusCode().', ip: '.$this->ip);
                throw new \RuntimeException($message);
            } else {
                $output && $output->writeln("try again...");
            }
            return $this->parse($url, $output, $headersArr, $myIp ? $myIp : null, $browser ? $browser : null, $tryLimit, ++$iteration);
        }
        return empty($headersArr['stream']) ? $result->getBody()->getContents() : $result;
    }
    protected function postParse($url, $output = null, $formParams, $tryLimit = 10, $iteration = 0)
    {
        $client = new Client();
        $user = $this->user;
        $pass = $this->pass;
        $headers = ['User-Agent' => $this->browser];
        $output && $output->writeln("ip: ".$this->ip);
        $output && $output->writeln("user_agent: ".$this->browser);
        $output && $output->writeln("url: ".$url);
        $result = null;
        try {
            /** @var Response $result */
            $result = $client->post(
                $url,
                [
                    'connect_timeout' => self::$CONNECT_TIMEOUT,
                    'proxy' => 'http://'.$user.':'.$pass.'@'.$this->ip,
                    'headers' => $headers,
                    'http_errors' => false,
                    'form_params' => $formParams,
                ]
            );
        } catch (\Exception $e) {
            if ($output) {
                $output->writeln("!!!! Problem: ".$e->getMessage());
            } else {
                throw $e;
            }
        }
        if (empty($result) || $result->getStatusCode() != 200) {
            if (!empty($result) && $result->getStatusCode() == 404) {
                $output && $output->writeln("404");
                return null;
            }
            if ($iteration > $tryLimit) {
                $message = printf('Problem with request %s%s', $url, null === $result ? "" : ": ".$result->getStatusCode().', ip: '.$this->ip);
                throw new \RuntimeException($message);
            } else {
                $output && $output->writeln("try again...");
            }
            return $this->postParse($url, $output, $formParams, $tryLimit, ++$iteration);
        }
        return empty($headersArr['stream']) ? $result->getBody()->getContents() : $result;
    }
    protected function chooseRandomBrowserAndOs()
    {
        $frequencies = array(
            34 => array(
                89 => array('chrome', 'win'),
                9 => array('chrome', 'mac'),
                2 => array('chrome', 'lin'),
            ),
            32 => array(
                100 => array('iexplorer', 'win'),
            ),
            25 => array(
                83 => array('firefox', 'win'),
                16 => array('firefox', 'mac'),
                1 => array('firefox', 'lin'),
            ),
            7 => array(
                95 => array('safari', 'mac'),
                4 => array('safari', 'win'),
                1 => array('safari', 'lin'),
            ),
            2 => array(
                91 => array('opera', 'win'),
                6 => array('opera', 'lin'),
                3 => array('opera', 'mac'),
            ),
        );
        $rand = rand(1, 100);
        $sum = 0;
        foreach ($frequencies as $freq => $osFreqs) {
            $sum += $freq;
            if ($rand <= $sum) {
                $rand = rand(1, 100);
                $sum = 0;
                foreach ($osFreqs as $freq => $choice) {
                    $sum += $freq;
                    if ($rand <= $sum) {
                        return $choice;
                    }
                }
            }
        }
        throw new \Exception("Frequencies don't sum to 100.");
    }
    protected function array_random(array $array)
    {
        return $array[array_rand($array, 1)];
    }
    protected function nt_version()
    {
        return rand(5, 6).'.'.rand(0, 1);
    }
    protected function ie_version()
    {
        return rand(7, 9).'.0';
    }
    protected function trident_version()
    {
        return rand(3, 5).'.'.rand(0, 1);
    }
    protected function osx_version()
    {
        return "10_".rand(5, 7).'_'.rand(0, 9);
    }
    protected function chrome_version()
    {
        return rand(13, 15).'.0.'.rand(800, 899).'.0';
    }
    protected function presto_version()
    {
        return '2.9.'.rand(160, 190);
    }
    protected function presto_version2()
    {
        return rand(10, 12).'.00';
    }
    protected function firefox($arch)
    {
        $ver = $this->array_random(
            array(
                'Gecko/'.date('Ymd', rand(strtotime('2011-1-1'), time())).' Firefox/'.rand(5, 7).'.0',
                'Gecko/'.date('Ymd', rand(strtotime('2011-1-1'), time())).' Firefox/'.rand(5, 7).'.0.1',
                'Gecko/'.date('Ymd', rand(strtotime('2010-1-1'), time())).' Firefox/3.6.'.rand(1, 20),
                'Gecko/'.date('Ymd', rand(strtotime('2010-1-1'), time())).' Firefox/3.8',
            )
        );
        switch ($arch) {
            case 'lin':
                return "(X11; Linux {proc}; rv:".rand(5, 7).".0) $ver";
            case 'mac':
                $osx = $this->osx_version();
                return "(Macintosh; {proc} Mac OS X $osx rv:".rand(2, 6).".0) $ver";
            case 'win':
            default:
                $nt = $this->nt_version();
                return "(Windows NT $nt; {lang}; rv:1.9.".rand(0, 2).".20) $ver";
        }
    }
    protected function safari($arch)
    {
        $saf = rand(531, 535).'.'.rand(1, 50).'.'.rand(1, 7);
        if (rand(0, 1) == 0) {
            $ver = rand(4, 5).'.'.rand(0, 1);
        } else {
            $ver = rand(4, 5).'.0.'.rand(1, 5);
        }
        switch ($arch) {
            case 'mac':
                $osx = $this->osx_version();
                return "(Macintosh; U; {proc} Mac OS X $osx rv:".rand(2, 6).".0; {lang}) AppleWebKit/$saf (KHTML, like Gecko) Version/$ver Safari/$saf";
            //case 'iphone':
            //    return '(iPod; U; CPU iPhone OS ' . rand(3, 4) . '_' . rand(0, 3) . " like Mac OS X; {lang}) AppleWebKit/$saf (KHTML, like Gecko) Version/" . rand(3, 4) . ".0.5 Mobile/8B" . rand(111, 119) . " Safari/6$saf";
            case 'win':
            default:
                $nt = $this->nt_version();
                return "(Windows; U; Windows NT $nt) AppleWebKit/$saf (KHTML, like Gecko) Version/$ver Safari/$saf";
        }
    }
    protected function iexplorer($arch)
    {
        $ie_extra = array(
            '',
            '; .NET CLR 1.1.'.rand(4320, 4325).'',
            '; WOW64',
        );
        $nt = $this->nt_version();
        $ie = $this->ie_version();
        $trident = $this->trident_version();
        return "(compatible; MSIE $ie; Windows NT $nt; Trident/$trident)";
    }
    protected function opera($arch)
    {
        $op_extra = array(
            '',
            '; .NET CLR 1.1.'.rand(4320, 4325).'',
            '; WOW64',
        );
        $presto = $this->presto_version();
        $version = $this->presto_version2();
        switch ($arch) {
            case 'lin':
                return "(X11; Linux {proc}; U; {lang}) Presto/$presto Version/$version";
            case 'win':
            default:
                $nt = $this->nt_version();
                return "(Windows NT $nt; U; {lang}) Presto/$presto Version/$version";
        }
    }
    protected function chrome($arch)
    {
        $saf = rand(531, 536).rand(0, 2);
        $chrome = $this->chrome_version();
        switch ($arch) {
            case 'lin':
                return "(X11; Linux {proc}) AppleWebKit/$saf (KHTML, like Gecko) Chrome/$chrome Safari/$saf";
            case 'mac':
                $osx = $this->osx_version();
                return "(Macintosh; U; {proc} Mac OS X $osx) AppleWebKit/$saf (KHTML, like Gecko) Chrome/$chrome Safari/$saf";
            case 'win':
            default:
                $nt = $this->nt_version();
                return "(Windows NT $nt) AppleWebKit/$saf (KHTML, like Gecko) Chrome/$chrome Safari/$saf";
        }
    }
    /**
     * Main function which will choose random browser
     * @param  array $lang languages to choose from
     * @return string       user agent
     */
    protected function random_uagent(array $lang = array('en-US'))
    {
        list($browser, $os) = $this->chooseRandomBrowserAndOs();
        $proc = array(
            'lin' => array('i686', 'x86_64'),
            'mac' => array('Intel', 'PPC', 'U; Intel', 'U; PPC'),
            'win' => array('foo'),
        );
        switch ($browser) {
            case 'firefox':
                $ua = "Mozilla/5.0 ".$this->firefox($os);
                break;
            case 'safari':
                $ua = "Mozilla/5.0 ".$this->safari($os);
                break;
            case 'iexplorer':
                $ua = "Mozilla/5.0 ".$this->iexplorer($os);
                break;
            case 'opera':
                $ua = "Opera/".rand(8, 9).'.'.rand(10, 99).' '.$this->opera($os);
                break;
            case 'chrome':
                $ua = 'Mozilla/5.0 '.$this->chrome($os);
                break;
        }
        $ua = str_replace('{proc}', $this->array_random($proc[$os]), $ua);
        $ua = str_replace('{lang}', $this->array_random($lang), $ua);
        return $ua;
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
    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }
    /**
     * @param string $pass
     */
    public function setPass($pass)
    {
        $this->pass = $pass;
    }
}