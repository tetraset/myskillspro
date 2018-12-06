<?php
namespace MyskillsBundle\DomainManager\Subscription;

use MyskillsBundle\DomainManager\BaseDomainManager;
use MyskillsBundle\Entity\Payment;
use MyskillsBundle\Entity\Promo;
use MyskillsBundle\Exception\InvalidArgumentException;
use MyskillsBundle\Repository\PaymentRepository;
use MyskillsBundle\Repository\PromoRepository;
use MyskillsBundle\Service\RobokassaClientService;
use Doctrine\Bundle\DoctrineBundle\Registry;

class SubscriptionManager extends BaseDomainManager
{
    /** @var RobokassaClientService */
    private $robokassaClient;

    /** @var Registry */
    private $doctrine;

    /** @var PromoRepository */
    private $promoRepository;

    public function __construct(
        PaymentRepository $baseRepository,
        PromoRepository $promoRepository,
        RobokassaClientService $robokassaClientService,
        $doctrine
    )
    {
        parent::__construct($baseRepository);
        $this->robokassaClient = $robokassaClientService;
        $this->doctrine = $doctrine;
        $this->promoRepository = $promoRepository;
    }

    /**
     * @param $term
     */
    public function addRedirectToTerm($term) {
        $this->getSession()
             ->getFlashBag()
            ->add(
                'redirect',
                '/subscription/'.$term
            );
    }

    /**
     * @return array
     */
    public function getPayMethods() {
        return $this->robokassaClient->getPayMethods();
    }

    /**
     * @param $idUser
     * @return Promo
     */
    public function getPromoCode($idUser) {
        return $this->promoRepository->findOneBy(['isActive'=>true, 'isPayment'=>false, 'idUser'=>$idUser]);
    }

    /**
     * @param $idUser
     * @param $promoCode
     * @return Promo
     */
    public function activatePromoCode($idUser, $promoCode) {
        $em = $this->doctrine->getManager();
        /** @var Promo $promo */
        $promo = $this->promoRepository->findOneBy(['isActive'=>true, 'isPayment'=>false, 'idUser'=>0, 'code'=>$promoCode]);
        if(!empty($promo)) {
            $promo->setIdUser($idUser);
            $em->flush();
        }
        return $promo;
    }

    /**
     * @param $idUser
     * @return Promo
     */
    public function finishPromo($idUser) {
        $em = $this->doctrine->getManager();
        /** @var Promo $promo */
        $promo = $this->getPromoCode($idUser);
        if(!empty($promo)) {
            $promo->setIsPayment(true);
            $em->flush();
        }
        return $promo;
    }

    /**
     * @param $price
     * @param $userId
     * @param $IncCurrLabel
     * @param $term
     * @param bool $is_test
     * @return array
     */
    public function getPayFormsParams($price, $userId, $IncCurrLabel, $term, $is_test=false) {
        $m_id = $userId.'_'.$term.'_'.time();
        $my_hash = md5($userId.'_'.$term.'_'.$this->robokassaClient->getPass1($is_test));
        $m_desc = "Подписка на следующее количество месяцев: " . $term;

        return $this->robokassaClient->getPayFormsParams(
            $price,
            $m_id,
            $my_hash,
            $IncCurrLabel,
            $m_desc,
            $term,
            $is_test
        );
    }
    
    public function checkPaymentStatus($out_summ, $shp_item, $crc, $descId, $inv_id, $pay_hash, $is_test=false) {
        $crc = strtoupper($crc);

        $mrh_pass2 = $this->robokassaClient->getPass2($is_test);

        $my_crc = strtoupper(md5("$out_summ:$inv_id:$mrh_pass2:Shp_hash=$pay_hash:Shp_id=$descId:Shp_item=$shp_item"));

        // проверка корректности подписи
        // check signature
        if ($my_crc != $crc)
        {
            throw new InvalidArgumentException("incorrect signature");
        }

        $elements = explode('_', $descId);
        $userId = (int)$elements[0];
        $months = (int)$elements[1];
        $my_hash = md5($userId.'_'.$months.'_'.$this->robokassaClient->getPass1($is_test));

        if($my_hash != $pay_hash) {
            throw new InvalidArgumentException("incorrect my hash");
        }
    }

    /**
     * @param $userId
     * @param $operationPs
     * @param $mOrderid
     * @param $mAmount
     * @param $mCurr
     * @param $mDesc
     * @param $mStatus
     * @param $subscriptionTerm
     * @param bool $isTest
     */
    public function addPayment(
        $userId,
        $operationPs,
        $mOrderid,
        $mAmount,
        $mCurr,
        $mDesc,
        $mStatus,
        $subscriptionTerm,
        $isTest=false
    ) {
        $em = $this->doctrine->getManager();

        $payment = new Payment(
            $userId,
            $operationPs,
            new \DateTime(),
            new \DateTime(),
            $mOrderid,
            $mAmount,
            $mCurr,
            $mDesc,
            $mStatus,
            $subscriptionTerm,
            $isTest
        );

        $promo = $this->finishPromo($userId);
        if(!empty($promo)) {
            $payment->setIdPromo($promo->getId());
        }

        $em->persist($payment);
        $em->flush();

        if(!$isTest) {
            $user = $this->doctrine->getRepository('ApplicationSonataUserBundle:User')->find($userId);
            if($user !== null) {
                $user->setSubscriptionMonths($subscriptionTerm);
                $user->setSubscriptionStart(new \DateTime());
                $em->persist($user);
                $em->flush();
            }
        }
    }
}
