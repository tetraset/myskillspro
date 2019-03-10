<?php

namespace MyskillsBundle\Controller;

use MyskillsBundle\DomainManager\BaseDomainManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route(service="main.controller")
 */
class MainController extends BaseController
{
    /**
     * @Route("/terms", name="terms")
     */
    public function terms() {
        return $this->render('MyskillsBundle:Video:terms.html.twig');
    }

    /**
     * @Route("/licence", name="licence")
     */
    public function licence() {
        return $this->render('MyskillsBundle:Video:licence.html.twig');
    }
}
