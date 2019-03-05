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


$MCONF['name'] = 'web_txwfqbeM1';

$GLOBALS['LANG']->includeLLFile('EXT:wfqbe/mod1/locallang.xml');
$GLOBALS['BE_USER']->modAccess($MCONF, 1);

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "lib/class.tx_wfqbe_connect.php");

/**
 * Module 'DB structure' for the 'wfqbe' extension.
 *
 * @author     Mauro Lorenzutti <mauro.lorenzutti@webformat.com>
 * @package    TYPO3
 * @subpackage    tx_wfqbe
 */
class tx_wfqbe_module1 extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    var $pageinfo;
    var $conn = false;

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
                '1' => $LANG->getLL('function1'
                ),
                '2' => $LANG->getLL('function2'
                ), '3' => $LANG->getLL('function3'),));
        parent:: menuConfig();
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
        //$this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id,$this->perms_clause);
        //$access = is_array($this->pageinfo) ? 1 : 0;

        if (true) {

            // Draw the header.
            $this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
            $this->doc->backPath = $BACK_PATH;
            $this->doc->form = '<form action="" method="POST">';

            $this->doc->loadJavascriptLib('contrib/prototype/prototype.js');
            $this->doc->loadJavascriptLib('js/common.js');

            // JavaScript
            $this->doc->JScode = '
                                        <script language="javascript" type="text/javascript">
                                            script_ended = 0;
                                            function jumpToUrl(URL)    {
                                                document.location = URL;
                                            }
                                            
                                            function displayConfirm(testo)
                                            {
                                                if (confirm(testo)) {return(true)}
                                                else {return(false)}
                                            }
                                            
                                            function cambiaHelp() {
                                                sel = document.getElementById(\'newfield_type\').value;
                                                switch (sel)	{
                                                    case \'B\':
                                                    case \'T\':
                                                    case \'D\':
                                                    case \'L\':
                                                        document.getElementById(\'newfield_maxlength\').value = \'\';
                                                        document.getElementById(\'maxlength_label\').style.display = \'none\';
                                                        document.getElementById(\'newfield_maxlength\').style.display = \'none\';
                                                        break;
                                                    default:
                                                        document.getElementById(\'maxlength_label\').style.display = \'block\';
                                                        document.getElementById(\'newfield_maxlength\').style.display = \'block\';
                                                        break;
                                                }
                                                new Ajax.Request(\'' . $BACK_PATH . 'ajax.php\', {
                                                    method: \'get\',
                                                    parameters: \'ajaxID=tx_wfqbe_mod1_ajax::fieldTypeHelp&field=\'+sel,
                                                    onComplete: function(xhr, json) {
                                                        // display results, should be "The tree works"
                                                        document.getElementById(\'user_txwfqbeM1_help\').innerHTML = ""+xhr.responseText;
                                                    }.bind(this),
                                                    onT3Error: function(xhr, json) {
                                                        // display error message, will be "An error occurred" if an error occurred
                                                    }.bind(this)
                                                });
                                            }
                                            
                                        </script>
                                    ';
            $this->doc->postCode = '
                                        <script language="javascript" type="text/javascript">
                                            script_ended = 1;
                                            if (top.fsMod) top.fsMod.recentIds["web"] = 0;
                                        </script>
                                    ';

            $headerSection = $this->doc->getHeader('pages', $this->pageinfo, $this->pageinfo['_thePath']) . '<br />' . $LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path') . ': ' . \TYPO3\CMS\Core\Utility\GeneralUtility:: fixed_lgd_cs($this->pageinfo['_thePath'], 50);

            $this->content .= $this->doc->startPage($LANG->getLL('title'));
            $this->content .= $this->doc->sectionHeader($LANG->getLL('title'), false, 'style="margin-left:24px"');
            $this->content .= $this->doc->divider(5);

            // Render content:
            $this->moduleContent();

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
        global $LANG;
        switch ((string)$this->MOD_SETTINGS['function']) {
            case 1 :
            default :
                $content = '<p>' . $LANG->getLL('mod_descr') . '</p>';
                $content .= $this->selectCredentials() . '<br /><br />';

                if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('credentials') != '') {
                    $content .= $this->selectTable(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('credentials')) . '<br /><br />';

                    if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('credentials') != '') {
                        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('new_field') != '') {
                            $content .= $this->addFieldsForm(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('table')) . '<br /><br />';
                        } else {
                            $content .= $this->manageFields(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('credentials'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('table')) . '<br /><br />';
                        }
                    }
                }

                $this->content .= $this->doc->section('', '<div class="module-body t3js-module-body">' . $content . '</div>', 0, 1);
                break;
        }
    }


    function selectCredentials()
    {
        global $LANG, $BE_USER;

        if ($BE_USER->userTS['module.']['user_txwfqbeM1.']['allowedCredentials'] != '')
            $where = ' AND uid IN (' . $BE_USER->userTS['module.']['user_txwfqbeM1.']['allowedCredentials'] . ')';
        else
            $where = '';

        $content = '<br /><label for="credentials">' . $LANG->getLL('label_credentials') . '</label>: <select onchange="submit();" id="credentials" name="credentials">';
        $content .= '<option value=""></option>';

        if ($BE_USER->userTS['module.']['user_txwfqbeM1.']['allowedCredentials'] == '' || ($BE_USER->userTS['module.']['user_txwfqbeM1.']['allowedCredentials'] != '' && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($BE_USER->userTS['module.']['user_txwfqbeM1.']['allowedCredentials'], '0'))) {
            if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('credentials') == 0 && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('credentials')))
                $selected = ' selected="selected"';
            else
                $selected = '';
            $content .= '<option value="0"' . $selected . '>' . $LANG->getLL('typo3_credentials') . '</option>';
        }

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_wfqbe_credentials', 'deleted=0' . $where);
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('credentials') == $row['uid'])
                    $selected = ' selected="selected"';
                else
                    $selected = '';
                $content .= '<option value="' . $row['uid'] . '"' . $selected . '>' . $row['title'] . '</option>';
            }
        }

        $content .= '</select>';
        return $content;
    }


    function selectTable($credentials)
    {
        global $LANG, $BE_USER;
        $content = '<label for="table">' . $LANG->getLL('label_table') . ':</label> <select onchange="submit();" id="table" name="table">';

        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($credentials)) {

            $CONNECTION = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_connect");
            $this->conn = $CONNECTION->connectNow($credentials);
            if ($this->conn != false) {
                $tables = $this->conn->MetaTables(false, true);
                $content .= '<option value=""></option>';
                if (is_array($tables) && count($tables) > 0) {
                    foreach ($tables as $table) {
                        if ($BE_USER->userTS['module.']['user_txwfqbeM1.']['allowedTables.'][$credentials] != '' && !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($BE_USER->userTS['module.']['user_txwfqbeM1.']['allowedTables.'][$credentials], $table))
                            continue;

                        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('table') == $table)
                            $selected = ' selected="selected"';
                        else
                            $selected = '';
                        $content .= '<option value="' . $table . '"' . $selected . '>' . $table . '</option>';
                    }
                }
            } else {
                return $LANG->getLL('no_connection');
            }
        } else {
            return $LANG->getLL('no_credentials');
        }

        $content .= '</select>';

        return $content;
    }


    function manageFields($credentials, $table)
    {
        global $LANG, $BACK_PATH;

        if ($table == '')
            return '';

        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('delete_field') != '') {
            if ($this->deleteField($table, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('delete_field'))) {
                $content .= '<span>' . $LANG->getLL('field_deleted') . '</span><br /><br />';
            } else {
                $content .= '<span style="color: red">' . $LANG->getLL('field_not_deleted') . '</span><br /><br />';
            }
        }

        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('add_field') != '')
            $content .= $this->addField(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('table'));


        $columns = $this->conn->MetaColumns($table);

        $content .= '<table class="table table-striped" cellspacing="0" cellpadding="0" border="0">';
        $content .= '<thead>
                        <tr class="c-headLineTable">
                            <th style="padding-left: 5px;" class="c-table">' . $LANG->getLL('label_name') . '</th>
                            <th>' . $LANG->getLL('label_type') . '</th>
                            <th></th>
                        </tr>
                      </thead><tbody>';

        if (is_array($columns) && count($columns) > 0) {
            $i = 0;
            foreach ($columns as $col) {
                if ($i % 2 == 0)
                    $style = ' style="background-color: rgb(245, 245, 245);"';
                else
                    $style = '';
                $content .= '<tr' . $style . ' class="db_list_normal"><td style="padding-left: 5px;">' . $col->name . '</td><td>' . $col->type . ($col->max_length != '' && $col->max_length != -1 ? ' (' . $col->max_length . ')' : '') . '</td>';
                $content .= '<td><a href="index.php?id=0&credentials=' . $credentials . '&table=' . $table . '&delete_field=' . $col->name . '" onclick="return displayConfirm(\'' . $LANG->getLL('confirm_delete') . '\');"><img src="' . $BACK_PATH . 'sysext/t3skin/images/icons/actions/edit-delete.png"></a></td></tr>';
                $i++;
            }
        }

        $content .= '</tbody></table><br /><br />';

        $content .= '<input type="submit" name="new_field" value="' . $LANG->getLL('label_new_field') . '" />';

        return $content;
    }


    function addFieldsForm($table)
    {
        global $LANG;
        $content = '<h2>' . $LANG->getLL('title_add_field') . '</h2>';

        $content .= '<table><tr><td width="350px">';

        $content .= '<table cellspacing="0" cellpadding="5px" border="0">';
        $content .= '<tr><td><label for="newfield_name">' . $LANG->getLL('newfield_name') . '</label></td>
                    <td><input type="text" name="newfield[name]" id="newfield_name" value="" /></td></tr>';
        $content .= '<tr><td><label for="newfield_type">' . $LANG->getLL('newfield_type') . '</label></td>
                    <td><select onchange="cambiaHelp();" name="newfield[type]" id="newfield_type">
                        <option value="C">Varchar (max 255 characters)</option>
                        <option value="X">Larger varchar (max 4000 characters)</option>
                        <option value="C2">Multibyte varchar</option>
                        <option value="X2">Larger multibyte varchar</option>
                        <option value="B">Blob</option>
                        <option value="D">Date</option>
                        <option value="T">Timestamp or Datetime</option>
                        <option value="L">Boolean</option>
                        <option value="I">Integer</option>
                        <option value="F">Floating point number</option>
                        </select>
                    </td></tr>';

        $content .= '<tr><td><label id="maxlength_label" for="newfield_maxlength">' . $LANG->getLL('newfield_maxlength') . '</label></td>
                    <td><input type="text" size="3" name="newfield[maxlength]" id="newfield_maxlength" value="" /></td></tr>';

        $content .= '</table>';

        $content .= '<input type="submit" name="add_field" value="' . $LANG->getLL('label_add_field') . '" />';

        $content .= '<br /><br /><a href="javascript:history.go(-1);">' . $LANG->getLL('back') . '</a>';

        $content .= '</td><td valign="top"><h3>' . $LANG->getLL('help_header') . '</h3><div id="user_txwfqbeM1_help">';
        $content .= $LANG->getLL('help_C');
        $content .= '<br /></div></td></tr></table>';


        return $content;
    }


    function deleteField($table, $field)
    {
        global $LANG;
        $dict = NewDataDictionary($this->conn);
        $flds = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('delete_field');
        $sqlarray = $dict->DropColumnSQL($table, $flds);
        $dict->ExecuteSQLArray($sqlarray);

        return true;
    }


    function addField($table)
    {
        $field = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('newfield');
        if (is_array($field)) {
            if ($field['name'] == '') {
                return '<span style="color: red;">' . $LANG->getLL('newfield_error_name') . '</span>';
            }
            if ($field['type'] == '') {
                return '<span style="color: red;">' . $LANG->getLL('newfield_error_name') . '</span>';
            }

            $dict = NewDataDictionary($this->conn);
            $flds = $field['name'] . " " . $field['type'] . (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($field['maxlength']) ? "(" . $field['maxlength'] . ")" : "");
            $sqlarray = $dict->ChangeTableSQL($table, $flds);
            $dict->ExecuteSQLArray($sqlarray);

        } else {
            return '<span style="color: red;">' . $LANG->getLL('newfield_error') . '</span>';
        }
    }


    public function ajaxFieldTypeHelp($params, &$ajaxObj)
    {
        //$this->init();
        global $LANG;

        // the content is an array that can be set through $key / $value pairs as parameter
        $ajaxObj->addContent('help', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('field') . ' - ' . date('H:i:s', time()));
    }


    function mainAction(){
        $MCONF['name'] = 'web_txwfqbeM1';
        $this->initModule($MCONF);
        $this->main();
        $this->printContent();
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/mod1/index.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/mod1/index.php']);
}