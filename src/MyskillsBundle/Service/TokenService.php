<?php
namespace MyskillsBundle\Service;

use MyskillsBundle\Exception\UnexpectedTypeException;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TokenService {
    /** @var SessionInterface */
    private $session;
    const DEFAULT_TOKEN_PREFIX = '_default';

    public function __construct(SessionInterface $session) {
        $this->session = $session;
    }

    /**
     * @return string
     */
    public function setAccessToken($prefix = self::DEFAULT_TOKEN_PREFIX) {
        $csrfToken = md5(time().rand(0, 100).'_series_token');
        $this->getSession()
             ->set('csrf_token' . $prefix, $csrfToken);
        return $csrfToken;
    }

    /**
     * @param $token
     * @return bool
     */
    public function checkToken($token, $prefix = self::DEFAULT_TOKEN_PREFIX) {
        if(!$token || !$prefix) {
            return false;
        }
        $correctToken = $this->getSession()
                             ->get('csrf_token'.$prefix);
        return $correctToken == $token;
    }

    /**
     * @return SessionInterface
     * @throws UnexpectedTypeException
     */
    protected function getSession()
    {
        $this->checkClass($this->session, SessionInterface::class);

        return $this->session;
    }

    /**
     * @param $object
     * @param $class
     * @return bool
     * @throws UnexpectedTypeException
     */
    protected function checkClass($object, $class)
    {
        if(!($object instanceof $class)) {
            throw new UnexpectedTypeException($object, $class);
        }

        return true;
    }
}
