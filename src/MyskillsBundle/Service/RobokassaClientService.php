<?php
namespace MyskillsBundle\Service;

class RobokassaClientService
{
    private $pass1;
    private $pass2;
    private $pass1_test;
    private $pass2_test;
    private $shop_id;

    const PAY_METHODS_GET_API = "https://auth.robokassa.ru/Merchant/WebService/Service.asmx/GetCurrencies?MerchantLogin=anisub&Language=ru";
    const REAL_AMOUNT_COUNT_API = "https://auth.robokassa.ru/Merchant/WebService/Service.asmx/CalcOutSumm?MerchantLogin=anisub&IncCurrLabel={currLabel}&IncSum={sum}";

    /**
     * RobokassaClientService constructor.
     * @param string $pass1
     * @param string $pass2
     * @param string $pass1_test
     * @param string $pass2_test
     * @param string $shop_id
     */
    public function __construct($pass1, $pass2, $pass1_test, $pass2_test, $shop_id)
    {
        $this->pass1 = $pass1;
        $this->pass2 = $pass2;
        $this->pass1_test = $pass1_test;
        $this->pass2_test = $pass2_test;
        $this->shop_id = $shop_id;
    }

    /**
     * @return array
     */
    public function getPayMethods() {
        $payMethodsArr = [];

        while(empty($payMethodsContent)) {
            $payMethodsContent = file_get_contents(self::PAY_METHODS_GET_API);
        }
        $payMethodXml = simplexml_load_string($payMethodsContent);
        $payGroups = $payMethodXml->Groups->Group;
        foreach($payGroups as $payGroup) {
            $data = $payGroup->attributes();
            $titleGroup = $data->Description;
            $codeGroup = $data->Code;
            $data = $payGroup->Items->Currency->attributes();
            $titleMethod = $data->Name;
            $code = $data->Alias;
            $payMethodsArr[] = [
                'title'=> $codeGroup == "".$code ? $titleGroup : $titleGroup.': '.$titleMethod,
                'code' => $code
            ];
        }

        return $payMethodsArr;
    }

    /**
     * @param $price
     * @param $m_id
     * @param $my_hash
     * @param $IncCurrLabel
     * @param $m_desc
     * @param $term
     * @param bool $is_test
     * @return array
     */
    public function getPayFormsParams($price, $m_id, $my_hash, $IncCurrLabel, $m_desc, $term, $is_test=false) {
        $m_shop = $this->shop_id;
        $m_amount = number_format($price, 2, '.', '');
        $m_curr = 'RUB';
        $pass = $is_test ? $this->pass1_test : $this->pass1;
        $realAmountContent = null;

        while(empty($realAmountContent)) {
            $realAmountContent = file_get_contents(
                strtr(self::REAL_AMOUNT_COUNT_API, ['{currLabel}'=>$IncCurrLabel, '{sum}'=>$m_amount])
            );
        }
        $realAmountXml = simplexml_load_string($realAmountContent);
        $m_amount = empty($realAmountXml->OutSum) ? $m_amount : $realAmountXml->OutSum;

        // формирование подписи
        // generate signature
        $sign  = md5("$m_shop:$m_amount::$pass:Shp_hash=$my_hash:Shp_id=$m_id:Shp_item=$term");

        return [
            'm_shop' => $m_shop,
            'm_amount' => $m_amount,
            'm_curr' => $m_curr,
            'm_desc' => $m_desc,
            'm_id' => $m_id,
            'sign' => $sign,
            'my_hash' => $my_hash,
            'IncCurrLabel' => $IncCurrLabel
        ];
    }

    /**
     * @return string
     */
    public function getPass1($is_test=false)
    {
        return $is_test ? $this->pass1_test : $this->pass1;
    }

    /**
     * @return string
     */
    public function getPass2($is_test=false)
    {
        return $is_test ? $this->pass2_test : $this->pass2;
    }
}
