<?php
namespace MyskillsBundle\Entity;
use Symfony\Component\Validator\Constraints as Assert;

class UserAvatar
{
    /**
     * @Assert\NotBlank(message="Загрузите свой новый аватар")
     * @Assert\File(mimeTypes={ "image/gif", "image/jpeg", "image/png" })
     */
    private $avatar;

    /**
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param string $avatar
     * @return UserAvatar
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
        return $this;
    }
}