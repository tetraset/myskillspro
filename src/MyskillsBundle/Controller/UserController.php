<?php
namespace MyskillsBundle\Controller;

use MyskillsBundle\DomainManager\User\UserManager;
use MyskillsBundle\Form\Type\UserAvatarType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use MyskillsBundle\Entity\UserAvatar;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route(service="user.controller")
 */
class UserController extends BaseController
{
    /**
     * @Route("/avatar/new", name="user_profile_edit_avatar")
     * @Method({"POST", "GET"})
     */
    public function newAvatarAction(Request $request)
    {
        /** @var UserManager $manager */
        $manager = $this->getDomainManager();
        $avatar = new UserAvatar();
        $form = $this->createForm(new UserAvatarType(), $avatar);
        $form->handleRequest($request);
        /** @var User $user */
        $user = $this->getUser();

        if ($user !== null && $form->isSubmitted()) {
            if (!$form->isValid()) {
                return $this->redirect($this->generateUrl('fos_user_profile_edit'));
            }
            $manager->addAvatar($user, $avatar);

            return $this->redirect($this->generateUrl('fos_user_profile_show'));
        }

        return $this->render(
            'SonataUserBundle:Profile:changeAvatar.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * Чистим кеш таким образом, чтобы не пеписывать контроллер сонаты юзер бандла
     * С этим хаком юзер увидит уведомление о том, что письмо с подтверждением выслано
     * @Route("/clear/session", name="clear_session")
     */
    public function clearRegistrationSession() {
        /** @var UserManager $manager */
        $manager = $this->getDomainManager();
        $manager->clearSessionRedirectUrl();
        return new Response('ok');
    }

    /**
     * Форма смены пароля в настройке профиля
     * @Route("/change/password", name="change_password_form")
     */
    public function changePasswordAction()
    {
        /** @var UserManager $manager */
        $manager = $this->getDomainManager();
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $manager->getChangePasswordForm();
        $formHandler = $manager->getChangePasswordFormHandler();

        $process = $formHandler->process($user);
        if ($process) {
            $this->setFlash('fos_user_success', 'change_password.flash.success');

            return new RedirectResponse($this->getRedirectionUrl($user));
        }

        return $this->render(
            'SonataUserBundle:ChangePassword:changePasswordForm.html.twig',
            array('form' => $form->createView())
        );
    }
}
