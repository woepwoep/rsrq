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

$MCONF['name'] = 'web_txwfqbeM2';

$GLOBALS['LANG']->includeLLFile('EXT:wfqbe/mod2/locallang.xml');
$GLOBALS['BE_USER']->modAccess($MCONF, 1);

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . 'mod2/class.tx_wfqbe_belib.php');


/**
 * Module 'DB management' for the 'wfqbe' extension.
 *
 * @author     <>
 * @package    TYPO3
 * @subpackage    tx_wfqbe
 */
class  tx_wfqbe_module2 extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    var $pageinfo;

    /**
     * Initializes the backend module by setting internal variables, initializing the menu.
     *
     * @return void
     * @see init()
     */
    public function initModule($moduleConfiguration)
    {
        $this->MCONF = $moduleConfiguration;
        parent::init();
    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     *
     * @return    void
     */
    function menuConfig()
    {
        global $LANG;
        $this->MOD_MENU = Array(
            'function' => Array(
                '1' => $LANG->getLL('function1'),
                '2' => $LANG->getLL('function2'),
                '3' => $LANG->getLL('function3'),
            )
        );
        parent::menuConfig();
    }

    /**
     * Main function of the module. Write the content to $this->content
     * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
     *
     * @return    [type]        ...
     */
    function main()
    {
        global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;

        // Access check!
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($this->pageinfo) ? 1 : 0;

        if (($this->id && $access) || ($GLOBALS['BE_USER']->user['admin'] && !$this->id)) {

            // Draw the header.
            $this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
            $this->doc->backPath = $BACK_PATH;
            $this->doc->form = '<form action="" id="_form" method="post" enctype="multipart/form-data">';

            global $BACK_PATH;

            $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
            $pageRenderer->loadExtJS();
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');

            $modTSconfig = $GLOBALS['BE_USER']->getTSConfig(
                'mod.tx_wfqbe_mod2',
                \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->id)
            );

            if ($modTSconfig['properties']['customCSS'] != '') {
                $pageRenderer->addCssFile($BACK_PATH . '../' . $modTSconfig['properties']['customCSS']);
            }

            $this->content .= $this->doc->startPage($LANG->getLL('title'));
            $this->content .= $this->doc->divider(5);

            // Render content:
            $this->moduleContent();


            // ShortCut
            if ($BE_USER->mayMakeShortcut()) {
                $this->content .= $this->doc->section('', $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']));
            }

        } else {
            // If no access or if ID == zero

            $this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
            $this->doc->backPath = $BACK_PATH;

            $this->content .= $this->doc->startPage($LANG->getLL('title'));
            $this->content .= $this->doc->header($LANG->getLL('title'));
        }
    }

    /**
     * Prints out the module HTML
     *
     * @return    void
     */
    function printContent()
    {

        $this->content .= $this->doc->endPage();
        echo $this->content;
    }

    /**
     * Generates the module content
     *
     * @return    void
     */
    function moduleContent()
    {
        $BELIB = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_wfqbe_belib');
        $content = $BELIB->getContent($this);
        $title = $BELIB->getTitle();
        $this->content .= $this->doc->sectionHeader($title, false, 'style="margin-left:24px"');
        $this->content .= $this->doc->section("", $content, 0, 1);
    }


    function mainAction(){
        $MCONF['name'] = 'web_txwfqbeM2';
        $this->initModule($MCONF);
        $this->main();
        $this->printContent();
    }
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/mod2/index.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/mod2/index.php']);
}