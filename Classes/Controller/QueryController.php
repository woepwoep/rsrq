<?php
namespace RedSeadog\Wfqbe\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use RedSeadog\Wfqbe\Service\FlexformInfoService;
use RedSeadog\Wfqbe\Service\PluginService;
use RedSeadog\Wfqbe\Service\SqlService;

use TYPO3\CMS\Core\Pagination\ArrayPaginator;

use TYPO3\CMS\Core\Context\Context;
/**
 * QueryController
 *
 * @author Ronald Wopereis <woepwoep@gmail.com>
 */
class QueryController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @var $ffdata
     */
    protected $ffdata;

    /**
     * @var \RedSeadog\Wfqbe\Service\PluginService
     */
    protected $pluginService;

    /**
     * @var query
     */
    protected $query;

    /**
     * __construct() ... retrieve both the plugin settings and the flexform info
     */
    public function __construct()
    {
        $this->pluginService = new PluginService('tx_wfqbe');
        $flexformInfoService = new FlexformInfoService();

        // retrieve the query from the flexform ...
        $this->query = $flexformInfoService->getQuery();

        // retrieve other ffdata
        $this->ffdata = $flexformInfoService->getData();
    }

    /**
     * Filter the results of a query
     */
    public function filterAction()
    {
        return $this->listAction();
    }

    /**
     * List the results of a query
     */
    public function detailFormAction()
    {
        return $this->listAction();
    }

    /**
     * List the results of a query
     */
    public function listAction()
    {
        // search the where-clause for filter arguments ###filter###
        $pattern = '/###[^#]*###/';
        $subject = $this->query;
        preg_match_all($pattern, $subject, $matches);
        $markerFields = array_unique($matches[0]);

        // replace the ### with empty string
        $search = '###';
        $replace = '';
        $subject = $markerFields;
        $markerFields = str_replace($search, $replace, $subject);

        // process RSRQ_* arguments
        $args = $this->request->getArguments();
        // DebugUtility::debug($args,'getArguments in listAction');
        foreach ($args as $rsrq_name => $rsrq_value) {
            $rest = substr($rsrq_name, 0, 5);
            if ($rest === 'RSRQ_') {
                $rsrq_names[substr($rsrq_name, 5)] = $rsrq_value;
            }
        }
        // DebugUtility::debug($rsrq_names,'rsrq_names in listAction');

        // the RSRQ_* arguments are substituted in the raw query
        if (!empty($rsrq_names)) {
            foreach ($rsrq_names as $rsrq_name => $rsrq_value) {
                $replace = '###' . $rsrq_name . '###';
                $nieuw = str_replace($replace, $rsrq_value, $this->query);
                $this->query = $nieuw;
            };
        }

        // insert right after the WHERE
        $flexformInfoService = new FlexformInfoService();
        $andWhere = $flexformInfoService->andWhere($rsrq_names);

        // replace the special marker ###filterFields### with andWhere
        $string = $this->query;
        $pattern = '/###filterFields###/';
        $replacement = $andWhere;
        $sjaak = preg_replace($pattern, $replacement, $string);
        $this->query = $sjaak;

		// replace the ###sortField### and ###sortOrder### markers
        $sortObject = $flexformInfoService->getSortObject($rsrq_names);
        $string = $this->query;
        $pattern = '/###orderBy###/';
        $replacement = $sortObject['statement'];
        $sjaak = preg_replace($pattern, $replacement, $string);
        $this->query = $sjaak;

        // remove any other marker from the query
        $string = $this->query;
        $pattern = '/###[^#]+###/';
        $replacement = '';
        $sjaak = preg_replace($pattern, $replacement, $string);
        $this->query = $sjaak;

        // special cases
        $this->findAndReplace(
            'TSFE:fe_user\|user\|',
            $GLOBALS['TSFE']->fe_user->user
        );
        $this->findAndReplace(
            'cObj:',
            $this->configurationManager->getContentObject()->data
        );

        // execute the query and get the result set (rows)
        if ($this->ffdata['debug']) {
			DebugUtility::debug($this->query, 'this->query in listAction');
		}
        $sqlService = new SqlService($this->query);

        // use the template from the Flexform if there is one
        if (!empty($this->ffdata['templateFile'])) {
            $templateFile = GeneralUtility::getFileAbsFilename(
                $this->ffdata['templateFile']
            );
            $this->view->setTemplatePathAndFilename($templateFile);
        }

        // execute the query
        $rows = $sqlService->getRows();
        $columnNames = $sqlService->getColumnNamesFromResultRows($rows);
        $newColumns = $flexformInfoService->mergeFieldTypes($columnNames);
        $filterFieldList = $flexformInfoService->getFilterFieldList();
        $chartFieldList = $flexformInfoService->getChartFieldList();

        /* pagination */
        $itemsToBePaginated = $rows;
        $itemsPerPage = $this->ffdata['recordsForPage'];
        $currentPageNumber = 1;
        $parameter = 'pageNo';
        if ($this->request->hasArgument($parameter)) {
            $currentPageNumber = $this->request->getArgument($parameter);
        }

        $paginator = new ArrayPaginator(
            $itemsToBePaginated,
            $currentPageNumber,
            $itemsPerPage
        );
		$numberOfPages = $paginator->getNumberOfPages();

		$slidingPages = $this->ffdata['slidingPages'];

		$slidingFrom = $currentPageNumber - floor(($slidingPages - 1)/2);
		if ($slidingFrom < 1) $slidingFrom = 1;

		$slidingTo = $slidingFrom + $slidingPages - 1;
		if ($slidingTo > $numberOfPages ) $slidingTo = $numberOfPages;
		if ($slidingTo < 1) $slidingTo = 1;

		$prevPage = $currentPageNumber - 1;
		if ($prevPage < 1) $prevPage = 1;

		$nextPage = $currentPageNumber + 1;
		if ($nextPage > $numberOfPages) $nextPage = $numberOfPages;

        $pageInfo = [
            'numberOfPages' => $numberOfPages,
            'currentPageNumber' => $currentPageNumber,
			'slidingPages' => $slidingPages,
			'fromMinusOne' => $slidingFrom - 1,
			'toPlusOne' => $slidingTo + 1,
			'prevPage' => $prevPage,
			'nextPage' => $nextPage,
            'rowsPerPage' => $itemsPerPage,
            'totalAmountOfRows' => sizeof($rows),
        ];

        // assign the results in a view for fluid Query/List.html
        $this->view->assignMultiple([
            'settings' => $this->pluginService->getSettings(),
            'flexformdata' => $this->ffdata,
            'query' => $this->query,
            'columnNames' => $newColumns,
            'rows' => $paginator->getPaginatedItems(),
            'request' => $this->request,
            'user' => $GLOBALS["TSFE"]->fe_user->user,
            'cObject' => $this->configurationManager->getContentObject()->data,
            'filterFields' => $markerFields,
            'chartFields' => $chartFieldList,
            'rsrq_names' => $rsrq_names,
            'filterFieldList' => $filterFieldList,
            'pageInfo' => $pageInfo,
			'sortObject' => $sortObject,
        ]);
    }

    protected function findAndReplace($find, $replace)
    {
        if (is_array($replace)) {
            foreach ($replace as $key => $value) {
                if (!is_array($value)) {
                    $sjaak = preg_replace(
                        '/' . $find . '(' . $key . ')/',
                        $value,
                        $this->query
                    );
                    $this->query = $sjaak;
                }
            };
        }
    }
}
