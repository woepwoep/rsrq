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

$GLOBALS['LANG']->includeLLFile('EXT:wfqbe/tx_wfqbe_query_search/locallang.xml');


class tx_wfqbe_searchform_generator
{
    var $extKey = 'wfqbe';    // The extension key.

    var $blocks;
    var $markers;

    // Elenco dei tipi di input disponibili
    var $types = array('input', 'radio', 'select', 'check', 'calendar');

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

    function showForm($h, $link)
    {
        $content = '<table style="font-size: 0.9em" class="table table-striped">';
        $numForm = 0;

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

        $form_positions = '<option value=""></option>';
        if (is_array($this->blocks['fields'])) {
            $i = 1;
            foreach ($this->blocks['fields'] as $key => $value) {
                if ($i < count($this->blocks['fields']))
                    $form_positions .= '<option value="' . $key . '">' . $key . '</option>';
                $i++;
            }
        }


        $this->markers = $this->getMarkers();

        if (!is_array($this->markers)) {
            $content = '<tr class="db_list_normal"><td>No marker has been used in the query.</td></tr>';
            return $content . '</tabl>';
        }

        $this->markers[] = 'CUSTOM';

        if (is_array($this->blocks['fields'])) {
            foreach ($this->blocks['fields'] as $key => $value) {
                if ($value["marker"] != "") {
                    $content .= $this->renderField($numForm, $value, $h);
                    $numForm++;
                }
            }
        }


        $content .= $this->newEmptyField($numForm);

        return $content . '</table>';
    }


    /**
     * Function used to render a search field
     * @return unknown_type
     */
    function renderField($key, $value, $h)
    {
        $content = '';
        $numForm = $key;
        if ($numForm % 2 == 0)
            $backgroundColor = 'db_list_normal';
        else
            $backgroundColor = 'db_list_normal';

        $content .= '<tr class="' . $backgroundColor . '" id="field-' . $numForm . '" ><td>' . $numForm . ' - ';
        $content .= $this->showSelectMarkers($key, $value['marker']);

        if ($numForm > 0)
            $content .= ' <a href="#" title="Move up (' . ($numForm) . ')" onclick="document.getElementById(\'wfqbe_up\').value=' . ($numForm) . '; updateForm()"><img src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3/sysext/t3skin/icons/gfx/arrowup.png" /></a>';
        if ($numForm < sizeof($this->blocks['fields']) - 2)
            $content .= ' <a href="#" title="Move down (' . ($numForm) . ')" onclick="document.getElementById(\'wfqbe_down\').value=' . ($numForm) . '; updateForm()"><img src="' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3/sysext/t3skin/icons/gfx/arrowdown.png" /></a>';
//		$content.= ' Exchange with: <select onchange="document.getElementById(\'wfqbe_move_from\').value='.($numForm+1).'; document.getElementById(\'wfqbe_move_to\').value=document.getElementById(\'wfqbe_this_'.$numForm.'\').value; updateForm();" id="wfqbe_this_'.$numForm.'" name="wfqbe_move_to">'.$form_positions.'</select></a>';

        if ($value['marker'] == 'CUSTOM')
            $content .= ' ' . $GLOBALS['LANG']->getLL('unique_id') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][unique_id]" value="' . $value['form']['unique_id'] . '" />';

        $content .= '<br />' . $this->showSelectType($key, $value['type']);

        $content .= ' - ' . $GLOBALS['LANG']->getLL('update_change') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][onchange]" value="' . $value['form']['onchange'] . '" />';

        $content .= '<br />' . $GLOBALS['LANG']->getLL('custom_id') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][custom_id]" value="' . $value['form']['custom_id'] . '" />';
        $content .= ' - ' . $GLOBALS['LANG']->getLL('additional_attributes') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][additional_attributes]" value="' . $value['form']['additional_attributes'] . '" />';

        $content .= '<br />';

        switch ($value['type']) {
            case 'input':
                $content .= $this->showInput($key, $value['form']);
                break;
            case 'radio':
                $content .= $this->showRadio($key, $value['form'], $h);
                break;
            case 'select':
                $content .= $this->showSelect($key, $value['form'], $h);
                break;
            // check option added by MFG
            case 'check':
                $content .= $this->showCheck($key, $value['form'], $h);
                break;
            case 'calendar':
                $content .= $this->showCalendar($key, $value['form'], $h);
                break;
        }

        $content .= '<hr style="border: 1px;" /></td></tr>';

        return $content;
    }


    /**
     * Function used to create a selector box with markers
     * @param unknown_type $numForm
     * @return unknown_type
     */
    function newEmptyField($numForm)
    {
        $content = '';
        if ($numForm % 2 == 0)
            $backgroundColor = 'db_list_normal';
        else
            $backgroundColor = 'db_list_normal';
        $content .= '<tr class="' . $backgroundColor . '" id="field-' . (++$numForm) . '"><td>';
        $content .= $this->showSelectMarkers(++$numForm, "");
        $content .= '</td></tr>';

        return $content;
    }


    /**
     * Funzione che restituisce un array dei marcatori presenti nella query
     *
     * @return array array di tutti i marcatori definiti nella query
     */
    function getMarkers()
    {
        $var = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
        // There are problems with the JOIN if using DBAL
        //$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('q.query', 'tx_wfqbe_query AS s JOIN tx_wfqbe_query AS q ON s.searchinquery=q.uid', 's.uid="'.$var['uid'].'" AND s.deleted=0 AND q.deleted=0', '', '','');
        $resS = $GLOBALS['TYPO3_DB']->exec_SELECTquery('searchinquery', 'tx_wfqbe_query', 'uid=' . intval($var['uid']) . ' AND deleted=0', '', '', '');
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($resS) == 1) {
            $rowS = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resS);
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('query', 'tx_wfqbe_query', 'uid=' . intval($rowS['searchinquery']) . ' AND deleted=0', '', '', '');
            if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == 1)
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        }
        if (preg_match_all("/([#]{3})([a-z,A-Z,0-9,@,!,_]*)([#]{3})/", $row["query"], $markers))
            return $markers[2];
        else
            return null;
    }


    /**
     * Funzione che restituisce una select dei marcatori presenti nella query
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [string]        $selected: marcatore
     * @return    [string]        html contenente la select per i marcatori
     */
    function showSelectMarkers($key, $selected)
    {
        //$html = '<select onchange="updateForm()" name="wfqbe[fields]['.$key.'][marker]">';
        $html = '<select onchange="updateForm(' . $key . ')" name="wfqbe[fields][' . $key . '][marker]">';
        $html .= '<option value=""></option>';
        foreach ($this->markers as $value) {
            if ($value == $selected)
                $html .= '<option selected="selected" value="' . $value . '">' . $value . '</option>';
            else
                $html .= '<option value="' . $value . '">' . $value . '</option>';
        }
        $html .= '</select>';
        return $html;
    }


    /**
     * Funzione che restituisce una select dei tipi possibili di input
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [string]        $selected: tipo
     * @return    [string]        html contenente la select per i tipi
     */
    function showSelectType($key, $selected)
    {
        $html = $GLOBALS['LANG']->getLL('field_type') . ' <select onchange="updateForm()" name="wfqbe[fields][' . $key . '][type]"';
        $html .= '<option value=""></option>';
        foreach ($this->types as $value) {
            if ($value == $selected)
                $html .= '<option selected="selected" value="' . $value . '">' . $GLOBALS['LANG']->getLL($value) . '</option>';
            else
                $html .= '<option value="' . $value . '">' . $GLOBALS['LANG']->getLL($value) . '</option>';
        }
        $html .= '</select>';
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
        // $html = 'Label: <input type="text" name="wfqbe[fields]['.$key.'][form][label][def]" value="'.$label['def'].'" />';
        $html = $GLOBALS['LANG']->getLL('label') . ' (default): <input type="text" name="wfqbe[fields][' . $key . '][form][label][def]" value="' . $label['def'] . '" />';
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_language', 'hidden=0', '', 'title ASC');
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $html .= '<br />&nbsp;&nbsp;&nbsp;' . $GLOBALS['LANG']->getLL('label') . ' (' . $row['title'] . '): <input type="text" name="wfqbe[fields][' . $key . '][form][label][' . $row['uid'] . ']" value="' . $label[$row['uid']] . '" />';
        }

        return $html;
    }


    /**
     * Funzione che visualizza le opzioni di configurazione nel caso di tipo 'input'
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @return    [string]        html contenente la configurazione
     */
    function showInput($key, $form)
    {
        $html = $this->labelInput($key, $form['label']);

        if ($form['hidden'] == "si") {
            $html .= ' - ' . $GLOBALS['LANG']->getLL('hidden') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][hidden]" value="si" checked="checked" />';
        } else {
            $html .= ' - ' . $GLOBALS['LANG']->getLL('hidden') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][hidden]" value="si" />';
        }

        $html .= ' - ' . $GLOBALS['LANG']->getLL('size') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][size]" value="' . $form['size'] . '" size="3" />';
        $html .= ' - ' . $GLOBALS['LANG']->getLL('maxlength') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][maxlength]" value="' . $form['maxlength'] . '" size="3" />';

        return $html;
    }


    /**
     * Funzione che visualizza le opzioni di configurazione nel caso di tipo 'radio'
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @return    [string]        html contenente la configurazione
     */
    function showRadio($key, $form, $h)
    {
        $html = $this->labelInput($key, $form['label']) . '<br />';
        //$html .= 'Number of values: <input onblur="javascript:updateForm();" type="text" name="wfqbe[fields]['.$key.'][form][numValues]" value="'.$form['numValues'].'" size="3" />';

        if ($form['source'] == "db") {
            $html .= $GLOBALS['LANG']->getLL('source') . ': <input onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="static" /> ' . $GLOBALS['LANG']->getLL('static') . ' - ';
            $html .= '<input checked="checked" onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="db" /> ' . $GLOBALS['LANG']->getLL('db');
        } else {
            $html .= $GLOBALS['LANG']->getLL('source') . ': <input checked="checked" onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="static" /> ' . $GLOBALS['LANG']->getLL('static') . ' - ';
            $html .= '<input onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="db" /> ' . $GLOBALS['LANG']->getLL('db');
        }

        $html .= '<br />';

        if ($form['source'] == "db") {
            $html .= '<br />-----';
            $html .= '<br />' . $GLOBALS['LANG']->getLL('table') . ': ' . $this->showSelectTable($h, $form['table'], $key);

            if ($form['table'] != '') {
                $html .= '<br />' . $GLOBALS['LANG']->getLL('field_view') . ': ' . $this->showSelectField($h, $form['field_view'], $form['table'], "wfqbe[fields][$key][form][field_view]");
                $html .= ' # ' . $GLOBALS['LANG']->getLL('field_insert') . ': ' . $this->showSelectField($h, $form['field_insert'], $form['table'], "wfqbe[fields][$key][form][field_insert]");
                $html .= '<br />' . $GLOBALS['LANG']->getLL('field_where') . ': <input size="80" type="text" name="wfqbe[fields][' . $key . '][form][where]" value="' . $form['where'] . '" />';
            }

        } else {
            $html .= '<br />-----';
            $html .= '<br />' . $GLOBALS['LANG']->getLL('number_values') . ': <input onblur="javascript:updateForm();" type="text" name="wfqbe[fields][' . $key . '][form][numValues]" value="' . $form['numValues'] . '" size="3" />';


            for ($i = 0; $i < $form['numValues']; $i++) {
                $html .= '<br />' . $GLOBALS['LANG']->getLL('value') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][' . $i . '][value]" value="' . $form[$i]['value'] . '" /> - ';
                $html .= $GLOBALS['LANG']->getLL('label') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][' . $i . '][label]" value="' . $form[$i]['label'] . '" />';

            }
        }

        return $html;
    }


    /**
     * Function for option 'check'
     *
     * added by MFG
     */
    function showCheck($key, $form, $h)
    {
        $html = $this->labelInput($key, $form['label']) . '<br />';
        //$html .= 'Number of values: <input onblur="javascript:updateForm();" type="text" name="wfqbe[fields]['.$key.'][form][numValues]" value="'.$form['numValues'].'" size="3" />';

        if ($form['source'] == "db") {
            $html .= $GLOBALS['LANG']->getLL('source') . ': <input onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="static" /> ' . $GLOBALS['LANG']->getLL('static') . ' - ';
            $html .= '<input checked="checked" onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="db" /> ' . $GLOBALS['LANG']->getLL('db');
        } else {
            $html .= $GLOBALS['LANG']->getLL('source') . ': <input checked="checked" onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="static" /> ' . $GLOBALS['LANG']->getLL('static') . ' - ';
            $html .= '<input onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="db" /> ' . $GLOBALS['LANG']->getLL('db');
        }

        $html .= '<br />';

        if ($form['source'] == "db") {
            $html .= '<br />-----';
            $html .= '<br />' . $GLOBALS['LANG']->getLL('table') . ': ' . $this->showSelectTable($h, $form['table'], $key);

            if ($form['table'] != '') {
                $html .= '<br />' . $GLOBALS['LANG']->getLL('field_view') . ': ' . $this->showSelectField($h, $form['field_view'], $form['table'], "wfqbe[fields][$key][form][field_view]");
                $html .= ' # ' . $GLOBALS['LANG']->getLL('field_insert') . ': ' . $this->showSelectField($h, $form['field_insert'], $form['table'], "wfqbe[fields][$key][form][field_insert]");
                $html .= '<br />' . $GLOBALS['LANG']->getLL('field_where') . ': <input size="80" type="text" name="wfqbe[fields][' . $key . '][form][where]" value="' . $form['where'] . '" />';
            }

        } else {
            $html .= '<br />-----';
            $html .= '<br />' . $GLOBALS['LANG']->getLL('number_values') . ': <input onblur="javascript:updateForm();" type="text" name="wfqbe[fields][' . $key . '][form][numValues]" value="' . $form['numValues'] . '" size="3" />';


            for ($i = 0; $i < $form['numValues']; $i++) {
                $html .= '<br />' . $GLOBALS['LANG']->getLL('value') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][' . $i . '][value]" value="' . $form[$i]['value'] . '" /> - ';
                $html .= $GLOBALS['LANG']->getLL('label') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][' . $i . '][label]" value="' . $form[$i]['label'] . '" />';

            }
        }

        return $html;
    }


    /**
     * Funzione che visualizza le opzioni di configurazione nel caso di tipo 'input'
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @return    [string]        html contenente la configurazione
     */
    function showSelect($key, $form, $h)
    {
        $html = $this->labelInput($key, $form['label']) . '<br />';

        if ($form['source'] == "db") {
            $html .= $GLOBALS['LANG']->getLL('source') . ': <input onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="static" /> ' . $GLOBALS['LANG']->getLL('static') . ' - ';
            $html .= '<input checked="checked" onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="db" /> ' . $GLOBALS['LANG']->getLL('db');
        } else {
            $html .= $GLOBALS['LANG']->getLL('source') . ': <input checked="checked" onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="static" /> ' . $GLOBALS['LANG']->getLL('static') . ' - ';
            $html .= '<input onclick="javascript:updateForm();" type="radio" name="wfqbe[fields][' . $key . '][form][source]" value="db" /> ' . $GLOBALS['LANG']->getLL('db');
        }

        if ($form['multiple'] == "si") {
            $html .= ' - ' . $GLOBALS['LANG']->getLL('multiple') . ': <input onclick="javascript:updateForm();" type="checkbox" name="wfqbe[fields][' . $key . '][form][multiple]" value="si" checked="checked" />';
            $html .= ' - ' . $GLOBALS['LANG']->getLL('size') . ' <input type="text" size="3" value="' . $form['size'] . '" name="wfqbe[fields][' . $key . '][form][size]" />';
        } else
            $html .= ' - ' . $GLOBALS['LANG']->getLL('multiple') . ': <input onclick="javascript:updateForm();" type="checkbox" name="wfqbe[fields][' . $key . '][form][multiple]" value="si" />';

        if ($form['required'] == "si") {
            $html .= ' - ' . $GLOBALS['LANG']->getLL('required') . ': <input onclick="javascript:updateForm();" type="checkbox" name="wfqbe[fields][' . $key . '][form][required]" value="si" checked="checked" />';
        } else
            $html .= ' - ' . $GLOBALS['LANG']->getLL('required') . ': <input onclick="javascript:updateForm();" type="checkbox" name="wfqbe[fields][' . $key . '][form][required]" value="si" />';

        $html .= '<br />Label for empty value: <input type="text" name="wfqbe[fields][' . $key . '][form][labelEmptyValue]" value="' . $form['labelEmptyValue'] . '" size="30" />';

        $html .= '<br />';

        if ($form['source'] == "db") {
            $html .= '<br />-----';
            $html .= '<br />' . $GLOBALS['LANG']->getLL('table') . ': ' . $this->showSelectTable($h, $form['table'], $key);

            if ($form['table'] != '') {
                $html .= '<br />' . $GLOBALS['LANG']->getLL('field_view') . ': ' . $this->showSelectField($h, $form['field_view'], $form['table'], "wfqbe[fields][$key][form][field_view]");
                $html .= ' # ' . $GLOBALS['LANG']->getLL('field_insert') . ': ' . $this->showSelectField($h, $form['field_insert'], $form['table'], "wfqbe[fields][$key][form][field_insert]");
                $html .= '<br />Field - Where: <input size="80" type="text" name="wfqbe[fields][' . $key . '][form][where]" value="' . $form['where'] . '" />';
                $html .= '<br />' . $GLOBALS['LANG']->getLL('field_orderby') . ': ' . $this->showSelectField($h, $form['field_orderby'], $form['table'], "wfqbe[fields][$key][form][field_orderby]");
                $html .= '# <select name="wfqbe[fields][' . $key . '][form][field_orderby_mode]">' .
                    '<option ' . ($form['field_orderby_mode'] == '' ? 'selected="selected"' : '') . ' value=""></option>' .
                    '<option ' . ($form['field_orderby_mode'] == 'ASC' ? 'selected="selected"' : '') . ' value="ASC">' . $GLOBALS['LANG']->getLL('field_orderby_asc') . '</option>' .
                    '<option ' . ($form['field_orderby_mode'] == 'DESC' ? 'selected="selected"' : '') . ' value="DESC">' . $GLOBALS['LANG']->getLL('field_orderby_desc') . '</option>' .
                    '</select>';

            }

            $html .= '<br />Custom query: <textarea rows="5" cols="80" name="wfqbe[fields][' . $key . '][form][customquery]">' . $form['customquery'] . '</textarea><br /><em>(ex. SELECT DISTINCT field_view, field_insert FROM table)</em><br />';

        } else {
            $html .= '<br />-----';
            $html .= '<br />' . $GLOBALS['LANG']->getLL('number_values') . ': <input onblur="javascript:updateForm();" type="text" name="wfqbe[fields][' . $key . '][form][numValues]" value="' . $form['numValues'] . '" size="3" />';


            for ($i = 0; $i < $form['numValues']; $i++) {
                $html .= '<br />' . $GLOBALS['LANG']->getLL('value') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][' . $i . '][value]" value="' . $form[$i]['value'] . '" /> - ';
                $html .= $GLOBALS['LANG']->getLL('label') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][' . $i . '][label]" value="' . $form[$i]['label'] . '" />';

            }
        }

        return $html;
    }


    /**
     * Funzione che visualizza le opzioni di configurazione nel caso di tipo 'calendar'
     *
     * @param    [int]            $key: indice dell'array del form attuale
     * @param    [array]            $form: configurazione del campo
     * @return    [string]        html contenente la configurazione
     */
    function showCalendar($key, $form)
    {
        $html = $this->labelInput($key, $form['label']);

        if ($form['date2cal'] == "si") {
            $html .= ' - Use old date2cal ext: <input type="checkbox" onchange="javascript:updateForm();" name="wfqbe[fields][' . $key . '][form][date2cal]" value="si" checked="checked" />';
            if ($form['time'] == "si") {
                $html .= ' - ' . $GLOBALS['LANG']->getLL('time') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][time]" value="si" checked="checked" />';
            } else {
                $html .= ' - ' . $GLOBALS['LANG']->getLL('time') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][time]" value="si" />';
            }

            $html .= ' - ' . $GLOBALS['LANG']->getLL('format') . ': <input type="text" name="wfqbe[fields][' . $key . '][form][format]" value="' . $form['format'] . '" size="25" />';


            if ($form['nlp'] == "si") {
                $html .= ' - ' . $GLOBALS['LANG']->getLL('nlp') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][nlp]" value="si" checked="checked" />';
            } else {
                $html .= ' - ' . $GLOBALS['LANG']->getLL('nlp') . ': <input type="checkbox" name="wfqbe[fields][' . $key . '][form][nlp]" value="si" />';
            }
        } else {
            // jQuery datepicker mode
            $html .= ' - Use old date2cal ext: <input type="checkbox" onchange="javascript:updateForm();" name="wfqbe[fields][' . $key . '][form][date2cal]" value="si" />';

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

        $html .= '<br /><br /><strong>If you don\'t use the (deprecated) date2cal extension, in order to get the calendar to work in the frontend you need the jQuery datepicker plugin. In the backend it is rendered based on the standard extjs TYPO3 calendar.</strong><br />';


        return $html;
    }


    /**
     * Crea un elemento "select" dove gli elementi "option" contengono le tabelle del database selezionato.
     *
     * @param    [resource]        $h: puntatore alla connessione
     * @param    [string]        $selectedTable: tabella selezionata
     * @param    [int]            $key: indice dell'array del form attuale
     *
     * @return    [string]    $content: stringa che contiene html degli elementi select per la selezione delle tabelle
     */

    function showSelectTable($h, $selectedTable, $key)
    {
        $html = '<select onChange="updateForm();"  name="wfqbe[fields][' . $key . '][form][table]">';
        $html .= '<option value=""></option>';

        $tabelle = $h->MetaTables(false, true);

        for ($i = 0; $i < sizeof($tabelle); $i++) {
            if ($selectedTable == $tabelle[$i])
                $html .= '<option value="' . $tabelle[$i] . '" selected="selected">' . $tabelle[$i] . '</option>';
            else
                $html .= '<option value="' . $tabelle[$i] . '">' . $tabelle[$i] . '</option>';
        }
        $html .= '</select>';

        if (sizeof($tabelle) == 0) {
            $html = '<input onChange="updateForm();" type="text" name="wfqbe[fields][' . $key . '][form][table]" value="' . $selectedTable . '" size="40" />';

        }

        return $html;
    }


    /**
     * Visualizza  le colonne delle tabelle selezionate
     *
     * @param    [resource]        $h: puntatore alla connessione
     * @param    [string]        $selectedField: campo selezionato
     * @param    [string]        $selectedTable: tabella selezionata
     * @param    [string]        $name: nome del campo
     *
     * @return    [string]    $content: stringa che contiene l'html della text area che contiene tutti i campi della tabella.
     */

    function showSelectField($h, $selectedField, $selectedTable, $name)
    {
        $html = '<select name="' . $name . '">';
        $html .= '<option  value=""></option>';

        $columns = $h->MetaColumnNames($selectedTable);

        if (!is_array($columns)) {
            $html = "<input type='text' name='" . $name . "' value='" . $selectedField . "' />";
            return $html;
        }
        foreach ($columns as $key => $value) {
            if ($selectedField == $value)
                $html .= '<option  value="' . $value . '" selected="selected">' . $value . '</option>';
            else
                $html .= '<option  value="' . $value . '">' . $value . '</option>';
        }
        $html .= '</select>';


        return $html;

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
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('search', 'tx_wfqbe_query', 'tx_wfqbe_query.uid=' . intval($var['uid']) . ' AND tx_wfqbe_query.deleted!=1', '', '', '');
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $saveXml = $API->xml2array($row["search"]);
        //siccome per rispettare la sintassi xml aggiungo dei tag contenitori quando salvo(vedi funzione saveQuery) adesso estraggo solo
        //la parte utile per costruire il form o la text area.

        if (is_array($saveXml) && is_array($saveXml['fields']))
            $this->blocks = $saveXml;
        else
            $this->blocks['fields'] = $saveXml;
    }


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/tx_wfqbe_query_search/class.tx_wfqbe_searchform_generator.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/tx_wfqbe_query_search/class.tx_wfqbe_searchform_generator.php']);
}
