<?php
namespace MyskillsBundle\Service;

class SkyEngTranslatorService
{
    const QUERY_PATTERN = "http://dictionary.skyeng.ru/api/public/v1/words/search?search={text}";

    public function translate($text) {
        $result =
            file_get_contents(
                strtr(
                    self::QUERY_PATTERN,
                    ['{text}'=>urlencode($text)]
                )
            );
        $result = json_decode($result, true);

        if (!empty($result)) {
            $result = current($result);
        }

        return empty($result['meanings']) ? [] : $result['meanings'];
    }
}
