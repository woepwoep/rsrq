<?php
/*
 *  Copyright notice
 *
 *  (c) 2006-2017 WEBFORMAT
 *
 *  All rights reserved
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
/**
 * Plugin 'DB Integration' for the 'wfqbe' extension.
 *
 */

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "lib/class.tx_wfqbe_api_xml2array.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "lib/class.tx_wfqbe_utils.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "lib/class.tx_wfqbe_connect.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "tx_wfqbe_query_query/class.tx_wfqbe_queryform_generator.php");

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "pi1/class.tx_wfqbe_results.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "pi1/class.tx_wfqbe_search.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "pi1/class.tx_wfqbe_insert.php");

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('adodb') . 'adodb/adodb.inc.php');


class tx_wfqbe_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    var $prefixId = 'tx_wfqbe_pi1';        // Same as class name
    var $scriptRelPath = 'pi1/class.tx_wfqbe_pi1.php';    // Path to this script relative to the extension dir.
    var $extKey = 'wfqbe';    // The extension key.

    var $original_row = ''; // This parameter is used to save the original query record when using the add_new option
    var $insertBlocks = ''; // This parameter contains the original insert form while using insert wizards

    var $beMode = false;
    var $beObj = null;


    /**
     * The main method of the PlugIn
     *
     * @param    string $content : The PlugIn content
     * @param    array $conf : The PlugIn configuration
     * @return    The content that is displayed on the website
     */

    /* Questa funzione ha i seguenti compiti :
        -Estrarre i parametri host,tipo di DBMS,username,password, nome del database e la query(che si vuole fare) dalle deue tabelle create
       (credentials e query).
       -Creare un tipo di connessione diversa,tramite ADODB,in base al tipo di DBMS al quale ci si vuole connettere.
       -Eseguire la query , estrarre i risultati e visualizzare a frontend la tabella risultante.
       -Viene utilizzato un template (template.html) per poter visualizzare la tabella in formato html a frontend.

    */

    function main($content, $conf)
    {
        // Initialize extension configuration
        $this->conf = $conf;
        $this->initFF();

        // Hook that can be used to customize the extension configuration programmatically
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['customizeConfiguration'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['customizeConfiguration'] as $_classRef) {
                $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                $_params = array('conf' => $this->conf);
                $this->conf = $_procObj->customizeConfiguration($_params, $this);
            }
        }


        if ($this->conf['recordsForPage'] != '')
            $this->conf['ff_data']['recordsForPage'] = $this->conf['recordsForPage'];

        // Disable output if required parameter is not set
        if ($this->conf['ff_data']['parameterCheck'] != '') {
            $check = explode('|', $this->conf['ff_data']['parameterCheck']);
            if (is_array($check)) {
                $var = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($check[0]);
                if ($var == '')
                    return '';
                if (count($check) > 1) {
                    for ($i = 1; $i < count($check); $i++) {
                        $var = $var[$check[$i]];
                        if ($var == '')
                            return '';
                    }
                }
            }
        }

        //controllo se è stato definito un css. Se si lo utilizzo altrimenti prendo quello di default
        if ($this->conf['style'] != "") {
            $css = $this->cObj->fileResource($this->conf["style"]);
            $GLOBALS["TSFE"]->setCSS($this->extKey, $css);
        }

        $js = $this->cObj->fileResource('EXT:wfqbe/res/functions.js');
        $GLOBALS["TSFE"]->setJS($this->extKey, $js);
        unset($css, $js);

        $this->pi_setPiVarDefaults();
        $this->pi_loadLL();
        $this->pi_USER_INT_obj = 1;    // Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!

        /**
         * Instantiate the xajax object and configure it
         */
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1) {
            // Include xaJax
            require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('xajax') . 'class.tx_xajax.php');
            // Make the instance
            $this->xajax = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_xajax');
            // nothing to set, we send to the same URI
            # $this->xajax->setRequestURI('xxx');
            // Decode form vars from utf8 ???
            $this->xajax->decodeUTF8InputOn();
            // Encode of the response to utf-8 ???
            $this->xajax->setCharEncoding('utf-8');
            // To prevent conflicts, prepend the extension prefix
            $this->xajax->setWrapperPrefix($this->prefixId);
            // Do you wnat messages in the status bar?
            $this->xajax->statusMessagesOn();
            // Turn only on during testing
            //$this->xajax->debugOn();
            // Register the names of the PHP functions you want to be able to call through xajax
            // $xajax->registerFunction(array('functionNameInJavascript', &$object, 'methodName'));

            //$this->xajax->registerFunction(array('processFormData', &$this, 'processAjax'));
            $this->xajax->registerFunction(array('processResultsData', &$this, 'processResults'));
            $this->xajax->registerFunction(array('processSearchData', &$this, 'processSearch'));
            $this->xajax->registerFunction(array('processInsertData', &$this, 'processInsert'));
            //$this->xajax->registerFunction(array('processInsertUpdateForm', &$this, 'processInsert'));

            // If this is an xajax request, call our registered function, send output and exit
            $this->xajax->processRequests();
            // Else create javascript and add it to the header output
            $GLOBALS['TSFE']->additionalHeaderData[$this->prefixId] = $this->xajax->getJavascript(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('xajax'));
        }
        // The form goes here
        $form_built = false;
        $content .= $this->sGetForm($form_built);

        // The result box goes here
        if (!\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_wfqbe_pi1') && $form_built) {
            // We make an empty result box on the first call to send our xajax responses to
            //$content .= '<div id="wfqbe_results"></div>';
        } else {
            // This fallback will only be used if JavaScript doesn't work
            // Responses of xajax exit before this
            $content .= $this->sGetFormResult();
        } // if (!\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('xajax'))

        $content = $this->clearInput($content);

        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('type') == '181')
            return $content;
        elseif ($this->conf['wrapInBaseClass'] == 1)
            return $this->pi_wrapInBaseClass($content);
        else
            return $content;
    }


    /**
     * This function is used to initialize the FlexForm data from the plugin and to overwrite the TS configuration
     */
    function initFF()
    {
        $this->pi_initPIflexForm(); // Init and get the flexform data of the plugin
        $this->lConf = array(); // Setup our storage array...
        // Assign the flexform data to a local variable for easier access
        $piFlexForm = $this->cObj->data['pi_flexform'];

        // Traverse the entire array based on the language...
        // and assign each configuration option to $this->lConf array...
        if (!is_array($piFlexForm['data']))
            return;

        foreach ($piFlexForm['data'] as $sheet => $data)
            foreach ($data as $lang => $value)
                foreach ($value as $key => $val)
                    $this->conf['ff_data'][$key] = $this->pi_getFFvalue($piFlexForm, $key, $sheet);

        if ($this->conf['ff_data']['templateFile'] != '') {
            if (strpos($this->conf['ff_data']['templateFile'], 'media:') !== false) {
                $file = explode(':', $this->conf['ff_data']['templateFile']);
                if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($file[1])) {
                    $resDam = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_dam', 'uid=' . $file[1] . $this->cObj->enableFields('tx_dam'));
                    if ($resDam !== false && $GLOBALS['TYPO3_DB']->sql_num_rows($resDam) == 1) {
                        $rowDam = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resDam);
                        $this->conf['template'] = $rowDam['file_path'] . $rowDam['file_name'];
                    }
                }
            } else {
                $this->conf['template'] = $this->conf['ff_data']['templateFile'];
            }
        }
        if ($this->conf['ff_data']['debugQuery'] != '')
            $this->conf['debugQuery'] = $this->conf['ff_data']['debugQuery'];
        if ($this->conf['ff_data']['customTemplate'] != '')
            $this->conf['defLayout'] = $this->conf['ff_data']['customTemplate'];
        if ($this->conf['ff_data']['pageConfirmation'] != '')
            $this->conf['insert.']['pageConfirmation'] = $this->conf['ff_data']['pageConfirmation'];
        if ($this->conf['ff_data']['div_id'] == '')
            $this->conf['ff_data']['div_id'] = 'wfqbe_id_notset';
        if ($this->conf['ff_data']['notify_subject'] != '')
            $this->conf['email.']['notify_subject'] = $this->conf['ff_data']['notify_subject'];
        if ($this->conf['ff_data']['notify_email'] != '')
            $this->conf['email.']['notify_email'] = $this->conf['ff_data']['notify_email'];
        if ($this->conf['ff_data']['send_email'] != '')
            $this->conf['email.']['send_email'] = $this->conf['ff_data']['send_email'];
        if ($this->conf['ff_data']['notify_subject_user'] != '')
            $this->conf['email.']['notify_subject_user'] = $this->conf['ff_data']['notify_subject_user'];
        if ($this->conf['ff_data']['field_email_user'] != '')
            $this->conf['email.']['field_email_user'] = $this->conf['ff_data']['field_email_user'];
        if ($this->conf['ff_data']['send_email_user'] != '')
            $this->conf['email.']['send_email_user'] = $this->conf['ff_data']['send_email_user'];
        if ($this->conf['ff_data']['mailTemplate'] != '')
            $this->conf['email.']['template'] = $this->conf['ff_data']['mailTemplate'];
        if ($this->conf['recordsForPage'] != '')
            $this->conf['ff_data']['recordsForPage'] = $this->conf['recordsForPage'];
    }


    /**
     * The registered XAJAX function for the results list
     */
    function processResults($data)
    {
        $this->piVars = $data[$this->prefixId];

        $content = $this->sGetFormResult();

        $objResponse = new tx_xajax_response();
        if ($this->conf['ff_data']['div_search_result_id'] != "")
            $objResponse->addAssign($this->conf['ff_data']['div_search_result_id'], 'innerHTML', $content);
        else
            $objResponse->addAssign($this->conf['ff_data']['div_id'], 'innerHTML', $content);
        return $objResponse->getXML();
    }


    /**
     * The registered XAJAX function for the search form update
     */
    function processSearch($data)
    {
        $this->piVars = $data[$this->prefixId];

        $SEARCH = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_search");
        $SEARCH->main($this->conf, $this->cObj, $this);
        $content = $SEARCH->sGetSearchForm_Ajax();

        $objResponse = new tx_xajax_response();
        $objResponse->addAssign($this->piVars['wfqbe_destination_id'], 'innerHTML', $content);
        return $objResponse->getXML();
    }


    /**
     * The registered XAJAX function for the insert data management
     */
    function processInsert($data)
    {
        $this->piVars = $data[$this->prefixId];

        $INSERT = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_insert");
        $INSERT->main($this->conf, $this->cObj, $this);
        $content = $INSERT->sGetInsertForm_Ajax();

        $objResponse = new tx_xajax_response();

        $destination_id = $this->conf['ff_data']['div_id'];
        if ($this->piVars['wfqbe_destination_id'] != "")
            $destination_id = $this->piVars['wfqbe_destination_id'];

        $objResponse->addAssign($destination_id, 'innerHTML', $content);
        return $objResponse->getXML();
    }


    function sGetForm(&$form_built)
    {
        return $this->do_general('do_sGetForm', $form_built);
    }

    function sGetFormResult()
    {
        $form_built = '';
        return $this->do_general('do_sGetFormResult', $form_built);
    }


    function do_general($to_function, &$form_built, $beObj = null)
    {
        $content = '';

        if (!is_null($beObj)) {
            $this->beObj = $beObj;
        }

        if (!is_object($this->cObj)) {
            $this->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        }


        if ($this->piVars['wfqbe_add_new'] != '' && intval($this->piVars['wfqbe_add_new']) > 0) {
            $where = 'tx_wfqbe_query.uid=' . intval($this->conf['ff_data']['queryObject']) . ' AND ';
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_wfqbe_query', $where . 'tx_wfqbe_query.hidden!=1 AND tx_wfqbe_query.deleted!=1');
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                $this->original_row = $row;
                $this->insertBlocks = $this->getInsertBlocks($row);
            }
        } elseif (isset($this->piVars['wfqbe_add_new'])) {
            unset($this->piVars['wfqbe_add_new']);
        }
        //if ($this->piVars['wfqbe_select_wizard']!='' && intval($this->piVars['wfqbe_select_wizard'])>0)	{
        if ($this->piVars['wfqbe_select_wizard'] != '' && intval($this->piVars['wfqbe_select_wizard']) >= 0) {
            $where = 'tx_wfqbe_query.uid=' . intval($this->conf['ff_data']['queryObject']) . ' AND ';
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_wfqbe_query', $where . 'tx_wfqbe_query.hidden!=1 AND tx_wfqbe_query.deleted!=1');
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                $this->original_row = $row;
                $this->insertBlocks = $this->getInsertBlocks($row);
                if ($this->insertBlocks['fields'][$this->piVars['wfqbe_select_wizard']]['form']['multiple'] == 1)
                    $this->piVars['wfqbe_select_wizard_type'] = 'checkbox';
                else
                    $this->piVars['wfqbe_select_wizard_type'] = 'radio';
            }
        } elseif (isset($this->piVars['wfqbe_select_wizard'])) {
            unset($this->piVars['wfqbe_select_wizard']);
        }

        $where = '';
        if ($this->piVars['wfqbe_results_query'] != '') {
            $where = 'tx_wfqbe_query.uid=' . intval($this->piVars['wfqbe_results_query']) . ' AND ';
        } elseif ($this->piVars['wfqbe_add_new'] != '') {
            $where = 'tx_wfqbe_query.uid=' . intval($this->insertBlocks['fields'][$this->piVars['wfqbe_add_new']]['form']['add_new']) . ' AND ';
        } elseif (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->piVars['wfqbe_select_wizard'])) {
            $where = 'tx_wfqbe_query.uid=' . intval($this->insertBlocks['fields'][$this->piVars['wfqbe_select_wizard']]['form']['select_wizard']) . ' AND ';
        } else {
            $where = 'tx_wfqbe_query.uid=' . intval($this->conf['ff_data']['queryObject']) . ' AND ';
        }

        // Create the connection to the remote DB
        $CONN = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_connect");
        $connection_obj = $CONN->connect($where);

        if ($connection_obj !== false) {
            if ($to_function == 'do_sGetForm')
                $content .= $this->do_sGetForm($connection_obj["row"], $connection_obj["conn"], $form_built);
            else
                $content .= $this->do_sGetFormResult($connection_obj["row"], $connection_obj["conn"]);
        } else {
            $content .= '<div id="' . $this->conf['ff_data']['div_id'] . '">';
            $content .= "Connection failed. Please check your credentials and the dbname.";
            $content .= '</div>';
            return $content;
        }

        return $content;
    }


    /**
     * Function used to unserialize the insertq field (the insert form data structure)
     */
    function getInsertBlocks($row)
    {
        $API = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_api_xml2array");

        $blocks = "";
        if ($row["insertq"] != "") {
            $blocks = $API->xml2array($row["insertq"]);
        }
        return $blocks;
    }


    function do_sGetForm($row, $h, &$form_built)
    {
        if ($row['type'] == 'search') {
            // SEARCH
            $SEARCH = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_search");
            $SEARCH->main($this->conf, $this->cObj, $this);
            $content = $SEARCH->do_sGetForm($row, $h, $form_built);

        } elseif ($row['type'] == 'insert') {
            // INSERT
            /*$INSERT = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_insert");
            $INSERT->main($this->conf, $this->cObj, $this);
            $content = $INSERT->do_sGetForm($row, $h);
            */
        } else {
            return '';
        }

        return $content;
    }


    function do_sGetFormResult($row, $h)
    {
        if ($row['type'] == "insert") {
            // INSERT
            $INSERT = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_insert");
            $INSERT->main($this->conf, $this->cObj, $this);
            $content = $INSERT->do_sGetFormResult($row, $h);

        } elseif ($row['type'] == "search") {
            // SEARCH
            $SEARCH = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_search");
            $SEARCH->main($this->conf, $this->cObj, $this);
            $content = $SEARCH->do_sGetFormResult($row, $h);

        } else {
            // SELECT
            $SELECT = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_results");
            $SELECT->main($this->conf, $this->cObj, $this);
            $content = $SELECT->do_sGetFormResult($row, $h);
        }

        return $content;
    }


    /**
     * html without hidden non-associated tags
     */
    function clearInput($html)
    {
        $html = preg_replace("/(###)+[a-z,A-Z,0-9,@,!,%_]+(###)/", "", $html);
        return $html;
    }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/pi1/class.tx_wfqbe_pi1.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/pi1/class.tx_wfqbe_pi1.php']);
}

