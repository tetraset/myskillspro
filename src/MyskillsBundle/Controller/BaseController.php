<?php
namespace MyskillsBundle\Controller;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\Exception\UnexpectedTypeException;
use MyskillsBundle\Service\SerializerService;
use MyskillsBundle\Service\TokenService;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Common\Cache\MemcachedCache;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;

abstract class BaseController extends Controller
{
    const CACHE_TIMEOUT = 30 * 24 * 60 * 60; // 1 month

    /** @var Logger */
    protected $logger;

    /** @var MemcachedCache */
    private $cacheService;

    /** @var SerializerService */
    private $serializer;

    /** @var RequestStack */
    private $requestStack;

    /** @var BaseDomainManager */
    private $domainManager;

    /** @var EngineInterface */
    private $templating;

    /** @var Router */
    private $router;

    /** @var TokenStorage */
    private $tokenStorage;

    /** @var FormFactory */
    private $formFactory;

    /** @var TokenService */
    private $tokenizer;

    /**
     * @param BaseDomainManager $domainManager
     */
    public function __construct(BaseDomainManager $domainManager = null)
    {
        $this->domainManager = $domainManager;
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string|FormTypeInterface $type    The built type of the form
     * @param mixed                    $data    The initial data for the form
     * @param array                    $options Options for the form
     *
     * @return Form
     */
    public function createForm($type, $data = null, array $options = array())
    {
        return $this->formFactory->create($type, $data, $options);
    }

    /**
     * @return User|null
     */
    public function getUser() {
        $user = null;
        $token = $this->getTokenStorage()->getToken();
        if (null !== $token && is_object($token->getUser())) {
            $user = $token->getUser();
        }
        return $user;
    }

    /**
     * @return FormFactory
     */
    public function getFormFactory()
    {
        $this->checkClass($this->formFactory, FormFactory::class);
        return $this->formFactory;
    }

    /**
     * @param FormFactory $formFactory
     */
    public function setFormFactory($formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @return TokenStorage
     */
    public function getTokenStorage()
    {
        $this->checkClass($this->tokenStorage, TokenStorage::class);
        return $this->tokenStorage;
    }

    /**
     * @param TokenStorage $tokenStorage
     */
    public function setTokenStorage($tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        $this->checkClass($this->router, Router::class);
        return $this->router;
    }

    /**
     * @param Router $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @return EngineInterface
     * @throws UnexpectedTypeException
     */
    public function getTemplating()
    {
        $this->checkClass($this->templating, EngineInterface::class);
        return $this->templating;
    }

    /**
     * @param EngineInterface $templating
     */
    public function setTemplating($templating)
    {
        $this->templating = $templating;
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string      $route         The name of the route
     * @param mixed       $parameters    An array of parameters
     * @param bool|string $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->getRouter()->generate($route, $parameters, $referenceType);
    }

    /**
     * Renders a view.
     *
     * @param string   $view       The view name
     * @param array    $parameters An array of parameters to pass to the view
     * @param Response $response   A response instance
     *
     * @return Response A Response instance
     */
    public function render($view, array $parameters = array(), Response $response = null)
    {
        return $this->getTemplating()->renderResponse($view, $parameters, $response);
    }

    /**
     * Get domain manager
     *
     * @return BaseDomainManager
     * @throws UnexpectedTypeException
     */
    protected function getDomainManager()
    {
        $this->checkClass($this->domainManager, BaseDomainManager::class);

        return $this->domainManager;
    }

    /**
     * @return Logger
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return MemcachedCache
     * @throws UnexpectedTypeException
     */
    protected function getCacheService()
    {
        $this->checkClass($this->cacheService, MemcachedCache::class);

        return $this->cacheService;
    }

    /**
     * @param MemcachedCache $cacheService
     */
    public function setCacheService(MemcachedCache $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * @return SerializerService
     * @throws UnexpectedTypeException
     */
    protected function getSerializer()
    {
        $this->checkClass($this->serializer, SerializerService::class);

        return $this->serializer;
    }

    /**
     * @param SerializerService $serializer
     */
    public function setSerializer(SerializerService $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return RequestStack
     * @throws UnexpectedTypeException
     */
    protected function getRequestStack()
    {
        $this->checkClass($this->requestStack, RequestStack::class);

        return $this->requestStack;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return TokenService
     * @throws UnexpectedTypeException
     */
    protected function getTokenizer()
    {
        $this->checkClass($this->tokenizer, TokenService::class);

        return $this->tokenizer;
    }

    /**
     * @param TokenService $tokenizer
     */
    public function setTokenizer(TokenService $tokenizer)
    {
        $this->tokenizer = $tokenizer;
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
