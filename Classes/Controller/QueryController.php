<?php

namespace RedSeadog\Rsrq\Controller;

use RedSeadog\Rsrq\Domain\Repository\QueryRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class QueryController
 *
 * @package RedSeadog\Rsrq\Controller
 */
class QueryController extends ActionController
{

    /**
     * @var QueryRepository
     */
    private $queryRepository;


    /**
     * Inject the query repository
     *
     * @param \RedSeadog\Rsrq\Domain\Repository\QueryRepository $queryRepository
     */
    public function injectQueryRepository(QueryRepository $queryRepository)
    {
        $this->queryRepository = $queryRepository;
    }

    /**
     * List Action
     *
     * @return void
     */
    public function listAction()
    {
        $queries = $this->queryRepository->findAll();
        $this->view->assign('queries', $queries);
    }
}
