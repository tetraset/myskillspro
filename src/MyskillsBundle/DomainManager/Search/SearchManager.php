<?php
namespace MyskillsBundle\DomainManager\Search;

use MyskillsBundle\DomainManager\BaseDomainManager;
use Doctrine\ORM\EntityRepository;
use IAkumaI\SphinxsearchBundle\Search\Sphinxsearch;

class SearchManager extends BaseDomainManager
{
    private $sphinxService;

    public function __construct(
        Sphinxsearch $sphinxService
    ) {
        parent::__construct(null);
        $this->sphinxService = $sphinxService;
    }

    /**
     * Защита от воспроизведения клипа в обход поиска по клипам
     * return void
     */
    public function setSessionVisit() {
        $this->getSession()
             ->set('visit', true);
    }

    public function search($s, $skip, $limit, $index, $weights=[], $matchType=SPH_MATCH_EXTENDED2) {
        $total = 0;
        $results_arr = 0;
        $is_more = false;

        if ( $s ) {
            $this->sphinxService->setLimits($skip, $limit);
            $this->sphinxService->SetRankingMode(SPH_MATCH_EXTENDED2);
            $this->sphinxService->SetMatchMode($matchType);
            $this->sphinxService->SetSortMode(SPH_SORT_RELEVANCE);
            $s = "($s | *$s*)";

            if(!empty($weights)) {
                $this->sphinxService
                    ->getClient()
                    ->SetFieldWeights($weights);
            }

            // ToDo: cache by $search_request
            $results = $this->sphinxService->searchEx($s, $index);
            $is_more = $results['total_found'] > $skip + $limit;
            if ( !$results['total_found'] || !isset($results['matches']) ) {
                $results['matches'] = array();
            } else {
                $results['matches'] = array_map(
                    function($r) {
                        return $r['entity'];
                    },
                    $results['matches']
                );
            }
            $total = $results['total_found'];
            $results_arr = $results['matches'];
            $results_arr = array_filter(
                $results_arr,
                function ($s) {
                    return $s !== null;
                }
            );
        }

        return [
            'items' => $results_arr,
            'total' => $total,
            'is_more' => $is_more
        ];
    }
}
