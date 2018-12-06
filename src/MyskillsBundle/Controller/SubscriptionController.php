<?php
namespace MyskillsBundle\Controller;

use MyskillsBundle\DomainManager\Subscription\SubscriptionManager;
use MyskillsBundle\Entity\Promo;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * @Route(service="subscription.controller")
 */
class SubscriptionController extends BaseController
{
    const OLD_PRICES = [
        1=>299,
        3=>749,
        6=>1299,
        12=>2299
    ];
    const PRICES = [
        1=>199,
        3=>499,
        6=>899,
        12=>1490
    ];

    /**
     * @Route("/subscription", name="subscription")
     */
    public function subscriptionAction()
    {
        return $this->render('MyskillsBundle:Video:subscription.html.twig', array('prices'=>self::PRICES, 'old_prices'=>self::OLD_PRICES));
    }

    /**
     * @Route("/subscription/{term}", name="subscription_term_robo")
     * @Method({"POST", "GET"})
     */
    public function roboSubscriptionTermAction($term, Request $request)
    {
        /** @var SubscriptionManager $manager */
        $manager = $this->getDomainManager();
        $user = $this->getUser();
        $promoCode = $request->request->get('promo_code');

        if(!in_array($term, [1,3,6,12])) {
            return $this->redirect($this->generateUrl('subscription'));
        }

        if($user === null) {
            $manager->addRedirectToTerm($term);
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

        /** @var Promo $promo */
        $promo = $manager->getPromoCode($user->getId());
        if(!empty($promoCode) && empty($promo)) {
            $promo = $manager->activatePromoCode($user->getId(), $promoCode);
        }

        $price = number_format(self::PRICES[$term]*(empty($promo) ? 1 : (100-$promo->getDiscountPercent())/100), 2, '.', '');

        return $this->render('MyskillsBundle:Video:payment_robo.html.twig', array(
            'term'=>$term,
            'prices'=>self::PRICES,
            'price' => $price,
            'payMethods' => $manager->getPayMethods(),
            'promo' => $promo
        ));
    }

    /**
     * @Route("/subscription/{term}/purchase", name="subscription_term_robo_finish")
     */
    public function roboPurchaseAction($term, Request $request)
    {
        /** @var SubscriptionManager $manager */
        $manager = $this->getDomainManager();
        $user = $this->getUser();

        if(!in_array($term, [1,3,6,12])) {
            return $this->redirect($this->generateUrl('subscription'));
        }

        if($user === null) {
            $manager->addRedirectToTerm($term);
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $price = empty($request->query->get('tetra_price')) ? self::PRICES[$term] : (int)$request->query->get('tetra_price');
        /** @var Promo $promo */
        $promo = $manager->getPromoCode($user->getId());
        if(!empty($promo)) {
            $price = $price*((100-$promo->getDiscountPercent()) / 100);
        }
        
        $payFormParams = $manager->getPayFormsParams(
            $price,
            $user->getId(),
            $request->query->get('IncCurrLabel'),
            $term,
            !empty($request->query->get('tetra_test'))
        );
        $params = array_merge(
            $payFormParams,
            [
                'term'=>$term,
                'prices'=>self::PRICES,
            ]
        );

        return $this->render('MyskillsBundle:Video:payment_pay.html.twig', $params);
    }

    /**
     * @Route("/robo/status/{status}", name="robo_status_payment")
     */
    public function roboStatusPaymentAction($status) {
        return $this->render('MyskillsBundle:Video:payment_'.$status.'.html.twig');
    }

    /**
     * @Route("/robo/status", name="robo_status")
     */
    public function roboStatusAction()
    {
        /** @var SubscriptionManager $manager */
        $manager = $this->getDomainManager();
        if (isset($_POST['InvId']) && isset($_POST['SignatureValue']))
        {
            // чтение параметров
            // read parameters
            $out_summ = $_REQUEST["OutSum"];
            $shp_item = $_REQUEST["Shp_item"];
            $crc = $_REQUEST["SignatureValue"];
            $descId = $_REQUEST["Shp_id"];
            $inv_id = $_REQUEST["InvId"];
            $pay_hash = $_REQUEST["Shp_hash"];
            $is_test = !empty($_REQUEST["IsTest"]);

            try {
                $manager->checkPaymentStatus($out_summ, $shp_item, $crc, $descId, $inv_id, $pay_hash, $is_test);

                $elements = explode('_', $descId);
                $userId = (int)$elements[0];
                $months = (int)$elements[1];
                $desc = "Подписка на следующее количество месяцев: " . $months;
                $manager->addPayment($userId, 'robokassa', $inv_id ? $inv_id : $descId, $out_summ, 'RUB', $desc, 'success', $months, $is_test);

                return new Response("OK$inv_id\n");
            }catch(\Exception $e) {
                $this->getLogger() && $this->getLogger()->addError($e->getMessage());
                return new Response("error");
            }
        }
    }
}
