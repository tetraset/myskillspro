<?php
namespace MyskillsBundle\DomainManager\User;

use Application\Sonata\UserBundle\Entity\User;
use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\DomainManager\Game\GameManager;
use MyskillsBundle\Entity\UserAvatar;
use Sonata\UserBundle\Entity\UserManager as FOSUserManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UserManager extends BaseDomainManager
{
    /** @var FOSUserManager */
    private $userManager;

    private $avatarDirectory;

    private $webDirectory;

    private $container;

    /**
     * @var GameManager
     */
    private $gameManager;

    public function __construct(
        FOSUserManager $userManager,
        GameManager $gameManager,
        $avatarDirectory,
        $webDirectory,
        $container
    )
    {
        parent::__construct(null);
        $this->userManager = $userManager;
        $this->avatarDirectory = $avatarDirectory;
        $this->webDirectory = $webDirectory;
        $this->container = $container;
        $this->gameManager = $gameManager;
    }

    /**
     * @param $user
     * @param UserAvatar $avatar
     */
    public function addAvatar($user, UserAvatar $avatar) {
        // $file stores the uploaded file
        /** @var UploadedFile $file */
        $file = $avatar->getAvatar();

        // Generate a unique name for the file before saving it
        $fileName = md5(uniqid()) . '.' . $file->guessExtension();

        // Move the file to the directory where brochures are stored
        $newAvatar = $file->move(
            $this->avatarDirectory,
            $fileName
        );
        if($newAvatar) {
            $user->setPicture(str_replace($this->webDirectory, '', $newAvatar));
            $user->setIsUpdatedPicture(true);
            $this->userManager->updateUser($user);
        }
    }

    public function clearSessionRedirectUrl() {
        $this->getSession()->remove('sonata_user_redirect_url');
    }

    /**
     * @return mixed
     */
    public function getChangePasswordForm()
    {
        return $this->container->get('fos_user.change_password.form');
    }

    /**
     * @return mixed
     */
    public function getChangePasswordFormHandler()
    {
        return $this->container->get('fos_user.change_password.form.handler');
    }

    public function saveUser(User $user) {
        $this->userManager->updateUser($user);
    }

    public function getById($id)
    {
        return $this->userManager->find($id);
    }
}
