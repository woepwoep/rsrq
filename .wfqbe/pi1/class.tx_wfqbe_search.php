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
 * Search class for the 'wfqbe' extension.
 *
 */
class tx_wfqbe_search
{

    var $conf;
    var $cObj;
    var $pibase;
    var $prefixId = 'tx_wfqbe_pi1';
    var $row = array();

    function main($conf, $cObj, $pibase)
    {
        $this->conf = $conf;
        $this->cObj = $cObj;
        $this->pibase = $pibase;
    }


    function getBlocks($row, $h)
    {
        $API = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_api_xml2array");

        $blocks = "";
        if ($row["search"] != "") {
            $blocks = $API->xml2array($row["search"]);
        }

        if (!is_array($blocks['fields']))
            $blocks['fields'] = $blocks;

        return $blocks;
    }


    function do_sGetForm($row, $h, &$form_built)
    {
        $blocks = $this->getBlocks($row, $h);
        if ($this->pibase->beMode != '')
            $file = @file_get_contents(PATH_site . $GLOBALS['TSFE']->tmpl->getFileName($this->conf['template']));
        else
            $file = $this->cObj->fileResource($this->conf['template']);
        $file = $this->cObj->getSubpart($file, '###SEARCH_TEMPLATE###');

        // Search module creation
        if (is_array($blocks['fields']) && !$form_built) {
            $content .= $this->searchForm($file, $blocks['fields'], $h, $row);
            $form_built = true;
        }
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1 && ($this->conf['ff_data']['resultsPage'] == $GLOBALS['TSFE']->id || $this->conf['ff_data']['resultsPage'] == '')) {
            $mA['###XAJAX_SUBMIT###'] = 'onsubmit="' . $this->prefixId . 'processResultsData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_form\')); return false;"';
        } else {
            $mA['###XAJAX_SUBMIT###'] = '';
        }
        $mA['###CONF_DIVID###'] = $this->conf['ff_data']['div_id'];

        $params = array();
        $params['parameter'] = ($this->conf['ff_data']['resultsPage'] == "" ? $GLOBALS['TSFE']->id : $this->conf['ff_data']['resultsPage']);
        $mA['###CONF_SEARCH###'] = $this->cObj->typoLink_URL($params);

        $mA['###LABEL_SEARCH###'] = $this->pibase->pi_getLL('search_submit', 'Search');

        $content = $this->cObj->substituteMarkerArray($content, $mA);
        return $content;
    }

    function do_sGetFormResult($row, $h)
    {
        // SEARCH
        $not_used = true;
        $content = $this->do_sGetForm($row, $h, $not_used);
        return $content;
    }


    function searchForm($content, $blocks, $h, $row)
    {
        $this->row = $row;
        $blockTemplate = $this->cObj->getSubpart($content, '###SEARCH_BLOCK###');
        $blockList = '';
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1 && $this->conf['ff_data']['resultsPage'] == $GLOBALS['TSFE']->id)
            $blockList .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_results_query]" value="' . $row['searchinquery'] . '" />';

        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1) {
            $blockList .= '<input type="hidden" id="wfqbe_destination_id" name="tx_wfqbe_pi1[wfqbe_destination_id]" value="" />';
            $blockList .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_this_query]" value="' . $row['uid'] . '" />';
            $blockList .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_results_query]" value="' . $row['searchinquery'] . '" />';
        }

        $i = 0;
        foreach ($blocks as $key => $value) {
            if ($value['marker'] == "")
                continue;

            if ($value['marker'] == 'CUSTOM' && $value['form']['unique_id'] != '')
                $value['marker'] = $value['form']['unique_id'];
            elseif ($value['marker'] == 'CUSTOM')
                $value['marker'] = $value['marker'] . '_' . $i;
            $fieldContent = $this->getSearchField($key, $value, $h, $blockTemplate, ($i % 2), $row);
            if ($fieldContent != '') {
                $blockList .= $fieldContent;
                $i++;
            }
        }

        $content = $this->cObj->substituteSubpart($content, '###SEARCH_BLOCK###', $blockList, 0, 0);
        return $content;
    }


    function getSearchField($key, $value, $h, $blockTemplate, $odd = 0, $row)
    {
        $rA['###SEARCH_ID###'] = $value['marker'] . '_' . $key;
        $rA['###SEARCH_ID_FIELD###'] = 'FIELD_' . $value['marker'] . '_' . $key;

        if (!is_array($value['form']['label']))
            $rA['###SEARCH_LABEL###'] = $value['form']['label'];
        else {
            // This will show the label in the correct language
            if ($GLOBALS['TSFE']->sys_language_uid == 0 || $value['form']['label'][$GLOBALS['TSFE']->sys_language_uid] == '')
                $rA['###SEARCH_LABEL###'] = $value['form']['label']['def'];
            else
                $rA['###SEARCH_LABEL###'] = $value['form']['label'][$GLOBALS['TSFE']->sys_language_uid];
        }

        // Get field name
        if (strpos($value['marker'], 'CUSTOM') == 0 && strpos($value['marker'], 'CUSTOM') !== false)
            if ($value['form']['unique_id'] != '')
                $name = $value['form']['unique_id'];
            else
                $name = $value['marker'];
        else
            $name = substr($value['marker'], 6);

        if ($name != '' && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->conf["customSearch."][$row['uid'] . "."]['excludeFields'], $name)) {
            return '';
        }

        switch ($value['type']) {
            case 'input':
                $rA['###SEARCH_FIELD###'] = $this->showInput($value, $name, $rA['###SEARCH_ID_FIELD###'], $blockTemplate);
                break;
            case 'radio':
                $rA['###SEARCH_FIELD###'] = $this->showRadio($value, $name, $h, $rA['###SEARCH_ID_FIELD###']);
                break;
            case 'select':
                $rA['###SEARCH_FIELD###'] = $this->showSelect($value, $name, $h, $rA['###SEARCH_ID_FIELD###']);
                break;
            // check option added by MFG
            case 'check':
                $rA['###SEARCH_FIELD###'] = $this->showCheck($value, $name, $h, $rA['###SEARCH_ID_FIELD###']);
                break;
            case 'calendar':
                $rA['###SEARCH_FIELD###'] = $this->showCalendar($value, $name, $rA['###SEARCH_ID_FIELD###'], $blockTemplate);
                break;
        }
        if ($odd)
            $rA['###WFQBE_CLASS###'] = $this->conf['classes.']['odd'];
        else
            $rA['###WFQBE_CLASS###'] = $this->conf['classes.']['even'];

        return $this->cObj->substituteMarkerArray($blockTemplate, $rA);
    }


    function showInput($value, $name, $id, &$blockTemplate)
    {
        $wfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_wfqbe_pi1');

        if ($value['form']['onchange'] != "") {
            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1) {
                $update = ' onblur="document.getElementById(\'wfqbe_destination_id\').value=\'' . $value['form']['onchange'] . '\'; ' . $this->prefixId . 'processSearchData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_form\')); return false;"';
            } else {
                $params = array();
                $params['parameter'] = $GLOBALS['TSFE']->id;
                $action = $this->cObj->typoLink_URL($params);
                $update = ' onblur="document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').action=\'' . $action . '\'; document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').onsubmit=\'\'; submit();"';
            }
        } else {
            $update = '';
        }

        if ($value['form']['hidden'] == 'si') {
            $type = 'hidden';
            $blockTemplate = $this->cObj->substituteSubpart($blockTemplate, '###FIELD_BLOCK###', '###SEARCH_FIELD###');
        } else {
            $type = 'text';
        }

        $additionalParams = '';
        if ($value['form']['size'] != '' && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($value['form']['size']))
            $additionalParams .= ' size="' . $value['form']['size'] . '"';
        if ($value['form']['maxlength'] != '' && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($value['form']['maxlength']))
            $additionalParams .= ' maxlength="' . $value['form']['maxlength'] . '"';
        if ($value['form']['additional_attributes'] != '')
            $additionalParams .= ' ' . $value['form']['additional_attributes'];

        if ($value['form']['custom_id'] != "")
            $id = $value['form']['custom_id'];

        return '<input class="form-control" ' . $additionalParams . ' id="' . $id . '" type="' . $type . '" name="tx_wfqbe_pi1[' . $name . ']" value="' . $wfqbe[$name] . '"' . $update . ' />';
    }


    function showRadio($value, $name, $h, $id)
    {
        $wfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_wfqbe_pi1');
        $listaRadio = '';

        if ($value['form']['onchange'] != "") {
            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1) {
                $update = ' onchange="document.getElementById(\'wfqbe_destination_id\').value=\'' . $value['form']['onchange'] . '\'; ' . $this->prefixId . 'processSearchData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_form\')); return false;"';
            } else {
                $params = array();
                $params['parameter'] = $GLOBALS['TSFE']->id;
                $action = $this->cObj->typoLink_URL($params);
                $update = ' onchange="document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').action=\'' . $action . '\'; document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').onsubmit=\'\'; submit();"';
            }
        } else {
            $update = '';
        }

        if ($value['form']['additional_attributes'] != '')
            $update .= ' ' . $value['form']['additional_attributes'];

        if ($value['form']['source'] == 'static') {
            for ($i = 0; $i < $value['form']['numValues']; $i++) {
                if ($i == 0) {
                    if ($value['form']['custom_id'] != "")
                        $idi = ' id="' . $value['form']['custom_id'] . '"';
                    else
                        $idi = ' id="' . $id . '"';
                } else
                    $idi = '';
                if ($value['form'][$i]['value'] == $wfqbe[$name] || $value['form'][$i]['value'] == $this->pibase->piVars[$name])
                    $listaRadio .= '<input' . $idi . $update . ' checked="checked" type="radio" name="tx_wfqbe_pi1[' . $name . ']" value="' . $value['form'][$i]['value'] . '" /> ' . $value['form'][$i]['label'];
                else
                    $listaRadio .= '<input' . $idi . $update . ' type="radio" name="tx_wfqbe_pi1[' . $name . ']" value="' . $value['form'][$i]['value'] . '" /> ' . $value['form'][$i]['label'];
                if ($i < $value['form']['numValues'] - 1)
                    $listaRadio .= '<br />';
            }
        } else {
            // Source == db
            $where = "";
            if ($value['form']['where'] != "") {
                $where = "WHERE " . $this->substituteSearchMarkers($value['form']['where']) . " ";
            }
            $query = 'SELECT DISTINCT ' . $value['form']['field_view'] . ', ' . $value['form']['field_insert'] . ' FROM ' . $value['form']['table'] . ' ' . $where . 'ORDER BY ' . $value['form']['field_view'];
            $ris = $h->Execute($query);

            $emptyOption = false;
            while ($array = $ris->FetchRow()) {
                if ($array[1] == "")
                    $emptyOption = true;
                if ($array[1] == $wfqbe[$name] || $array[1] == $this->pibase->piVars[$name])
                    $listaRadio .= '<input' . $update . ' checked="checked" type="radio" name="tx_wfqbe_pi1[' . $name . ']" value="' . $array[1] . '" /> ' . $array[0] . '<br />';
                else
                    $listaRadio .= '<input' . $update . ' type="radio" name="tx_wfqbe_pi1[' . $name . ']" value="' . $array[1] . '" /> ' . $array[0] . '<br />';
            }
            if (!$emptyOption) {
                if ($value['form']['custom_id'] != "")
                    $id = $value['form']['custom_id'];

                if ($wfqbe[$name] == "")
                    $listaRadio = '<input id="' . $id . '"' . $update . ' checked="checked" type="radio" name="tx_wfqbe_pi1[' . $name . ']" value="" /> <br />' . $listaRadio;
                else
                    $listaRadio = '<input id="' . $id . '"' . $update . ' type="radio" name="tx_wfqbe_pi1[' . $name . ']" value="" /> <br />' . $listaRadio;
            }
            if ($listaRadio != '')
                $listaRadio = substr($listaRadio, 0, -6);
        }

        return $listaRadio;
    }


    // added by MFG: function showCheck to show search with option "check"

    function showCheck($value, $name, $h, $id)
    {
        $wfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_wfqbe_pi1');
        $listCheck = '';

        if ($value['form']['onchange'] != "") {
            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1) {
                $update = ' onchange="document.getElementById(\'wfqbe_destination_id\').value=\'' . $value['form']['onchange'] . '\'; ' . $this->prefixId . 'processSearchData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_form\')); return false;"';
            } else {
                $params = array();
                $params['parameter'] = $GLOBALS['TSFE']->id;
                $action = $this->cObj->typoLink_URL($params);
                $update = ' onchange="document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').action=\'' . $action . '\'; document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').onsubmit=\'\'; submit();"';
            }
        } else {
            $update = '';
        }

        if ($value['form']['additional_attributes'] != '')
            $update .= ' ' . $value['form']['additional_attributes'];

        if ($value['form']['source'] == 'static') {
            for ($i = 0; $i < $value['form']['numValues']; $i++) {
                if ($i == 0) {
                    if ($value['form']['custom_id'] != "")
                        $idi = ' id="' . $value['form']['custom_id'] . '"';
                    else
                        $idi = ' id="' . $id . '"';
                } else
                    $idi = '';
                if ((is_array($wfqbe[$name]) && in_array($value['form'][$i]['value'], $wfqbe[$name])) || $value['form'][$i]['value'] == $this->pibase->piVars[$name])
                    $listCheck .= '<input' . $idi . $update . ' checked="checked" type="checkbox" name="tx_wfqbe_pi1[' . $name . '][]" value="' . $value['form'][$i]['value'] . '" /> ' . $value['form'][$i]['label'];
                else
                    $listCheck .= '<input' . $idi . $update . ' type="checkbox" name="tx_wfqbe_pi1[' . $name . '][]" value="' . $value['form'][$i]['value'] . '" /> ' . $value['form'][$i]['label'];
                if ($i < $value['form']['numValues'] - 1)
                    $listCheck .= '<br />';
            }

        } else {
            // Source == db
            $where = "";
            if ($value['form']['where'] != "") {
                $where = "WHERE " . $this->substituteSearchMarkers($value['form']['where']) . " ";
            }
            $query = 'SELECT DISTINCT ' . $value['form']['field_view'] . ', ' . $value['form']['field_insert'] . ' FROM ' . $value['form']['table'] . ' ' . $where . 'ORDER BY ' . $value['form']['field_view'];
            $ris = $h->Execute($query);

            // emptyOption not needed for checkboxes
            //$emptyOption = false;
            while ($array = $ris->FetchRow()) {
                //if ($array[1]=="")
                //$emptyOption = true;
                if ((is_array($wfqbe[$name]) && in_array($array[1], $wfqbe[$name])) || $array[1] == $this->pibase->piVars[$name])
                    $listCheck .= '<input' . $update . ' checked="checked" type="checkbox" name="tx_wfqbe_pi1[' . $name . '][]" value="' . $array[1] . '" /> ' . $array[0] . '<br />';
                else
                    $listCheck .= '<input' . $update . ' type="checkbox" name="tx_wfqbe_pi1[' . $name . '][]" value="' . $array[1] . '" /> ' . $array[0] . '<br />';
            }
            //if (!$emptyOption)	{
            //if ($wfqbe[$name]=="")
            //$listCheck = '<input id="'.$id.'"'.$update.' checked="checked" type="checkbox" name="tx_wfqbe_pi1['.$name.'][]" value="" /> <br />'.$listCheck;
            //else
            //$listCheck = '<input id="'.$id.'"'.$update.' type="checkbox" name="tx_wfqbe_pi1['.$name.'][]" value="" /> <br />'.$listCheck;
            //}
            if ($listCheck != '')
                $listCheck = substr($listCheck, 0, -6);
        }

        return $listCheck;
    }


    function showSelect($value, $name, $h, $id)
    {
        $wfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_wfqbe_pi1');

        if ($value['form']['onchange'] != "") {
            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1) {
                $update = ' onchange="document.getElementById(\'wfqbe_destination_id\').value=\'' . $value['form']['onchange'] . '\'; ' . $this->prefixId . 'processSearchData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_form\')); return false;"';
            } else {
                $params = array();
                $params['parameter'] = $GLOBALS['TSFE']->id;
                $action = $this->cObj->typoLink_URL($params);
                $update = ' onchange="document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').action=\'' . $action . '\'; document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').onsubmit=\'\'; submit();"';
            }
        } else {
            $update = '';
        }

        if ($value['form']['additional_attributes'] != '')
            $update .= ' ' . $value['form']['additional_attributes'];
        if ($value['form']['custom_id'] != "")
            $id = $value['form']['custom_id'];

        if ($value['form']['multiple'] == 'si') {
            $size = $value['form']['size'] > 0 ? $value['form']['size'] : 5;
            $listaSelect = '<select id="' . $id . '" name="tx_wfqbe_pi1[' . $name . '][]" size="' . $size . '" multiple="multiple"' . $update . '>';
        } else
            $listaSelect = '<select id="' . $id . '" name="tx_wfqbe_pi1[' . $name . '][]"' . $update . '>';

        if ($value['form']['source'] == 'static') {
            for ($i = 0; $i < $value['form']['numValues']; $i++) {
                if ((is_array($wfqbe[$name]) && in_array($value['form'][$i]['value'], $wfqbe[$name])) || $value['form'][$i]['value'] == $this->pibase->piVars[$name])
                    $listaSelect .= '<option selected="selected" value="' . $value['form'][$i]['value'] . '" />' . $value['form'][$i]['label'] . '</option>';
                else
                    $listaSelect .= '<option value="' . $value['form'][$i]['value'] . '" />' . $value['form'][$i]['label'] . '</option>';
            }
        } else {
            // Source == db
            if ($value['form']['customquery'] != "") {
                $query = $this->substituteSearchMarkers($value['form']['customquery']) . " ";

            } else {

                $where = "";
                if ($value['form']['where'] != "") {
                    $where = "WHERE " . $this->substituteSearchMarkers($value['form']['where']) . " ";
                }

                foreach ($value['form'] as $k => $v)
                    $wfqbeArray['###WFQBE_FIELD_' . $k . '###'] = $v;
                foreach ($value['form'] as $f => $v) {
                    if ($this->conf["customQuery."][$this->row['uid'] . "."][$f] != "") {
                        $confArray = $this->conf["customQuery."][$this->row['uid'] . "."][$f . "."];
                        $confArray = $this->parseTypoScriptConfiguration($confArray, $wfqbeArray);
                        $func = $this->conf["customQuery."][$this->row["uid"]."."][$f];
                        $value['form'][$f] = "{$this->cObj->$func($confArray)}";
                    }
                }
                if ($value['form']['field_orderby'] != '' && $value['form']['field_orderby_mode'] != '')
                    $orderby = ' ORDER BY ' . $value['form']['field_orderby'] . ' ' . $value['form']['field_orderby_mode'];
                $query = 'SELECT DISTINCT ' . $value['form']['field_view'] . ', ' . $value['form']['field_insert'] . ' FROM ' . $value['form']['table'] . ' ' . $where . $orderby;
            }

            $ris = $h->Execute($query);

            if ($value['form']['required'] != 'si')
                $listaSelect .= '<option value="">' . $value['form']['labelEmptyValue'] . '</option>';

            if ($ris !== false) {
                while ($array = $ris->FetchRow()) {
                    foreach ($array as $k => $v)
                        $wfqbeArray['###WFQBE_FIELD_' . $k . '###'] = $v;
                    if ($this->conf["customProcess."][$this->row['uid'] . "."][$value['form']['field_view']] != "") {
                        $confArray = $this->conf["customProcess."][$this->row['uid'] . "."][$value['form']['field_view'] . "."];
                        $confArray = $this->parseTypoScriptConfiguration($confArray, $wfqbeArray);
                        $func = $this->conf["customProcess."][$this->row["uid"]."."][$value['form']['field_view']];
                        $array[0] = "{$this->cObj->$func($confArray)}";
                    }

                    if ((is_array($wfqbe[$name]) && in_array($array[1], $wfqbe[$name])) || $array[1] == $wfqbe[$name] || $array[1] == $this->pibase->piVars[$name])
                        $listaSelect .= '<option selected="selected" value="' . $array[1] . '">' . $array[0] . '</option>';
                    else
                        $listaSelect .= '<option value="' . $array[1] . '">' . $array[0] . '</option>';
                }
            }
        }

        return $listaSelect . '</select>';
    }


    function showCalendar($value, $name, $id, &$blockTemplate)
    {
        $wfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_wfqbe_pi1');
        if ($value['form']['custom_id'] != "")
            $id = $value['form']['custom_id'];

        if ($value['form']['date2cal'] == 'si' && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('date2cal')) {
            include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('date2cal') . '/src/class.jscalendar.php');
            // init jscalendar class
            $JSCalendar = JSCalendar::getInstance();
            $JSCalendar->setInputField($id);

            if ($value['form']['time'] == 'si') {
                $JSCalendar->setDateFormat(true);
                $format = ($value['form']['format'] != '' ? $value['form']['format'] : '%H:%M %d-%m-%Y');
                $JSCalendar->setConfigOption('ifFormat', $format);
                $JSCalendar->setConfigOption('daFormat', $format);
            } else {
                $JSCalendar->setDateFormat(false);
                $format = ($value['form']['format'] != '' ? $value['form']['format'] : '%d-%m-%Y');
                $JSCalendar->setConfigOption('ifFormat', $format);
                $JSCalendar->setConfigOption('daFormat', $format);
            }
            if ($value['form']['nlp'] == 'si')
                $JSCalendar->setNLP(true);
            else
                $JSCalendar->setNLP(false);

            $field = $JSCalendar->render($wfqbe[$name], 'tx_wfqbe_pi1[' . $name . ']');
            //$field = str_replace('name="tx_wfqbe_pi1['.$name.']_hr"', 'name="tx_wfqbe_pi1['.$name.']"', $field);

            // get initialisation code of the calendar
            if (($jsCode = $JSCalendar->getMainJS()) != '') {
                $GLOBALS['TSFE']->additionalHeaderData['wfqbe_date2cal'] = $jsCode;
            }

            return $field;

        } elseif ($value['form']['date2cal'] == 'si' && !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('date2cal')) {
            return '<br />ERROR: date2cal extension is not loaded!<br />';

        } elseif ($value['form']['date2cal'] != 'si') {
            if (!$this->pibase->beMode) {
                // Uses jQuery datepicker
                $format = ($value['form']['format'] != '' ? $value['form']['format'] : 'dd-mm-yy');
                $jsCode = '<script>
                            jQuery(function() {
                                jQuery( "#' . $id . '" ).datepicker({"dateFormat": "' . $format . '", "defaultDate": jQuery.datepicker.parseDate("' . $format . '",jQuery("#' . $id . '").attr(\'value\'))});
                                //alert ("' . $id . ': "+jQuery("#' . $id . '").attr(\'value\'));
                                //jQuery( "#' . $id . '" ).datepicker( "option", "dateFormat", "' . $format . '" );
                                
                                jQuery( "#' . $id . '" ).datepicker( "option", "changeYear", true );
                                jQuery( "#' . $id . '" ).datepicker( "option", "constrainInput", false );
                            ';
                if ($value['form']['min_date'] != '') {
                    $jsCode .= 'jQuery( "#' . $id . '" ).datepicker( "option", "minDate", ' . $value['form']['min_date'] . ' );';
                }
                if ($value['form']['max_date'] != '') {
                    $jsCode .= 'jQuery( "#' . $id . '" ).datepicker( "option", "maxDate", ' . $value['form']['max_date'] . ' );';
                }

                $jsCode .= '});
                            </script>';
                $GLOBALS['TSFE']->additionalHeaderData['wfqbe_datepicker'] = $jsCode;

                return '<input id="' . $id . '" type="text" name="tx_wfqbe_pi1[' . $name . ']" value="' . $this->pibase->piVars[$name] . '" />';

            } elseif ($this->pibase->beMode) {
                // Uses extbase calendar

                $format = str_replace('dd', 'DD', $value['form']['format']);
                $format = str_replace('mm', 'MM', $format);
                $format = str_replace('yy', 'YYYY', $format);

                $fieldId = "tceforms-datetimefield-$id";

                $JScode = '<script type="text/javascript">
                    TYPO3.jQuery( window ).load(function() {
                         TYPO3.jQuery("#'.$fieldId.'").val("'.$this->pibase->piVars[$name].'");
                         if(TYPO3.jQuery("#'.$fieldId.'").data(\'DateTimePicker\') !== undefined){
                            TYPO3.jQuery("#'.$fieldId.'").data(\'DateTimePicker\').format("'.$format.'");
                         }
                    }); 
                </script>';

                $iconFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
                /** @var  TYPO3\CMS\Core\Imaging\Icon $icon */
                $icon = $iconFactory->getIcon(
                    'actions-edit-pick-date',
                    \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL);

                return $JScode . '<div class="form-control-clearable"><input name="tx_wfqbe_pi1[' . $name . ']" 
                                         data-date-type="datetime" 
                                         data-date-time-picker="[format: '.$format.']"
                                         class="t3js-datetimepicker form-control t3js-clearable hasDefaultValue" 
                                         type="text" 
                                         id="'.$fieldId . '" 
                                         value="' . $this->pibase->piVars[$name] . '" />' .
                    '<button type="button" class="close" tabindex="-1" aria-hidden="true" style="display: none;"><span class="fa fa-times"></span></button></div>'.
                    '<span class="input-group-btn">
						<label class="btn btn-default" for="'.$fieldId.'">
							'.$icon->__toString().'
						</label>
					</span>';
            }
        }
    }


    /**
     * This function is used for parsing the TS fields configuration and to substitute the markers with the field value
     */
    function parseTypoScriptConfiguration($confArray, $wfqbeArray)
    {
        if (is_array($confArray) && is_array($wfqbeArray)) {
            foreach ($confArray as $k => $value) {
                if (is_array($value))
                    $confArray[$k] = $this->parseTypoScriptConfiguration($value, $wfqbeArray);

                if (strpos($value, "###WFQBE_FIELD_") !== false) {
                    $confArray[$k] = $this->cObj->substituteMarkerArray($value, $wfqbeArray);
                }
            }
        }
        return $confArray;
    }


    /**
     * Query parameters management
     * This function substitutes the ###WFQBE_VARIABLE_NAME### markers with \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wfqbe[variable_name]')
     */
    function substituteSearchMarkers($where)
    {
        $markerParametri = array();

        // Default values from TS
        $tsMarkers = $this->getTSMarkers($where);
        if (is_array($tsMarkers)) {
            foreach ($tsMarkers as $marker) {
                if ($this->conf['customQuery.'][$this->conf['ff_data']['queryObject'] . '.'][$marker] != "") {
                    $confArray = $this->conf["customQuery."][$this->conf['ff_data']['queryObject'] . "."][$marker . "."];
                    //$confArray = $this->parseTypoScriptConfiguration($confArray, $wfqbeArray);
                    $func = $this->conf["customQuery."][$this->conf['ff_data']['queryObject']."."][$marker];
                    $markerParametri["###" . $marker . "###"] = "{$this->cObj->$func($confArray)}";
                }
            }
        }

        // Values from a previous selection (passed via GET or POST)
        $parametri = $this->pibase->piVars;
        if (is_array($parametri)) {
            foreach ($parametri as $key => $value) {
                if (!is_array($value)) {
                    $markerParametri["###WFQBE_" . strtoupper($key) . "###"] = strip_tags($value);
                } elseif (sizeof($value) == 1) {
                    $markerParametri["###WFQBE_" . strtoupper($key) . "###"] = strip_tags($value[0]);
                } else {
                    $i = 0;
                    foreach ($value as $sel) {
                        if ($i > 0)
                            $markerParametri["###WFQBE_" . strtoupper($key) . "###"] .= "'";
                        $markerParametri["###WFQBE_" . strtoupper($key) . "###"] .= strip_tags($sel);
                        if ($i < sizeof($value) - 1)
                            $markerParametri["###WFQBE_" . strtoupper($key) . "###"] .= "',";
                        $i++;
                    }
                }
            }
            $where = $this->cObj->substituteMarkerArray($where, $markerParametri);
        }
        //return ereg_replace("(###)+[a-z,A-Z,0-9,@,!,_]+(###)","",$where);
        return preg_replace("/(###)+[a-z,A-Z,0-9,@,!,_]+(###)/", "", $where);
    }

    /**
     * This function is used to retrieve the markers from a string
     */
    function getTSMarkers($query)
    {
        if (preg_match_all("/([#]{3})([a-z,A-Z,0-9,@,!,_]*)([#]{3})/", $query, $markers))
            return $markers[2];
        else
            return null;
    }


    /**
     * AJAX INTEGRATIONS
     */
    function sGetSearchForm_Ajax()
    {
        $content = "";

        if ($this->pibase->piVars['wfqbe_this_query'] != "") {
            $where = 'tx_wfqbe_query.uid=' . intval($this->pibase->piVars['wfqbe_this_query']) . ' AND ';

            // Creates the connection to the remote DB
            $CONN = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_connect");
            $connection_obj = $CONN->connect($where);


            $blocks = $this->getBlocks($connection_obj['row'], $connection_obj['conn']);
            if (is_array($blocks)) {
                $file = $this->cObj->fileResource($this->conf['template']);
                $file = $this->cObj->getSubpart($file, '###SEARCH_TEMPLATE###');
                $blockTemplate = $this->cObj->getSubpart($file, '###FIELD_BLOCK###');

                // Now I get the block requested via XAJAX
                $i = 0;
                foreach ($blocks as $key => $value) {
                    if ($this->pibase->piVars['wfqbe_destination_id'] == strtoupper($value['marker']) . '_' . $key) {
                        $content .= $this->getSearchField($key, $value, $connection_obj['conn'], $blockTemplate, ($i % 2));
                        break;
                    }
                    $i++;
                }
            }
        }

        return $content;
    }


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/pi1/class.tx_wfqbe_search.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/pi1/class.tx_wfqbe_search.php']);
}

