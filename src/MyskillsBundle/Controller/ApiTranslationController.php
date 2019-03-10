<?php
namespace MyskillsBundle\Controller;
use MyskillsBundle\DomainManager\ApiTranslation\ApiTranslationManager;
use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\Exception\InvalidArgumentException;
use MyskillsBundle\Service\TokenService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\Criteria;
use Sonata\NewsBundle\Model\PostInterface;
use Sonata\NewsBundle\Model\PostManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\Serializer\SerializerBuilder;
/**
 * @Route(service="api_translation.controller")
 */
class ApiTranslationController extends BaseController
{
    const SHOW_LIMIT_ON_PAGE = 3;
    const CACHE_TIMEOUT = 30 * 24 * 60 * 60; // 1 month
    private $allowedDomains = ['myskills.pro', 'en.myskills.pro', 'myskillspro.ru', 'en.myskillspro.ru'];
    /**
     * @Route("/api/entranslate", name="get_en_translate")
     * @return Response
     */
    public function getTranslation(Request $request) {
        $word = trim(mb_strtolower($request->get('word', '')));
        $csrfToken = $request->get('csrf_token');
        $csrfPrefix = $request->get('csrf_prefix', TokenService::DEFAULT_TOKEN_PREFIX);
        return $this->translate($word, $csrfToken, $csrfPrefix);
    }
    /**
     * @Route("/api/entranslate/add", name="add_en_translate")
     * @Method("POST")
     */
    public function addTranslation(Request $request) {
        $word = trim(mb_strtolower($request->get('word', '')));
        $translation = strip_tags(trim($request->get('translation', '')));
        $csrfToken = $request->get('csrf_token');
        $csrfPrefix = $request->get('csrf_prefix', TokenService::DEFAULT_TOKEN_PREFIX);
        /** @var ApiTranslationManager $manager */
        $manager = $this->getDomainManager();
        $user = $this->getUser();
        if(!$this->getTokenizer()->checkToken($csrfToken, $csrfPrefix)) {
            return new Response(
                '{"error":"csrf token is invalid"}',
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/json')
            );
        }
        if(null === $user) {
            return new Response(
                '{"error":"Вам необходимо войти или зарегистрироваться, чтобы добавлять переводы"}',
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/json')
            );
        }
        try {
            $word = $manager->limitWords($word);
            $manager->addTranslation($word, $translation, $user);
        } catch(InvalidArgumentException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                400
            );
        }
        return new JsonResponse(
            ['result' => 'ok'],
            Response::HTTP_OK
        );
    }
    /**
     * @Route("/api/entranslate/remove", name="remove_en_translate")
     * @Method("DELETE")
     */
    public function removeTranslation(Request $request) {
        $idTranslation = (int)$request->get('id_translation');
        $csrfToken = $request->get('csrf_token');
        $csrfPrefix = $request->get('csrf_prefix', TokenService::DEFAULT_TOKEN_PREFIX);
        /** @var ApiTranslationManager $manager */
        $manager = $this->getDomainManager();
        $user = $this->getUser();
        if(!$this->getTokenizer()->checkToken($csrfToken, $csrfPrefix)) {
            return new Response(
                '{"error":"csrf token is invalid"}',
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/json')
            );
        }
        if(null === $user) {
            return new Response(
                '{"error":"Вам необходимо войти или зарегистрироваться, чтобы добавлять/удалять переводы"}',
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/json')
            );
        }
        try {
            $manager->removeTranslation($idTranslation, $user);
        } catch(InvalidArgumentException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }
        return new JsonResponse(
            ['result' => 'ok'],
            Response::HTTP_OK
        );
    }
    private function translate($word, $csrfToken, $csrfPrefix) {
        /** @var ApiTranslationManager $manager */
        $manager = $this->getDomainManager();
        if(!empty($this->allowedDomains)) {
            foreach($this->allowedDomains as $domain) {
                header('Access-Control-Allow-Origin: '.$domain);
            }
        }
        if(!$this->getTokenizer()->checkToken($csrfToken, $csrfPrefix)) {
            return new Response(
                '{"error":"csrf token is invalid"}',
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/json')
            );
        }
        $word = $manager->limitWords($word);
        $key = md5($word);
        if(($jsonContent = $this->getCacheService()->fetch('word_'.$key)) === false) {
            $results_arr = $manager->translate($word, false);
            $serializer = SerializerBuilder::create()->build();
            $jsonContent = $serializer->serialize(
                array_slice($results_arr, 0, self::SHOW_LIMIT_ON_PAGE),
                'json'
            );
            $this->getCacheService()->save('word_'.$key, $jsonContent, self::CACHE_TIMEOUT);
        }
        return new Response(
            $jsonContent,
            Response::HTTP_OK,
            array('content-type' => 'application/json')
        );
    }
}