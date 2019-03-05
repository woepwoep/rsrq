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

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('adodb') . 'adodb/adodb.inc.php');
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "lib/class.tx_wfqbe_api_array2xml.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "lib/class.tx_wfqbe_api_xml2array.php");

$GLOBALS['LANG']->includeLLFile('EXT:wfqbe/tx_wfqbe_query_insert/locallang.xml');

class tx_wfqbe_insertform_generator
{
    var $extKey = 'wfqbe';    // The extension key.

    var $blocks;
    var $markers;

    var $RAW_MODE = false;

    // Elenco dei tipi di input disponibili
    var $types = array('display only', 'input', 'datetype', 'calendar', 'password', 'radio', 'select', 'textarea', 'checkbox', 'hidden', 'upload', 'relation', 'PHP function', 'Raw HTML');
    var $calendarDateFormats = array(
        'datetocal' => array(
            '%d/%m/%Y',
            '%d-%m-%Y',
            '%d.%m.%Y',
            '%m/%d/%Y',
            '%m-%d-%Y',
            '%m.%d.%Y',
            '%Y-%m-%d',
            '%H:%M %d/%m/%Y',
            '%H:%M %d-%m-%Y',
            '%H:%M %d.%m.%Y',
            '%H:%M %m/%d/%Y',
            '%H:%M %m-%d-%Y',
            '%H:%M %m.%d.%Y',
        ),
        'jquery_datepicker' => array(
            'dd-mm-yy',
            'dd.mm.yy',
            'dd/mm/yy',
            'mm-dd-yy',
            'mm.dd.yy',
            'mm/dd/yy'
        )
    );


    function init()
    {
        $wfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wfqbe');
        $this->blocks = $wfqbe;

        if ($this->blocks == "")
            $this->parseModule();
    }


    /**
     * Crea una stringa che contiene l'xml della query creata.
     * Questa funzione viene richiamata nel file index.php quando si salva oppure si salva e si chiede il file.
     * Utilizza la funzione array2xml() definita nella classe tx_wfqbe_api_array2xml che converte un array in un file(stringa) xml.
     *
     * @return    [string]    $xml: stringa che contiene la query in formato xml
     */
    function saveModule()
    {
        $API = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_api_array2xml");
        $xml = $API->array2xml($this->blocks);
        return $xml;
    }

    /**
     * Crea il form per la creazione della query
     *
     * @param    [type]        $h: puntatore alla connessione al database
     *
     * @return    [string]    $content: stringa che contiene l'html del form
     */

    function showForm($h)
    {
        if ($this->blocks['RAW_MODE'] == 1)
            $this->RAW_MODE = true;

        $content = "<h1>Insert/Edit Module</h1>";
        $content .= '<table style="font-size: 0.9em" class="table table-striped">';

        // Hook that can be used to add custom field types
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['customFieldTypesWizard'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['customFieldTypesWizard'] as $_classRef) {
                $_params = array();
                $_params['blocks'] = $this->blocks;
                $_params['connection'] = $h;
                $_params['types'] = $this->types;
                $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                $this->types = $_procObj->addCustomFieldTypes($_params, $this);
            }
        }

//$content .= \TYPO3\CMS\Core\Utility\GeneralUtility::view_array($this->blocks);

        $content .= '<input id="wfqbe_up" type="hidden" name="wfqbe[up]" value="" />';
        $content .= '<input id="wfqbe_down" type="hidden" name="wfqbe[down]" value="" />';
        $content .= '<input id="wfqbe_move_from" type="hidden" name="wfqbe[move_from]" value="" />';
        $content .= '<input id="wfqbe_move_to" type="hidden" name="wfqbe[move_to]" value="" />';

        if ($this->blocks['up'] != '') {
            $up = intval($this->blocks['up']);
            if ($up > 0) {
                $this->blocks['fields']['temp'] = $this->blocks['fields'][$up - 1];
                $this->blocks['fields'][$up - 1] = $this->blocks['fields'][$up];
                $this->blocks['fields'][$up] = $this->blocks['fields']['temp'];
            }
        }
        if ($this->blocks['down'] != '') {
            $up = intval($this->blocks['down']);
            if ($up < sizeof($this->blocks['fields'])) {
                $this->blocks['fields']['temp'] = $this->blocks['fields'][$up + 1];
                $this->blocks['fields'][$up + 1] = $this->blocks['fields'][$up];
                $this->blocks['fields'][$up] = $this->blocks['fields']['temp'];
            }
        }
        if ($this->blocks['move_from'] != '' && $this->blocks['move_to'] != '') {
            $to = intval($this->blocks['move_to']);
            $from = intval($this->blocks['move_from']);
            if ($to < sizeof($this->blocks['fields'])) {
                $this->blocks['fields']['temp'] = $this->blocks['fields'][$to];
                $this->blocks['fields'][$to] = $this->blocks['fields'][$from];
                $this->blocks['fields'][$from] = $this->blocks['fields']['temp'];
            }
        }
        if (is_array($this->blocks))
            unset($this->blocks['fields']['temp']);

        // Visualizza la selezione delle tabelle
        $content .= '<tr class="db_list_normal"><td><strong>Select Table:</strong> ';
        $content .= $this->showSelectTable($h, $this->blocks['table']);
        $content .= '</td></tr>';

        if ($this->blocks['table'] != "") {
            $content .= '<tr class="db_list_normal"><td>';
            $content .= '<strong style="color: red;">For editing functionality:</strong>';
            $content .= '<br />ID field: ' . $this->showSelectField($h, $this->blocks['ID_field'], $this->blocks['table'], 'wfqbe[ID_field]', false);
            $content .= '</td></tr>';

            $content .= '<tr class="db_list_normal"><td>';
            $content .= '<strong>Restricting access:</strong>';
            $content .= '<br />ID IN:<br /><textarea rows="3" cols="90" name="wfqbe[ID_restricting]">' . $this->blocks['ID_restricting'] . '</textarea>';
            $content .= '<br />ex. ID IN: select uid FROM fe_users WHERE pid=123 AND usergroup=5';
            $content .= '</td></tr>';
        }

        $form_positions = '<option value=""></option>';
        if (is_array($this->blocks['fields'])) {
            foreach ($this->blocks['fields'] as $key => $value) {
                $form_positions .= '<option value="' . $key . '">' . $key . '</option>';
            }
        }

        $numForm = 0;
        if ($this->blocks['table'] != "") {
            $this->checkBlocks();
            $fields = $h->MetaColumnNames($this->blocks['table']);
            if (is_array($this->blocks['fields'])) {
                foreach ($this->blocks['fields'] as $key => $value) {
                    if ($numForm % 2 == 0)
                        $backgroundColor = 'db_list_normal';
                    else
                        $backgroundColor = 'db_list_normal';

                    $content .= '<tr class="' . $backgroundColor . '" ><td>' . $numForm . ' ---- ';

                    $content .= '<strong>Select the field to insert/edit:</strong> ' . $this->showSelectField($h, $this->blocks['fields'][$key]['field'], $this->blocks['table'], 'wfqbe[fields][' . $numForm . '][field]', true, true);
                    if ($numForm > 0)
                        $content .= ' <a href="#" onclick="document.getElementById(\'wfqbe_up\').value=' . $numForm . '; updateForm()"><img src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3/sysext/t3skin/icons/gfx/arrowup.png" /></a>';
                    if ($numForm < sizeof($this->blocks['fields']) - 1)
                        $content .= ' <a href="#" onclick="document.getElementById(\'wfqbe_down\').value=' . $numForm . '; updateForm()"><img src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3/sysext/t3skin/icons/gfx/arrowup.png" /></a>';
                    $content .= ' Exchange with: <select onchange="document.getElementById(\'wfqbe_move_from\').value=' . $numForm . '; document.getElementById(\'wfqbe_move_to\').value=document.getElementById(\'wfqbe_this_' . $numForm . '\').value; updateForm();" id="wfqbe_this_' . $numForm . '" name="wfqbe_move_to">' . $form_positions . '</select></a>';
                    $content .= '<br />Select input type: ';

                    $content .= $this->showSelectType($numForm, $key, $this->blocks['fields'][$key]['type'], $this->blocks['fields'][$key]['required']);
                    $content .= '<br />';

                    switch ($this->blocks['fields'][$key]['type']) {
                        case 'display only':
                            $content .= $this->showDisplay($numForm, $this->blocks['fields'][$key]['form']);
                            break;
                        case 'input':
                            $content .= $this->showInput($numForm, $this->blocks['fields'][$key]['form']);
                            break;
                        case 'datetype':
                            $content .= $this->showDatetype($numForm, $this->blocks['fields'][$key]['form']);
                            break;
                        case 'calendar':
                            $content .= $this->showCalendar($numForm, $this->blocks['fields'][$key]['form']);
                            break;
                        case 'password':
                            $content .= $this->showPassword($numForm, $this->blocks['fields'][$key]['form']);
                            break;
                        case 'radio':
                            $content .= $this->showRadio($numForm, $this->blocks['fields'][$key]['form'], $h);
                            break;
                        case 'select':
                            $content .= $this->showSelect($numForm, $this->blocks['fields'][$key]['form'], $h);
                            break;
                        case 'textarea':
                            $content .= $this->showTextarea($numForm, $this->blocks['fields'][$key]['form']);
                            break;
                        case 'checkbox':
                            $content .= $this->showCheckbox($numForm, $this->blocks['fields'][$key]['form'], $h);
                            break;
                        case 'hidden':
                            $content .= $this->showHidden($numForm, $this->blocks['fields'][$key]['form']);
                            break;
                        case 'upload':
                            $content .= $this->showUpload($numForm, $this->blocks['fields'][$key]['form']);
                            break;
                        case 'relation':
                            $content .= $this->showRelation($numForm, $this->blocks['fields'][$key]['form'], $h);
                            break;
                        case 'PHP function':
                            $content .= $this->showPHPFunction($numForm, $this->blocks['fields'][$key]['form']);
                            break;
                        case 'Raw HTML':
                            $content .= $this->showRawHTML($numForm, $this->blocks['fields'][$key]['form']);
                            break;
                        default:
                            // Hook that can be used to manage custom field types
                            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['customFieldTypesWizard'])) {
                                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['customFieldTypesWizard'] as $_classRef) {
                                    $_params = array();
                                    $_params['key'] = $numForm;
                                    $_params['form'] = $this->blocks['fields'][$key]['form'];
                                    $_params['blocks'] = $this->blocks;
                                    $_params['connection'] = $h;
                                    $_params['type'] = $this->blocks['fields'][$key]['type'];
                                    $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                                    $content .= $_procObj->manageCustomFieldType($_params, $this);
                                }
                            }
                            break;
                    }

                    if ($this->blocks['fields'][$key]['type'] != '' && $this->blocks['fields'][$key]['type'] != "hidden" && $this->blocks['fields'][$key]['type'] != "PHP function" && $this->blocks['fields'][$key]['type'] != "display only") {
                        //$content .= '<br />-----<br />Help text: <textarea cols="80" name="wfqbe[fields]['.$numForm.'][help]">'.$this->blocks['fields'][$key]['help'].'</textarea>';
                        $content .= $this->helpInput($numForm, $this->blocks['fields'][$key]['help']);
                    }

                    $content .= '</td></tr>';

                    $numForm++;
                }
            }

            if ($numForm % 2 == 0)
                $backgroundColor = 'db_list_normal';
            else
                $backgroundColor = 'db_list_normal';

            $content .= '<tr class="' . $backgroundColor . '" ><td>';
            $content .= '<strong>Select the field to insert/edit:</strong> ' . $this->showSelectField($h, "", $this->blocks['table'], 'wfqbe[fields][' . $numForm . '][field]', true, true);
            $content .= '</td></tr>';

        }

        $content .= '<tr class="db_list_normal"><td>';
        $content .= '<strong>Convert dropdowns to input fields:</strong> ';
        $content .= '<input type="checkbox" onchange="updateForm()" name="wfqbe[RAW_MODE]" value="1" ' . ($this->blocks['RAW_MODE'] == 1 ? 'checked="checked"' : '') . ' />';
        $content .= '<br />Switch from dropdowns to input fields if your db user doesn\'t have permissions to list tables and fields.';
        $content .= '</td></tr>';

        //$content .= \TYPO3\CMS\Core\Utility\GeneralUtility::view_array($this->blocks);

        return $content . '</table>';
    }


    /**
     * Funzione che restituisce una select dei tipi possibili di input
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [string]        $selected: tipo
     * @return    [string]        html contenente la select per i tipi
     */
    function showSelectType($numForm, $key, $selected, $required)
    {
        $html = '<select onchange="updateForm()" name="wfqbe[fields][' . $numForm . '][type]"';
        $html .= '<option value=""></option>';
        foreach ($this->types as $value) {
            if ($value == $selected)
                $html .= '<option selected="selected" value="' . $value . '">' . $value . '</option>';
            else
                $html .= '<option value="' . $value . '">' . $value . '</option>';
        }
        $html .= '</select>';
        if ($selected != '') {
            if ($selected != 'display only') {
                if ($required == 1)
                    $checked = 'checked="checked" ';
                else
                    $checked = '';
                $html .= ' - Required: <input ' . $checked . 'type="checkbox" name="wfqbe[fields][' . $numForm . '][required]" value="1" />';
                $html .= ' - Update on change: <input type="text" name="wfqbe[fields][' . $numForm . '][form][onchange]" value="' . $this->blocks['fields'][$key]['form']['onchange'] . '" />';
            }

            if ($this->blocks['fields'][$key]['form']['when']['insert'] == 1)
                $checked = ' checked="checked"';
            else
                $checked = '';
            $html .= '<br />Use it on insert: <input' . $checked . ' type="checkbox" name="wfqbe[fields][' . $numForm . '][form][when][insert]" value="1" />';
            if ($this->blocks['fields'][$key]['form']['when']['edit'] == 1)
                $checked = ' checked="checked"';
            else
                $checked = '';
            $html .= ' - Use it on edit: <input' . $checked . ' type="checkbox" name="wfqbe[fields][' . $numForm . '][form][when][edit]" value="1" />';
            $html .= '<br />';

            $html .= $this->clearFunctions($key, $selected);

        }
        return $html;
    }


    /**
     * Function used to create the label input field
     */
    function labelInput($key, $label)
    {
        if (!is_array($label)) {
            $def = $label;
            $label = array();
            $label['def'] = $def;
        }
        $html = 'Label: <input type="text" name="wfqbe[fields][' . $key . '][form][label][def]" value="' . $label['def'] . '" />';
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_language', 'hidden=0', '', 'title ASC');
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $html .= '<br />&nbsp;&nbsp;&nbsp;Label (' . $row['title'] . '): <input type="text" name="wfqbe[fields][' . $key . '][form][label][' . $row['uid'] . ']" value="' . $label[$row['uid']] . '" />';
        }

        return $html;
    }


    /**
     * Function used to create the help input field
     */
    function helpInput($numForm, $label)
    {
        if (!is_array($label)) {
            $def = $label;
            $label = array();
            $label['def'] = $def;
        }
        $html = '<br />-----<br />Help text: <textarea cols="80" name="wfqbe[fields][' . $numForm . '][help][def]">' . $label['def'] . '</textarea>';
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_language', 'hidden=0', '', 'title ASC');
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $html .= '<br />&nbsp;&nbsp;&nbsp;Help text (' . $row['title'] . '): <textarea cols="80" name="wfqbe[fields][' . $numForm . '][help][' . $row['uid'] . ']">' . $label[$row['uid']] . '</textarea>';
        }

        return $html;
    }


    /**
     * Function used to create a display value and to configure it
     *
     * @param    [int]            $key: field index (used to sort the fields)
     * @param    [array]            $form: field configuration
     * @return    [string]        html form
     */
    function showDisplay($key, $form)
    {
        $html = $this->labelInput($key, $form['label']);
        $html .= '<br />Fixed value: <input type="text" name="wfqbe[fields][' . $key . '][form][value]" value="' . $form['value'] . '" />';
        $html .= '<br />PHP code: <textarea name="wfqbe[fields][' . $key . '][form][code]" cols="60" rows="5">' . $form['code'] . '</textarea>';
        $html .= '<br /><em>PHP code take precedence over fixed value</em>';
        return $html;
    }


    /**
     * Function used to create an input field and to configure it
     *
     * @param    [int]            $key: field index (used to sort the fields)
     * @param    [array]            $form: field configuration
     * @return    [string]        html form
     */
    function showInput($key, $form)
    {
        $html = $this->labelInput($key, $form['label']);
        $html .= '<br />Size: <input type="text" name="wfqbe[fields][' . $key . '][form][size]" value="' . $form['size'] . '" />';
        $html .= ' - MaxLength: <input type="text" name="wfqbe[fields][' . $key . '][form][maxlength]" value="' . $form['maxlength'] . '" />';
        return $html;
    }


    /** Function used to create a datetype field and to configure it (added by Fabian Moser)
     *
     * @param    [int]            $key: field index (used to sort the fields)
     * @param    [array]            $form: field configuration
     * @return    [string]        html form
     */
    function showDatetype($key, $form)
    {
        $html = $this->labelInput($key, $form['label']);
        $html .= '<br /><br /><strong style="color: red;">Expected input is in the format d.m.Y only</strong><br />';
        $html .= '<br />Size: <input type="text" name="wfqbe[fields][' . $key . '][form][size]" value="' . $form['size'] . '" />';
        /*if ($form['format']=='')
            $form['format'] = 'd.m.Y';
        $html .= ' - Format: <select name="wfqbe[fields]['.$key.'][form][format]">';
        if ($form['format']=='d.m.Y')
            $html .= '<option value="d.m.Y" selected="selected">d.m.Y</option>';
        else
            $html .= '<option value="d.m.Y">d.m.Y</option>';
        if ($form['format']=='H:i')
            $html .= '<option value="H:i" selected="selected">H:i</option>';
        else
            $html .= '<option value="H:i">H:i</option>';
        if ($form['format']=='H:i d.m.Y')
            $html .= '<option value="H:i d.m.Y" selected="selected">H:i d.m.Y</option>';
        else
            $html .= '<option value="H:i d.m.Y">H:i d.m.Y</option>';
        $html .= '</select>';
        */

        //$html .= ' - MaxLength: <input type="text" name="wfqbe[fields]['.$key.'][form][maxlength]" value="'.$form['maxlength'].'" />';
        return $html;
    }


    /**
     * Function used to create a calendar field
     *
     * @param    [int]            $key: field index (used to sort the fields)
     * @param    [array]            $form: field configuration
     * @return    [string]        html form
     */
    function showCalendar($key, $form)
    {
        $html = $this->labelInput($key, $form['label']);

        if ($form['date2cal'] == "si") {
            // Deprecated old date2cal mode
            $html .= ' - ' . $GLOBALS['LANG']->getLL('date2cal') . ': <input type="checkbox" onchange="javascript:updateForm();" name="wfqbe[fields][' . $key . '][form][date2cal]" value="si" checked="checked" />';
            if ($form['nlp'] == "si") {
                $html .= ' - ' . $GLOBALS['LANG']->getLL('nlp') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][nlp]" value="si" checked="checked" />';
            } else {
                $html .= ' - ' . $GLOBALS['LANG']->getLL('nlp') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][nlp]" value="si" />';
            }

            if ($form['time'] == "si") {
                $html .= ' - ' . $GLOBALS['LANG']->getLL('time') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][time]" value="si" checked="checked" />';
            } else {
                $html .= ' - ' . $GLOBALS['LANG']->getLL('time') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][time]" value="si" />';
            }

            if ($form['format'] == '')
                $format = '%d-%m-%Y';
            else
                $format = $form['format'];

            $html .= ' - ' . $GLOBALS['LANG']->getLL('format') . ': <select name="wfqbe[fields][' . $key . '][form][format]" />';
            foreach ($this->calendarDateFormats['date2cal'] as $value) {
                if ($value == $format)
                    $html .= '<option selected="selected" value="' . $value . '">' . $value . '</option>';
                else
                    $html .= '<option value="' . $value . '">' . $value . '</option>';
            }
            $html .= '</select>';

        } else {
            // jQuery datepicker mode
            $html .= ' - ' . $GLOBALS['LANG']->getLL('date2cal') . ': <input type="checkbox" onchange="javascript:updateForm();" name="wfqbe[fields][' . $key . '][form][date2cal]" value="si" />';

            if ($form['format'] == '')
                $format = 'dd-mm-yy';
            else
                $format = $form['format'];

            $html .= ' - ' . $GLOBALS['LANG']->getLL('format') . ': <select name="wfqbe[fields][' . $key . '][form][format]" />';
            foreach ($this->calendarDateFormats['jquery_datepicker'] as $value) {
                if ($value == $format)
                    $html .= '<option selected="selected" value="' . $value . '">' . $value . '</option>';
                else
                    $html .= '<option value="' . $value . '">' . $value . '</option>';
            }
            $html .= '</select>';

            $html .= '<br />Min Date: <input type="text" name="wfqbe[fields][' . $key . '][form][min_date]" value="' . $form['min_date'] . '" /> (e.g. new Date(2005, 1, 1), see jQuery datepicker documentation for the right format)';
            $html .= '<br />Max Date: <input type="text" name="wfqbe[fields][' . $key . '][form][max_date]" value="' . $form['max_date'] . '" /> (e.g. \'+1y\', see jQuery datepicker documentation for the right format)';

        }

        if ($form['convert_timestamp'] == "si") {
            $html .= '<br />' . $GLOBALS['LANG']->getLL('convert_timestamp') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][convert_timestamp]" value="si" checked="checked" />';
        } else {
            $html .= '<br />' . $GLOBALS['LANG']->getLL('convert_timestamp') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][convert_timestamp]" value="si" />';
        }

        if ($form['convert_to_date_oracle'] == "si") {
            $html .= '<br />' . $GLOBALS['LANG']->getLL('convert_to_date_oracle') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][convert_to_date_oracle]" value="si" checked="checked" />';
        } else {
            $html .= '<br />' . $GLOBALS['LANG']->getLL('convert_to_date_oracle') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][convert_to_date_oracle]" value="si" />';
        }

        $html .= '<br /><br /><strong>If you don\'t use the (deprecated) date2cal extension, in order to get the calendar to work in the frontend you need the jQuery datepicker plugin. In the backend it is rendered based on the standard extjs TYPO3 calendar.</strong><br />';

        return $html;
    }


    /**
     * Function used to create a password field and to configure it
     *
     * @param    [int]            $key: field index (used to sort the fields)
     * @param    [array]            $form: field configuration
     * @return    [string]        html form
     */
    function showPassword($key, $form)
    {
        $html = $this->labelInput($key, $form['label']);
        $html .= '<br />Size: <input type="text" name="wfqbe[fields][' . $key . '][form][size]" value="' . $form['size'] . '" />';
        $html .= ' - MaxLength: <input type="text" name="wfqbe[fields][' . $key . '][form][maxlength]" value="' . $form['maxlength'] . '" />';
        return $html;
    }


    /**
     * Funzione che visualizza le opzioni di configurazione nel caso di tipo 'file'
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @return    [string]        html contenente la configurazione
     */
    function showUpload($key, $form)
    {
        $html = $this->labelInput($key, $form['label']);
        $html .= '<br />Max File Size: <input type="text" name="wfqbe[fields][' . $key . '][form][maxfilesize]" value="' . $form['maxfilesize'] . '" />';
        $html .= '<br />Directory: <input type="text" name="wfqbe[fields][' . $key . '][form][basedir]" value="' . $form['basedir'] . '" /> <em>(e.g. fileadmin/wfqbe/)</em>';
        $html .= '<br />Number of uploads: <input type="text" size="3" name="wfqbe[fields][' . $key . '][form][numofuploads]" value="' . $form['numofuploads'] . '" />';
        if ($form['donotrename'] == 1)
            $checked = 'checked="checked" ';
        else
            $checked = '';
        $html .= '<br />Don\'t rename file: <input onchange="javascript:updateForm();" ' . $checked . 'type="checkbox" name="wfqbe[fields][' . $key . '][form][donotrename]" value="1" />';
        if ($form['donotrename'] == 1) {
            if ($form['overwrite'] == 1)
                $checked = 'checked="checked" ';
            else
                $checked = '';
            $html .= ' - Overwrite existing file: <input ' . $checked . 'type="checkbox" name="wfqbe[fields][' . $key . '][form][overwrite]" value="1" />';
        }
        return $html;
    }


    /**
     * Funzione che visualizza le opzioni di configurazione nel caso di tipo 'hidden'
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @return    [string]        html contenente la configurazione
     */
    function showHidden($key, $form)
    {
        $html = 'Value: <input type="text" name="wfqbe[fields][' . $key . '][form][value]" value="' . $form['value'] . '" />';
        $html .= '<br />Get value from parameter: <input type="text" name="wfqbe[fields][' . $key . '][form][value_from_parameter]" value="' . $form['value_from_parameter'] . '" />';
        return $html;
    }


    /**
     * Funzione che visualizza le opzioni di configurazione nel caso di tipo 'textarea'
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @return    [string]        html contenente la configurazione
     */
    function showTextarea($key, $form)
    {
        $html = $this->labelInput($key, $form['label']);

        $html .= ' - Rows: <input type="text" name="wfqbe[fields][' . $key . '][form][rows]" value="' . $form['rows'] . '" size="3" />';
        $html .= ' - Cols: <input type="text" name="wfqbe[fields][' . $key . '][form][cols]" value="' . $form['cols'] . '" size="3" />';
        return $html;
    }


    /**
     * Funzione che visualizza le opzioni di configurazione nel caso di tipo 'radio'
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @param    [resource]        $h: connessione al DB
     * @return    [string]        html contenente la configurazione
     */
    function showRadio($key, $form, $h)
    {
        $html = $this->labelInput($key, $form['label']) . '<br />';

        if ($form['source'] == "db") {
            $html .= 'Source: <input onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="static" /> Static - ';
            $html .= '<input checked="checked" onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="db" /> DB';
        } else {
            $html .= 'Source: <input checked="checked" onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="static" /> Static - ';
            $html .= '<input onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="db" /> DB';
        }

        $html .= '<br />';

        if ($form['source'] == "db") {
            $html .= '<br />-----';
            $html .= '<br />Table: ' . $this->showSelectTable($h, $form['table'], "wfqbe[fields][$key][form][table]");

            if ($form['table'] != '') {
                $html .= '<br />Field - View: ' . $this->showSelectField($h, $form['field_view'], $form['table'], "wfqbe[fields][$key][form][field_view]");
                $html .= ' # Field - Insert: ' . $this->showSelectField($h, $form['field_insert'], $form['table'], "wfqbe[fields][$key][form][field_insert]");
                $html .= '<br />Field - Where: <input size="80" type="text" name="wfqbe[fields][' . $key . '][form][where]" value="' . $form['where'] . '" />';
                $html .= ' # Field - Order by: ' . $this->showSelectField($h, $form['field_orderby'], $form['table'], "wfqbe[fields][$key][form][field_orderby]");
                $html .= '<select name="wfqbe[fields][' . $key . '][form][field_orderby_mod]"><option value=""></option><option value="ASC"' . ($form['field_orderby_mod'] == 'ASC' ? ' selected="selected"' : '') . '>ASC</option><option value="DESC"' . ($form['field_orderby_mod'] == 'DESC' ? ' selected="selected"' : '') . '>DESC</option></select>';
            }

        } else {
            $html .= '<br />-----';
            $html .= '<br />Number of values: <input onblur="javascript:updateForm();" type="text" name="wfqbe[fields][' . $key . '][form][numValues]" value="' . $form['numValues'] . '" size="3" />';


            for ($i = 0; $i < $form['numValues']; $i++) {
                $html .= '<br />Value: <input type="text" name="wfqbe[fields][' . $key . '][form][' . $i . '][value]" value="' . $form[$i]['value'] . '" /> - ';
                $html .= 'Label: <input type="text" name="wfqbe[fields][' . $key . '][form][' . $i . '][label]" value="' . $form[$i]['label'] . '" />';

            }
        }

        if ($form['source'] == "db") {
            $html .= '<br />-----';
            $html .= '<br /><strong>Add new</strong>: ' . $this->showSelectWfqbeRecord($key, $form, $h, 'add_new');
        }

        return $html;
    }


    /**
     * Funzione che visualizza le opzioni di configurazione nel caso di tipo 'checkbox'
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @param    [resource]        $h: connessione al DB
     * @return    [string]        html contenente la configurazione
     */
    function showCheckbox($key, $form, $h)
    {
        $html = $this->labelInput($key, $form['label']) . '<br />';

        if ($form['source'] == "db") {
            $html .= 'Source: <input onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="static" /> Static - ';
            $html .= '<input checked="checked" onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="db" /> DB';
        } else {
            $html .= 'Source: <input checked="checked" onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="static" /> Static - ';
            $html .= '<input onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="db" /> DB';
        }

        $html .= '<br />';

        if ($form['source'] == "db") {
            $html .= '<br />-----';
            $html .= '<br />Table: ' . $this->showSelectTable($h, $form['table'], "wfqbe[fields][$key][form][table]");

            if ($form['table'] != '') {
                $html .= '<br />Field - View: ' . $this->showSelectField($h, $form['field_view'], $form['table'], "wfqbe[fields][$key][form][field_view]");
                $html .= ' # Field - Insert: ' . $this->showSelectField($h, $form['field_insert'], $form['table'], "wfqbe[fields][$key][form][field_insert]");
                $html .= '<br />Field - Where: <input size="80" type="text" name="wfqbe[fields][' . $key . '][form][where]" value="' . $form['where'] . '" />';
                $html .= ' # Field - Order by: ' . $this->showSelectField($h, $form['field_orderby'], $form['table'], "wfqbe[fields][$key][form][field_orderby]");
                $html .= '<select name="wfqbe[fields][' . $key . '][form][field_orderby_mod]"><option value=""></option><option value="ASC"' . ($form['field_orderby_mod'] == 'ASC' ? ' selected="selected"' : '') . '>ASC</option><option value="DESC"' . ($form['field_orderby_mod'] == 'DESC' ? ' selected="selected"' : '') . '>DESC</option></select>';
            }

        } else {
            $html .= '<br />-----';
            $html .= '<br />Number of values: <input onblur="javascript:updateForm();" type="text" name="wfqbe[fields][' . $key . '][form][numValues]" value="' . $form['numValues'] . '" size="3" />';


            for ($i = 0; $i < $form['numValues']; $i++) {
                $html .= '<br />Value: <input type="text" name="wfqbe[fields][' . $key . '][form][' . $i . '][value]" value="' . $form[$i]['value'] . '" /> - ';
                $html .= 'Label: <input type="text" name="wfqbe[fields][' . $key . '][form][' . $i . '][label]" value="' . $form[$i]['label'] . '" />';

            }
        }

        if ($form['source'] == "db") {
            $html .= '<br />-----';
            $html .= '<br /><strong>Add new</strong>: ' . $this->showSelectWfqbeRecord($key, $form, $h, 'add_new');
        }

        return $html;
    }


    /**
     * Funzione che visualizza le opzioni di configurazione nel caso di tipo 'select'
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @param    [resource]        $h: connessione al DB
     * @return    [string]        html contenente la configurazione
     */
    function showSelect($key, $form, $h)
    {
        $html = $this->labelInput($key, $form['label']) . '<br />';

        if ($form['source'] == "db") {
            $html .= 'Source: <input onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="static" /> Static - ';
            $html .= '<input checked="checked" onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="db" /> DB';
        } else {
            $html .= 'Source: <input checked="checked" onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="static" /> Static - ';
            $html .= '<input onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="db" /> DB';
        }

        if ($form['multiple'] == "si") {
            $html .= ' - Multiple: <input onclick="javascript:updateForm();" type="checkbox" name="wfqbe[fields][' . $key . '][form][multiple]" value="si" checked="checked" />';
            $html .= ' - Size: <input type="text" size="3" value="' . $form['size'] . '" name="wfqbe[fields][' . $key . '][form][size]" />';
        } else
            $html .= ' - Multiple: <input onclick="javascript:updateForm();" type="checkbox" name="wfqbe[fields][' . $key . '][form][multiple]" value="si" />';

        $html .= '<br />Label for empty value: <input type="text" name="wfqbe[fields][' . $key . '][form][labelEmptyValue]" value="' . $form['labelEmptyValue'] . '" size="30" />';
        $html .= '<br />-----';


        if ($form['source'] == "db") {
            $html .= '<br />-----';
            $html .= '<br />Table: ' . $this->showSelectTable($h, $form['table'], "wfqbe[fields][$key][form][table]");

            if ($form['table'] != '') {
                //$html .= '<br />Field - View: '.$this->showSelectField($h, $form['field_view'], $form['table'], "wfqbe[fields][$key][form][field_view]");
                //$html .= ' # Field - Insert: '.$this->showSelectField($h, $form['field_insert'], $form['table'], "wfqbe[fields][$key][form][field_insert]");

                $html .= '<br />Field - Insert: ' . $this->showSelectField($h, $form['field_insert'], $form['table'], "wfqbe[fields][$key][form][field_insert]");
                $html .= ' # Field - View: ' . $this->showSelectField($h, $form['field_view'], $form['table'], "wfqbe[fields][$key][form][field_view]", 1);
                if ($form['field_view'] != '') {
                    $html .= ' - Separator: <input size="3" type="text" name="wfqbe[fields][' . $key . '][form][field_view_sub][0][sep]" value="' . $form['field_view_sub'][0]['sep'] . '" />';
                    $html .= ' - View 2: ' . $this->showSelectField($h, $form['field_view_sub'][0]['field'], $form['table'], "wfqbe[fields][$key][form][field_view_sub][0][field]", 1);
                    $i = 0;
                    while ($form['field_view_sub'][$i]['field'] != '') {
                        $i++;
                        $html .= ' - Separator: <input size="3" type="text" name="wfqbe[fields][' . $key . '][form][field_view_sub][' . $i . '][sep]" value="' . $form['field_view_sub'][$i]['sep'] . '" />';
                        $html .= ' - View ' . ($i + 2) . ': ' . $this->showSelectField($h, $form['field_view_sub'][$i]['field'], $form['table'], "wfqbe[fields][$key][form][field_view_sub][$i][field]", 1);
                    }
                }

                $html .= '<br />Field - Where: <input size="80" type="text" name="wfqbe[fields][' . $key . '][form][where]" value="' . $form['where'] . '" />';
                $html .= ' # Field - Order by: ' . $this->showSelectField($h, $form['field_orderby'], $form['table'], "wfqbe[fields][$key][form][field_orderby]");
                $html .= '<select name="wfqbe[fields][' . $key . '][form][field_orderby_mod]"><option value=""></option><option value="ASC"' . ($form['field_orderby_mod'] == 'ASC' ? ' selected="selected"' : '') . '>ASC</option><option value="DESC"' . ($form['field_orderby_mod'] == 'DESC' ? ' selected="selected"' : '') . '>DESC</option></select>';
            }

        } else {
            $html .= '<br />-----';
            $html .= '<br />Number of values: <input onblur="javascript:updateForm();" type="text" name="wfqbe[fields][' . $key . '][form][numValues]" value="' . $form['numValues'] . '" size="3" />';


            for ($i = 0; $i < $form['numValues']; $i++) {
                $html .= '<br />Value: <input type="text" name="wfqbe[fields][' . $key . '][form][' . $i . '][value]" value="' . $form[$i]['value'] . '" /> - ';
                $html .= 'Label: <input type="text" name="wfqbe[fields][' . $key . '][form][' . $i . '][label]" value="' . $form[$i]['label'] . '" />';

            }
        }

        if ($form['source'] == "db") {
            $html .= '<br />-----';
            $html .= '<br /><strong>Add new</strong>: ' . $this->showSelectWfqbeRecord($key, $form, $h, 'add_new');
        }

        return $html;
    }


    /**
     * Function used to present the configuration options for the input type 'relation'
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @param    [resource]        $h: connessione al DB
     * @return    [string]        html contenente la configurazione
     */
    function showRelation($key, $form, $h)
    {
        $html = $this->labelInput($key, $form['label']);
        if ($form['multiple'])
            $checked = ' checked="checked"';
        else
            $checked = '';
        $html .= '<br />Multiple: <input type="checkbox" name="wfqbe[fields][' . $key . '][form][multiple]" value="1"' . $checked . ' />';
        if ($form['allow_edit'])
            $checked = ' checked="checked"';
        else
            $checked = '';
        $html .= ' - Allow edit: <input type="checkbox" name="wfqbe[fields][' . $key . '][form][allow_edit]" value="1"' . $checked . ' />';
        if ($form['allow_delete'])
            $checked = ' checked="checked"';
        else
            $checked = '';
        $html .= ' - Allow delete: <input type="checkbox" name="wfqbe[fields][' . $key . '][form][allow_delete]" value="1"' . $checked . ' />';

        $html .= '<br />';

        $html .= '<br />-----';
        $html .= '<br />Table: ' . $this->showSelectTable($h, $form['table'], "wfqbe[fields][$key][form][table]");

        if ($form['table'] != '') {
            $html .= '<br />Field - Insert: ' . $this->showSelectField($h, $form['field_insert'], $form['table'], "wfqbe[fields][$key][form][field_insert]");
            $html .= ' # Field - View: ' . $this->showSelectField($h, $form['field_view'], $form['table'], "wfqbe[fields][$key][form][field_view]", 1);
            if ($form['field_view'] != '') {
                $html .= ' - Separator: <input size="3" type="text" name="wfqbe[fields][' . $key . '][form][field_view_sub][0][sep]" value="' . $form['field_view_sub'][0]['sep'] . '" />';
                $html .= ' - View 2: ' . $this->showSelectField($h, $form['field_view_sub'][0]['field'], $form['table'], "wfqbe[fields][$key][form][field_view_sub][0][field]", 1);
                $i = 0;
                while ($form['field_view_sub'][$i]['field'] != '') {
                    $i++;
                    $html .= ' - Separator: <input size="3" type="text" name="wfqbe[fields][' . $key . '][form][field_view_sub][' . $i . '][sep]" value="' . $form['field_view_sub'][$i]['sep'] . '" />';
                    $html .= ' - View ' . ($i + 2) . ': ' . $this->showSelectField($h, $form['field_view_sub'][$i]['field'], $form['table'], "wfqbe[fields][$key][form][field_view_sub][$i][field]", 1);
                }
            }
        }

        $html .= '<br />-----';
        $html .= '<br /><strong>Add new wizard</strong>: ' . $this->showSelectWfqbeRecord($key, $form, $h, 'add_new');
        $html .= '<br /><strong>Select wizard</strong>: ' . $this->showSelectWfqbeRecord($key, $form, $h, 'select_wizard');

        return $html;
    }


    /**
     * Funzione che visualizza le opzioni di configurazione nel caso di tipo 'PHP Function'
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @return    [string]        html contenente la configurazione
     */
    function showPHPFunction($key, $form)
    {
        //$html = 'require_once: <input type="text" name="wfqbe[fields]['.$key.'][form][require_once]" value="'.$form['require_once'].'" />';
        //$html .= '<br />Function: <input type="text" name="wfqbe[fields]['.$key.'][form][function]" value="'.$form['function'].'" />';
        $html = 'PHP Code: <textarea cols="60" rows="10" name="wfqbe[fields][' . $key . '][form][code]">' . $form['code'] . '</textarea>';
        return $html;
    }


    /**
     * Function that provides a textarea where to put the raw HTML content
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @return    [string]        html contenente la configurazione
     */
    function showRawHTML($key, $form)
    {
        //$html = 'require_once: <input type="text" name="wfqbe[fields]['.$key.'][form][require_once]" value="'.$form['require_once'].'" />';
        //$html .= '<br />Function: <input type="text" name="wfqbe[fields]['.$key.'][form][function]" value="'.$form['function'].'" />';
        $html = 'Raw HTML code: <textarea cols="60" rows="10" name="wfqbe[fields][' . $key . '][form][code]">' . $form['code'] . '</textarea>';
        return $html;
    }


    /**
     * This function lists the tables of the selected database
     *
     * @param    [resource]        $h: connection resource
     * @param    [string]        $selectedTable: selected table
     * @param    [string]        $name: select name attribute
     *
     * @return    [string]        $content: selector box with all the database tables
     */

    function showSelectTable($h, $selectedTable, $name = "wfqbe[table]")
    {
        if ($this->RAW_MODE) {
            $html = '<input type="text" name="' . $name . '" value="' . $selectedTable . '" />';
            $html .= ' <a href="#" onclick="updateForm();"><img title="refresh document" src="img/refresh_n.gif" class="c-inputButton"></a>';

        } else {
            $html = '<select onChange="updateForm();"  name="' . $name . '">';
            $html .= '<option value=""></option>';

            $tabelle = $h->MetaTables(false, true);

            for ($i = 0; $i < sizeof($tabelle); $i++) {
                if ($selectedTable == $tabelle[$i])
                    $html .= '<option value="' . $tabelle[$i] . '" selected="selected">' . $tabelle[$i] . '</option>';
                else
                    $html .= '<option value="' . $tabelle[$i] . '">' . $tabelle[$i] . '</option>';
            }
            $html .= '</select>';
        }

        return $html;
    }


    /**
     * This function lists the columns of the selected table
     *
     * @param    [resource]        $h: connection resource
     * @param    [string]        $selectedField: selected field
     * @param    [string]        $selectedTable: selected table
     * @param    [string]        $name: select name attribute
     *
     * @return    [string]        $content: selector box with all the table fields.
     */

    function showSelectField($h, $selectedField, $selectedTable, $name, $onchange = false, $custom = false)
    {
        if ($this->RAW_MODE) {
            $html = '<input type="text" name="' . $name . '" value="' . $selectedField . '" />';
            $html .= ' <a href="#" onclick="updateForm();"><img title="refresh document" src="img/refresh_n.gif" class="c-inputButton"></a>';
        } else {
            if ($onchange)
                $html = '<select onchange="updateForm()" name="' . $name . '">';
            else
                $html = '<select name="' . $name . '">';
            $html .= '<option  value=""></option>';

            $columns = $h->MetaColumnNames($selectedTable);

            foreach ($columns as $key => $value) {
                if ($selectedField == $value)
                    $html .= '<option  value="' . $value . '" selected="selected">' . $value . '</option>';
                else
                    $html .= '<option  value="' . $value . '">' . $value . '</option>';
            }

            if ($custom) {
                if ($selectedField == 'wfqbe_custom')
                    $html .= '<option  value="wfqbe_custom" selected="selected">WFQBE custom field</option>';
                else
                    $html .= '<option  value="wfqbe_custom">WFQBE custom field</option>';
            }

            $html .= '</select>';
        }

        return $html;

    }


    /**
     * Function used to provide a selector box of wfqbe records
     */
    function showSelectWfqbeRecord($key, $form, $h, $type = 'add_new')
    {
        switch ($type) {
            case 'select_wizard':
                $q_type = 'select';
                break;
            case 'add_new':
            default:
                $q_type = 'insert';
                break;
        }
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, title', 'tx_wfqbe_query', 'deleted!=1 AND type="' . $q_type . '"', '', 'title');
        $html = '';

        if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
            $html = '<select name="wfqbe[fields][' . $key . '][form][' . $type . ']">';
            $html .= '<option value=""></option>';
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                if ($form[$type] == $row['uid'])
                    $html .= '<option selected="selected" value="' . $row['uid'] . '">' . $row['title'] . '</option>';
                else
                    $html .= '<option value="' . $row['uid'] . '">' . $row['title'] . '</option>';
            }
            $html .= '</select>';
        }

        return $html;
    }


    /**
     * Function used to check the fields definitions
     */
    function checkBlocks()
    {
        if (is_array($this->blocks['fields'])) {
            foreach ($this->blocks['fields'] as $field => $form) {
                if ($form['field'] == "") {
                    unset($this->blocks['fields'][$field]);
                    continue;
                }
                switch ($form['type']) {
                    case 'textarea':
                        $this->blocks['fields'][$field]['form']['rows'] = $form['form']['rows'] < 1 ? 5 : $form['form']['rows'];
                        $this->blocks['fields'][$field]['form']['cols'] = $form['form']['cols'] < 1 ? 50 : $form['form']['cols'];
                        break;
                    default:
                        break;
                }
            }
        }
    }


    function clearFunctions($key, $input)
    {
        $content = '';
        if ($input == 'input' || $input == 'textarea') {
            $content = 'Clear input: ';

            $functions = array();
            $functions[] = "strip_tags('|')";
            $functions[] = "htmlspecialchars('|')";
            $functions[] = "intval('|')";

            // Hook that can be used to add pre-process functions
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['clearFunctions'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['clearFunctions'] as $_classRef) {
                    $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                    $functions = $_procObj->add_clear_functions($functions, $input, $this);
                }
            }

            if (is_array($functions) && count($functions) > 0) {
                $content .= '<select name="wfqbe[fields][' . $key . '][form][clear]">';
                $content .= '<option value=""></option>';
                foreach ($functions as $func) {
                    if ($this->blocks['fields'][$key]['form']['clear'] == $func)
                        $content .= '<option value="' . $func . '" selected="selected">' . $func . '</option>';
                    else
                        $content .= '<option value="' . $func . '">' . $func . '</option>';
                }
                $content .= '</select>';
            }

            $content .= '<br />';
        }
        return $content;
    }


    /**
     *
     * Utilizza la funzione xml2array() definita nella classe tx_wfqbe_api_xml2array che converte un file xml in un array
     *
     * @return    [void]
     */

    function parseModule()
    {

        $API = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_api_xml2array");

        $var = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
        //estraggo la query salvata dal database (modalità xml) e la converto in array
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('insertq', 'tx_wfqbe_query', 'tx_wfqbe_query.uid=' . intval($var['uid']) . ' AND tx_wfqbe_query.deleted!=1', '', '', '');
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $this->blocks = $API->xml2array($row["insertq"]);
    }


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/tx_wfqbe_query_insert/class.tx_wfqbe_insertform_generator.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/tx_wfqbe_query_insert/class.tx_wfqbe_insertform_generator.php']);
}
