<?php
namespace MyskillsBundle\Controller;

use MyskillsBundle\DomainManager\Game\GameManager;
use MyskillsBundle\DomainManager\VideoClip\VideoClipManager;
use MyskillsBundle\Exception\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use MyskillsBundle\Entity\VideoClip;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route(service="video_clip.controller")
 */
class VideoClipController extends BaseController
{
    /**
     * @Route("/videoclip/{hash}", name="videoclip")
     */
    public function getByHashAction($hash, Request $request)
    {
        /** @var VideoClipManager $manager */
        $manager = $this->getDomainManager();
        $isIframe = $request->query->get('iframe', false);
        $isExport = $request->query->get('export', false);

        try {
            /** @var VideoClip $videoClip */
            $videoClip = $manager->getPublicByHash($hash);
        } catch(EntityNotFoundException $e) {
            throw $this->createNotFoundException('The video clip does not exist');
        }
        $prefix = 'clip_' . $hash;
        $csrfToken = $this->getTokenizer()->setAccessToken($prefix);
        $parentVideoClip = $videoClip->getParentVideoClip();

        $manager->addYandexData($videoClip);
        $manager->addYandexData($parentVideoClip);

        $videoLink = $videoClip->getVideoUrl();
        $parentVideoLink = $parentVideoClip->getVideoUrl();

        $contentType = 'video/mp4';

        return $this->render('MyskillsBundle:Video:video_clip'.($isIframe ? '_iframe' : '').'.html.twig', array(
            'episode' => $videoClip,
            'csrf_token' => $csrfToken,
            'video_link' => $videoLink,
            'video_link_full' => $parentVideoLink,
            'type' => 'clip',
            'video_type' => $contentType,
            'csrf_prefix' => $prefix,
            'export' => $isExport && $isIframe
        ));
    }
}
