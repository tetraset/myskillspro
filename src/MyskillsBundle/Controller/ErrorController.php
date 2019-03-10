<?php
namespace MyskillsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route(service="error.controller")
 */
class ErrorController extends BaseController
{
    public function __construct()
    {
        parent::__construct(null);
    }

    /**
     * @Route("/error/{code}", name="errorpage")
     */
    public function errorAction($code)
    {
        return $this->render('TwigBundle:Exception:error.html.twig');
    }
}
