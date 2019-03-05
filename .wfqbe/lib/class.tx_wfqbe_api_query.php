<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2010 Mauro Lorenzutti (Webformat srl) (mauro.lorenzutti@webformat.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Set of useful functions
 *
 * @author    Mauro Lorenzutti <mauro.lorenzutti@webformat.com>
 */


require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "lib/class.tx_wfqbe_api_xml2array.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "lib/class.tx_wfqbe_utils.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "lib/class.tx_wfqbe_connect.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "pi1/class.tx_wfqbe_results.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "tx_wfqbe_query_query/class.tx_wfqbe_queryform_generator.php");


class tx_wfqbe_api_query
{
    var $extKey = 'wfqbe';    // The extension key.

    var $piVars = array();
    var $query = '';
    var $connection = false;
    var $conf = array();
    var $cObj = '';


    /**
     * Function used to init the API
     * @param $query    The wfqbe record uid
     * @param $cObj        A cObj object
     * @param $piVars    The piVars array
     * @return boolean    Connection status
     */
    function init($query, $cObj = "", $piVars = "")
    {
        if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($query))
            return false;

        $this->cObj = $cObj;
        if (!is_object($this->cObj)) {
            $this->cObj = $cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
        }

        $result = false;
        if (is_array($piVars))
            $this->piVars = $piVars;
        else
            $this->piVars = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_wfqbe_pi1');

        // Finding the query record into the database
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_wfqbe_query', 'uid=' . $query);
        if ($res !== false && $GLOBALS['TYPO3_DB']->sql_num_rows($res) == 1) {
            $this->query = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
            $this->loadTSConfig($this->query['pid']);
            $result = $this->connect();
        }

        return $result;
    }


    /**
     * Function used to connect to DB
     * @return boolean    Connection status
     */
    private function connect()
    {
        $where = 'tx_wfqbe_query.uid=' . intval($this->query['uid']) . ' AND ';

        // Create the connection to the remote DB
        $CONN = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_connect");
        $connection_obj = $CONN->connect($where);

        if ($connection_obj !== false) {
            $this->connection = $connection_obj["conn"];
            return true;
        }

        return false;
    }


    /**
     * Function used to execute the query
     * @return array    Rows
     */
    public function execQuery()
    {
        $rows = array();
        if (!$this->connection)
            return $rows;

        if ($this->query['type'] == "select") {
            // SELECT
            $SELECT = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_results");
            $SELECT->main($this->conf, $this->cObj, $this);
            $ris = $SELECT->getResultQuery($this->query, $this->connection);

            if ($ris === false || $ris->RecordCount() == 0) {
                // TODO: Print an error message

            } else {
                $rows = $ris->GetRows();
            }

        }

        return $rows;
    }


    /**
     * Retrieves the configuration (TS setup) of the page with the PID provided
     * as the parameter $pageId.
     */
    private function &loadTSConfig($pageId)
    {

        $template = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Core\TypoScript\TemplateService');
        // Disables the logging of time-performance information.
        $template->tt_track = 0;
        $template->init();

        // Gets the root line.
        $sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Frontend\Page\PageRepository');
        // Finds the selected page in the BE exactly as in t3lib_SCbase::init().
        $rootline = $sys_page->getRootLine($pageId);

        // Generates the constants/config and hierarchy info for the template.
        $template->runThroughTemplates($rootline, 0);
        $template->generateConfig();

        if (isset($template->setup['plugin.']['tx_wfqbe_pi1.'])) {
            $result = $template->setup['plugin.']['tx_wfqbe_pi1.'];
        } else {
            $result = array();
        }

        $this->conf = $result;
    }


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/lib/class.tx_wfqbe_api_query.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/lib/class.tx_wfqbe_api_query.php']);
}


