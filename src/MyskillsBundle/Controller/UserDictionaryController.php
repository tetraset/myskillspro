<?php
namespace MyskillsBundle\Controller;

use MyskillsBundle\DomainManager\UserDictionary\UserDictionaryManager;
use MyskillsBundle\Exception\LimitUserDictionaryException;
use MyskillsBundle\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Application\Sonata\UserBundle\Entity\User;

/**
 * @Route(service="user_dictionary.controller")
 */
class UserDictionaryController extends BaseController
{
    /**
     * @Route("/dictionary", name="dictionary")
     * @Route("/dictionary/{idFolder}", name="dictionary")
     * @Method({"GET","POST"})
     */
    public function dictionaryAction(Request $request, $idFolder=0)
    {
        $ajax = (bool)$request->query->get('ajax', 0);
        $page = (int)$request->query->get('p', 1);
        $user = $this->getUser();
        /** @var UserDictionaryManager $manager */
        $manager = $this->getDomainManager();

        if($user === null) {
            return $this->redirect($this->generateUrl('fos_user_profile_show'));
        }
        $checkedWords = $request->request->get('checkedWords', []);
        $checkedFolders = $request->request->get('checkedFolders', []);
        $targetLocation = $request->request->get('targetLocation', -1);
        $addFolder = trim($request->request->get('new_folder', null));

        if (!empty($addFolder)) {
            $manager->addFolder($addFolder, $user->getId(), $idFolder);
        } elseif (!empty($checkedWords) || !empty($checkedFolders)) {
            if(isset($targetLocation) && $targetLocation != -1) {
                $manager->relocateWords($user->getId(), $checkedWords, (int)$targetLocation);
                if($idFolder != (int)$targetLocation) {
                    $manager->relocateFolders($user->getId(), $checkedFolders, (int)$targetLocation);
                }
            } else {
                $manager->deleteFolders($user->getId(), $checkedFolders);
                $manager->deleteWords($user->getId(), $checkedWords);
            }
        }
        $data = $manager->getLastWords($user->getId(), $page, $idFolder);
        $words = $data['items'];
        $is_more_words = $data['is_more'];
        $data = $manager->getLastFolders($user->getId(), $idFolder);
        $folders = $data['items'];

        if( $ajax ) {
            return $this->render('MyskillsBundle:Video:words_list.html.twig', array(
                'words' => $words,
                'page' => $page+1,
                'is_more' => $is_more_words
            ));
        }
        $csrfToken = $this->getTokenizer()->setAccessToken(UserDictionaryManager::TOKEN_PREFIX);

        return $this->render('MyskillsBundle:Video:dictionary.html.twig', array(
            'words' => $words,
            'folders' => $folders,
            'is_more' => $is_more_words,
            'page' => $page+1,
            'id_folder' => $idFolder,
            'csrf_token' => $csrfToken,
            'csrf_prefix' => UserDictionaryManager::TOKEN_PREFIX
        ));
    }

    /**
     * @Route("/api/word/add", name="api_word")
     * @Method({"POST"})
     */
    public function addWordAction(Request $request) {
        /** @var UserDictionaryManager $manager */
        $manager = $this->getDomainManager();

        $csrfToken = $request->request->get('csrf_token');
        $csrfPrefix = $request->get('csrf_prefix', TokenService::DEFAULT_TOKEN_PREFIX);

        if(!$this->getTokenizer()->checkToken($csrfToken, $csrfPrefix)) {
            return new Response(
                '{"error":"csrf token is invalid"}',
                Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/json')
            );
        }

        /**
         * @var User $user
         */
        $user = $this->getUser();

        if($user === null) {
            return new Response(
                '{"error":"Вам необходимо войти, чтобы добавлять слова и выражения в словарь"}', Response::HTTP_FORBIDDEN,
                array('content-type' => 'application/json')
            );
        }

        $idWord = (int)$request->request->get('id_word');

        if(!$idWord) {
            return new Response(
                '{"error":"Произошла ошибка на сервере, попробойте еще раз"}', Response::HTTP_INTERNAL_SERVER_ERROR,
                array('content-type' => 'application/json')
            );
        }
        $idVideo = (int)$request->request->get('id_video');
        $timeOnVideo = (int)$request->request->get('time_on_video');
        $subSearchText = $request->request->get('sub_search_text');
        $hashVideoClip = $request->request->get('hash_video_clip');
        $idUser = $user->getId();

        try {
            $manager->addWord($idUser, $user->isActiveSubscription(), $idWord, $idVideo, $timeOnVideo, $subSearchText, $hashVideoClip);
        } catch(LimitUserDictionaryException $e) {
            return new Response($e->getMessage());
        }
        return new Response('ok');
    }
}
