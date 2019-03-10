<?php
namespace Application\Sonata\UserBundle\Security\Core\User;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\FOSUBUserProvider as BaseFOSUBProvider;
use Symfony\Component\Security\Core\User\UserInterface;
use Application\Sonata\UserBundle\Entity\User;

class MyFOSUBUserProvider extends BaseFOSUBProvider
{
    /**
     * {@inheritDoc}
     */
    public function connect(UserInterface $user, UserResponseInterface $response)
    {
        $property = $this->getProperty($response);
        $username = $response->getUsername();
        //on connect - get the access token and the user ID
        $service = $response->getResourceOwner()->getName();
        $setter = 'set'.ucfirst($service);
        $setter_id = $setter.'Id';
        $setter_token = $setter.'AccessToken';
        $previousUser = $this->userManager->findUserBy(array($property => $username));

        //we "disconnect" previously connected users
        if ( $previousUser !== null ) {
            $previousUser->$setter_id(null);
            $previousUser->$setter_token(null);
            $this->userManager->updateUser($previousUser);
        }
        //we connect current user
        $user->$setter_id($username);
        $user->$setter_token($response->getAccessToken());
        $this->userManager->updateUser($user);
    }
    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $username = $response->getUsername();
        $user = $this->userManager->findUserBy(array($this->getProperty($response) => $username));
        $email = $response->getEmail() ? $response->getEmail() : null;

        if (null === $user && $email) {
            $user = $this->userManager->findUserBy(array('emailCanonical' => $email));
        }

        //when the user is registrating
        if (null === $user) {
            $service = $response->getResourceOwner()->getName();
            $setter = 'set'.ucfirst($service);
            $setter_id = $setter.'Id';
            $setter_token = $setter.'AccessToken';

            // create new user here
            /** @var User $user */
            $user = $this->userManager->createUser();
            $user->$setter_id($username);
            $user->$setter_token($response->getAccessToken());
            //I have set all requested data with the user's username
            //modify here with relevant data
            $user->setUsername($service.'_'.$username);
            if( $service == 'twitter' ) {
                $user->setPicture(
                    str_replace('ava3_normal', 'ava3_400x400', $response->getProfilePicture())
                );
            } else {
                $user->setPicture($response->getProfilePicture());
            }
            $user->setEmail($email);
            if ( $response->getFirstName() || $response->getLastName() ) {
                $user->setFirstname($response->getFirstName());
                $user->setLastname($response->getLastName());
            } elseif( $response->getRealName() ) {
                $user->setFirstname($response->getRealName());
            } elseif( $response->getNickname() ) {
                $user->setFirstname($response->getNickname());
            }

            $user->setPassword('');
            $user->setEnabled(true);
            $this->userManager->updateUser($user);
            return $user;
        }

        //if user exists - check service id -> connect profile
        $service = $response->getResourceOwner()->getName();
        $getter = 'get'.ucfirst($service);
        $getter_id = $getter.'Id';

        if( $user->$getter_id() != $username ) {
            $this->connect($user, $response);
            return $user;
        }

        // update access token
        $serviceName = $response->getResourceOwner()->getName();
        $setter = 'set' . ucfirst($serviceName) . 'AccessToken';
        //update access token
        $user->$setter($response->getAccessToken());
        return $user;
    }
}
