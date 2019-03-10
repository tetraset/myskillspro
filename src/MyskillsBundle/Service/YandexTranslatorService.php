<?php
namespace MyskillsBundle\Service;

class YandexTranslatorService
{
    private $apiKey;
    const QUERY_PATTERN = "https://translate.yandex.net/api/v1.5/tr.json/translate?key={apiKey}&lang={from}-{to}&text={text}";

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function translate( $text, $from='en', $to='ru' ) {
        $result =
            file_get_contents(
                strtr(
                    self::QUERY_PATTERN,
                    ['{apiKey}'=>$this->apiKey,'{from}'=>$from,'{to}'=>$to,'{text}'=>urlencode($text)]
                )
            );
        $result = json_decode($result, true);

        return empty($result['text']) ? null : implode(', ', $result['text']);
    }
}
