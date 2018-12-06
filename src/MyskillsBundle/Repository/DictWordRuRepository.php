<?php
namespace MyskillsBundle\Repository;

use MyskillsBundle\Entity\DictWordRu;

class DictWordRuRepository extends DictWordRepository
{
    const CACHE_TIMEOUT = 60 * 60; // 1 hour

    /**
     * @param $word
     * @param bool $withCache
     * @return mixed
     */
    public function searchWords($word, $withCache=true) {
        $qb = $this->createQueryBuilder('w');
        $query = $qb
            ->select('w')
            ->where('w.word = :word')
            ->setParameter('word', $word)
            ->getQuery();

        if($withCache) {
            $query->useQueryCache(true)->useResultCache(true, self::CACHE_TIMEOUT, 'ru_word_v2_' . $word);
        }

        return $query->getResult();
    }

    /**
     * @param $word
     * @param bool $withCache
     * @return mixed|null
     */
    public function findOneByWord($word, $withCache=true) {
        $words = $this->searchWords($word, $withCache);
        if(!empty($words)) {
            return current($words);
        }
        return null;
    }
}