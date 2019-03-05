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
 * Insert class for the 'wfqbe' extension.
 */
class tx_wfqbe_insert
{

    var $conf;
    var $cObj;
    var $pibase;
    var $prefixId = 'tx_wfqbe_pi1';
    var $blocks;
    var $mode = 'insert';    // insert or edit
    var $row = array();        // the query row
    var $markerArray = array();
    var $old_piVars = array();    // this array will contain old piVars in insert wizard mode


    function main($conf, $cObj, $pibase)
    {
        $this->conf = $conf;
        $this->cObj = $cObj;
        $this->pibase = $pibase;

        if (($this->pibase->piVars['wfqbe_editing_mode'] == 1 && $this->pibase->piVars['wfqbe_add_new'] == "") || ($this->pibase->piVars['wfqbe_edit_subrecord'] > 0 && $this->pibase->piVars['wfqbe_add_new'] != ""))
            $this->mode = 'edit';
        elseif (($this->pibase->piVars['wfqbe_deleting_mode'] == 1 && $this->pibase->piVars['wfqbe_add_new'] == "") || ($this->pibase->piVars['wfqbe_delete_subrecord'] > 0 && $this->pibase->piVars['wfqbe_add_new'] != ""))
            $this->mode = 'delete';
        else
            $this->mode = 'insert';
    }


    function getBlocks($row)
    {
        $API = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_api_xml2array");

        $blocks = "";
        if ($row["insertq"] != "") {
            $blocks = $API->xml2array($row["insertq"]);
        }
        return $blocks;
    }


    function do_sGetForm($row, $h)
    {
        return $this->do_sGetFormResult($row, $h);
    }


    /**
     * Function used to show the insert form
     *
     * @param array    row    the query table record
     * @param resource    h        the connection to the db
     * @param array    new_id    in position 0 there's the field key, in position 1 there's the new inserted field value (used for the add_new option)
     * @return string the form html
     */
    function do_sGetFormResult($row, $h, $new_id = '')
    {
        if ($this->pibase->beMode != '')
            $file = @file_get_contents(PATH_site . $GLOBALS['TSFE']->tmpl->getFileName($this->conf['template']));
        else
            $file = $this->cObj->fileResource($this->conf['template']);
        $this->row = $row;
        $this->blocks = $this->getBlocks($row);
        $this->blocks['query_row'] = $row;

        if (is_array($new_id)) {
            if ($new_id[2] == 'unset') {
                if ($this->blocks['fields'][$new_id[0]]['form']['multiple'] == 'si' || $this->blocks['fields'][$new_id[0]]['form']['multiple'] == '1' || $this->blocks['fields'][$new_id[0]]['type'] == 'select') {
                    if (is_array($this->pibase->piVars[$new_id[0]]))
                        $this->removeItem($new_id);
                    else
                        $this->pibase->piVars[$new_id[0]] = '';
                } else
                    $this->pibase->piVars[$new_id[0]] = '';
            } else {
                // I set the new value for the correct field
                if ($this->blocks['fields'][$new_id[0]]['form']['multiple'] == 'si' || $this->blocks['fields'][$new_id[0]]['form']['multiple'] == '1' || $this->blocks['fields'][$new_id[0]]['type'] == 'select') {
                    if (is_array($this->pibase->piVars[$new_id[0]]))
                        $this->pibase->piVars[$new_id[0]][] = $new_id[1];
                    else
                        $this->pibase->piVars[$new_id[0]] = array($new_id[1]);
                } else
                    $this->pibase->piVars[$new_id[0]] = $new_id[1];
            }
        }
        $content = '';

        if (is_array($_FILES['tx_wfqbe_pi1']['error'])) {
            $content .= $this->uploadFiles($this->blocks);
        }

        if ($this->pibase->piVars['file_delete'] != '') {

            $fileDelete = $this->pibase->piVars['file_delete'];
            $actionRequired = intval($this->pibase->piVars['action_required']);
            //do checks
            if (is_array($this->blocks['fields'])) {
                $fields = $this->blocks['fields'];
                foreach ($fields as $key => $field) {
                    // Hook that can be used to do custom delete of file
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['customFileDelete'])) {
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['customFileDelete'] as $_classRef) {
                            $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                            $fileDelete = $_procObj->deleteFile($results, $fileDelete, $this->blocks, $this->mode, $h, $this);
                        }
                    } else {
                        if (intval($key) === $actionRequired) {
                            $baseDir = dirname($fileDelete) . DIRECTORY_SEPARATOR;
                            if ($baseDir == $field['form']['basedir']) {
                                $this->deleteFiles($fileDelete);
                            }
                            break;
                        }
                    }
                }
            }
        }

        if (isset($this->pibase->piVars['submit_confirm']) && !isset($this->pibase->piVars['submit_insert']) && !isset($this->pibase->piVars['submit_modify'])) {
            // Show the confirmation page
            $content .= $this->showPageConfirmation($this->pibase->piVars, $this->blocks, $h, $file, $row);
        } elseif ($this->pibase->piVars['wfqbe_deleting_mode'] == 1 && !isset($this->pibase->piVars['submit_insert']) && !isset($this->pibase->piVars['submit_modify'])) {
            $editing_record = $this->initValues($h);  // Now I retrieve the values that I want to edit from the table

            // Hook that can be used to decide if the form can be shown or not
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['checkPermissions'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['checkPermissions'] as $_classRef) {
                    $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                    $res = $_procObj->checkPermissions($row, $this->pibase->piVars, $editing_record, $this);
                    if ($res['notAllowed'] == 1) {
                        return $res['content'];
                    }
                }
            }


            $content .= $this->showPageConfirmation($this->pibase->piVars, $this->blocks, $h, $file, $row);
            $content = $this->cObj->substituteSubpart($content, '###MODIFY_BUTTON###', '', 0, 0);
        } elseif (isset($this->pibase->piVars['submit_insert']) && $this->pibase->piVars['submit_insert'] != 'wfqbe_no') {
            $editing_record = $this->getEditingRecord($h, $this->pibase->piVars['wfqbe_id_field']);

            if ($this->mode == 'edit' && $this->blocks['ID_restricting'] != '') {
                $res = $this->checkIDRestricting($h, $this->blocks['ID_restricting'], $editing_record);

                if ($res['notAllowed'] == 1) {
                    return $res['content'];
                }
            }

            // Hook that can be used to decide if the form can be shown or not
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['checkPermissions'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['checkPermissions'] as $_classRef) {
                    $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                    $res = $_procObj->checkPermissions($row, $this->pibase->piVars, $editing_record, $this);
                    if ($res['notAllowed'] == 1) {
                        return $res['content'];
                    }
                }
            }


            // Show the final page (and make the insert)
            $results = $this->executeQuery($this->pibase->piVars, $this->blocks, $h);

            // Sends an email to site administrator
            if ($results['inserted'] == 1 && $this->conf['email.']['send_email'] == 1 && $this->conf['email.']['notify_email'] != '') {
                require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . 'lib/class.tx_wfqbe_mail.php');
                $mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_wfqbe_mail');
                $mail->init($this->cObj, $this->conf, $this->pibase->piVars, $this);
                $sent = $mail->sendEmail($this->conf['email.']['notify_email'], $this->conf['email.']['notify_subject'], $results, 'ADMIN');
            }

            // Sends a confirmation email to user
            if ($results['inserted'] == 1 && $this->conf['email.']['send_email_user'] == 1 && $this->conf['email.']['field_email_user'] != '' && \TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($this->pibase->piVars[$this->conf['email.']['field_email_user']])) {
                require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . 'lib/class.tx_wfqbe_mail.php');
                $mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_wfqbe_mail');
                $mail->init($this->cObj, $this->conf, $this->pibase->piVars, $this);
                $sent = $mail->sendEmail($this->pibase->piVars[$this->conf['email.']['field_email_user']], $this->conf['email.']['notify_subject_user'], $results, 'USER');
            }

            if ($this->conf['email.']['debug'] == 1) {
                \TYPO3\CMS\Core\Utility\DebugUtility::debug($results);
                \TYPO3\CMS\Core\Utility\DebugUtility::debug($this->pibase->piVars);
                die('DIE IN CLASS.TX_WFQBE_INSERT.PHP DUE TO EMAIL DEBUG ACTIVE');
            }

            if ($this->conf['ff_data']['clear_cache'] != '') {
                $clear_cache = explode(',', $this->conf['ff_data']['clear_cache']);
                if (is_array($clear_cache) && count($clear_cache) > 0) {
                    $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler');
                    foreach ($clear_cache as $pUid) {
                        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($pUid))
                            $tce->clear_cacheCmd($pUid);
                    }
                }
            }

            if ($results['inserted'] == true) {
                // Hook that can be used to do something after a delete/insert/update operation
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['afterExecuteQuery'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['afterExecuteQuery'] as $_classRef) {
                        $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                        $results = $_procObj->postQueryProcess($results, $this->blocks, $this->mode, $h, $this);
                    }
                }
            }

            $content .= $results['content'];
            if (!$results['inserted']) {
                $content .= $this->showPageConfirmation($this->pibase->piVars, $this->blocks, $h, $file, $row, false);
            } else {
                if ($this->pibase->piVars['wfqbe_add_new'] != '')
                    $content .= $this->showOriginalForm($h, $results);
                elseif ($results['id'] == '-2' && $this->conf['ff_data']['deletedPage'] != '') {
                    $params = array();
                    $params['parameter'] = $this->conf['ff_data']['deletedPage'];
                    $action = $this->cObj->typoLink_URL($params);
                    header('Location: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $action);
                } else {
                    if ($this->conf['ff_data']['detailedPage'] != '') {
                        // This redirects the user to the detail page
                        $query = '';
                        if ($this->conf['ff_data']['redirectQuery'] != '' && is_array($results['insert_data'])) {
                            foreach ($results['insert_data'] as $key => $value) {
                                $mA['###FIELD_' . $key . '###'] = $value;
                                $mA['###WFQBE_FIELD_' . $key . '###'] = $value;
                            }
                            $mA['###ID###'] = $results['id'];
                            $query = $this->cObj->substituteMarkerArray($this->conf['ff_data']['redirectQuery'], $mA);
                        }
                        $params = array();
                        $params['parameter'] = $this->conf['ff_data']['detailedPage'];
                        $params['additionalParams'] = '&' . $query;
                        $action = $this->cObj->typoLink_URL($params);
                        header('Location: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $action);
                    } else {
                        $content .= $this->showPageConfirmation($this->pibase->piVars, $this->blocks, $h, $file, $row, true);
                    }
                }
            }

        } elseif ($this->mode == 'delete' && $this->pibase->piVars['wfqbe_add_new'] != '' && $this->pibase->piVars['wfqbe_delete_subrecord'] != '' && !isset($this->pibase->piVars['submit_insert']) && !isset($this->pibase->piVars['submit_modify'])) {
            // Show the confirmation page
            $orig = $this->pibase->piVars;
            $wfqbe_delete_subrecord = $this->pibase->piVars['wfqbe_delete_subrecord'];
            //unset($this->pibase->piVars);
            $this->initValues($h, $wfqbe_delete_subrecord);
            $this->pibase->piVars['orig'] = $orig;

            $content .= $this->showPageConfirmation($this->pibase->piVars, $this->blocks, $h, $file, $row);
            $content = $this->cObj->substituteSubpart($content, '###MODIFY_BUTTON###', '', 0, 0);
        } else {
            // Show the insert form
            if ($this->pibase->piVars['wfqbe_add_new'] == '' && $this->pibase->piVars['orig']['wfqbe_add_new'] != '') {
                $this->pibase->piVars = $this->pibase->piVars['orig'];
                unset($this->pibase->piVars['wfqbe_add_new']);
                $this->main($this->conf, $this->cObj, $this->pibase);
            }
            $file = $this->cObj->getSubpart($file, '###INSERT_TEMPLATE###');
            if (is_array($this->blocks)) {
                $content = $this->showInsertModule($file, $this->blocks['fields'], $h, $row);
            }
        }

        return $content;
    }


    /**
     * This function is used to remove an item from an array when you now the value
     */
    function removeItem($new_id)
    {
        if (is_array($this->pibase->piVars[$new_id[0]])) {
            foreach ($this->pibase->piVars[$new_id[0]] as $key => $value) {
                if ($value == $new_id[1])
                    unset($this->pibase->piVars[$new_id[0]][$key]);
            }
        }
    }


    function sGetInsertForm_Ajax()
    {
        $content = "";
        if ($this->pibase->piVars['wfqbe_this_query'] != "") {
            $where = 'tx_wfqbe_query.uid=' . intval($this->pibase->piVars['wfqbe_this_query']) . ' AND ';

            // Creates the connection to the remote DB
            $CONN = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_connect");
            $connection_obj = $CONN->connect($where);

            if ($this->pibase->piVars['wfqbe_destination_id'] != "") {
                $this->blocks = $this->getBlocks($connection_obj['row']);
                if (is_array($this->blocks['fields'])) {
                    $file = $this->cObj->fileResource($this->conf['template']);
                    $file = $this->cObj->getSubpart($file, '###INSERT_TEMPLATE###');
                    $blockTemplate = $this->cObj->getSubpart($file, '###FIELD_BLOCK###');
                    $hiddenTemplate = $this->cObj->getSubpart($file, '###HIDDEN_BLOCK###');

                    // Now I get the block requested via XAJAX
                    foreach ($this->blocks['fields'] as $key => $value) {
                        if ($this->pibase->piVars['wfqbe_destination_id'] == $value['field'] . "_" . $key) {
                            $content .= $this->showInsertField($key, $value, $connection_obj['conn'], $blockTemplate, $hiddenTemplate);
                            break;
                        }
                    }
                }
            } else {
                $content = $this->do_sGetFormResult($connection_obj['row'], $connection_obj['conn']);
            }
        }

        return $content;
    }


    function showInsertModule($content, $blocks, $h, $row, $required = array())
    {
        $blockTemplate = $this->cObj->getSubpart($content, '###INSERT_BLOCK###');
        $hiddenTemplate = $this->cObj->getSubpart($content, '###HIDDEN_BLOCK###');
        $blockList = '';
        $blockHiddenList = '';

        $this->row = $row;

        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1) {
            $blockList .= '<input type="hidden" value="' . $row['uid'] . '" name="tx_wfqbe_pi1[wfqbe_this_query]" />';
            $blockList .= '<input type="hidden" value="" name="tx_wfqbe_pi1[wfqbe_destination_id]" id="wfqbe_destination_id" />';
        }

        $mA['###INSERT_HIDDEN_FIELDS###'] = '';
        if ($this->pibase->piVars['wfqbe_add_new'] != '') {
            $API = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_utils");
            if (is_array($this->pibase->piVars['orig']))
                $mA['###INSERT_HIDDEN_FIELDS###'] = $API->getHiddenFields($this->pibase->piVars, '', 'submit_modify');
            else
                $mA['###INSERT_HIDDEN_FIELDS###'] = $API->getHiddenFields($this->pibase->piVars, 'orig', 'submit_modify');
            $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_add_new]" value="' . $this->pibase->piVars['wfqbe_add_new'] . '" />';
            $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_edit_subrecord]" value="' . $this->pibase->piVars['wfqbe_edit_subrecord'] . '" />';
            $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_delete_subrecord]" value="' . $this->pibase->piVars['wfqbe_delete_subrecord'] . '" />';
            $wfqbe_add_new = $this->pibase->piVars['wfqbe_add_new'];
            $this->old_piVars = $this->pibase->piVars;
            if ($this->pibase->piVars['submit_modify'] == '') {
                if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->pibase->piVars['wfqbe_edit_subrecord']) && $this->pibase->piVars['wfqbe_edit_subrecord'] != '') {
                    $wfqbe_edit_subrecord = $this->pibase->piVars['wfqbe_edit_subrecord'];
                    unset($this->pibase->piVars);
                    $this->initValues($h, $wfqbe_edit_subrecord);
                } else {
                    unset($this->pibase->piVars);
                }
            } else {
                unset($this->pibase->piVars['submit_modify'], $this->pibase->piVars['wfqbe_add_new']);
            }
        } else {
            $content = $this->cObj->substituteSubpart($content, '###ADD_NEW_CANCEL###', '', 0, 0);
        }

        if ($wfqbe_add_new == "") {
            $editing_record = '';
            if ($this->mode == 'edit') {
                if ($this->blocks['ID_field'] == '')
                    return "";
                else {
                    if ($this->pibase->piVars['wfqbe_id_field'] != "" && is_int(intval($this->pibase->piVars['wfqbe_id_field']))) {
                        $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_id_field]" value="' . $this->pibase->piVars['wfqbe_id_field'] . '" />';
                        $editing_record = false;
                    } else {
                        $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_id_field]" value="' . $this->pibase->piVars[$this->blocks['ID_field']] . '" />';
                        $editing_record = $this->initValues($h);  // Now I retrieve the values that I want to edit from the table
                        unset($this->pibase->piVars[$this->blocks['ID_field']]);
                    }
                    $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_editing_mode]" value="1" />';
                }
            } elseif ($this->mode == 'delete') {
                if ($this->blocks['ID_field'] == '')
                    return "";
                else {
                    if ($this->pibase->piVars['wfqbe_id_field'] != "" && is_int(intval($this->pibase->piVars['wfqbe_id_field']))) {
                        $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_id_field]" value="' . $this->pibase->piVars['wfqbe_id_field'] . '" />';
                        $editing_record = false;
                    } else {
                        $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_id_field]" value="' . $this->pibase->piVars[$this->blocks['ID_field']] . '" />';
                        $editing_record = $this->initValues($h);  // Now I retrieve the values that I want to edit from the table
                        unset($this->pibase->piVars[$this->blocks['ID_field']]);
                    }
                    $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_deleting_mode]" value="1" />';
                }
            }
        } else {
            if ($this->mode == 'edit') {
                $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_id_field]" value="' . $this->pibase->piVars['wfqbe_id_field'] . '" />';
                $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_editing_mode]" value="1" />';
            } elseif ($this->mode == 'delete') {
                $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_id_field]" value="' . $this->pibase->piVars['wfqbe_id_field'] . '" />';
                $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_deleting_mode]" value="1" />';
            }
        }

        if ($editing_record === false)
            $editing_record = $this->getEditingRecord($h, $this->pibase->piVars['wfqbe_id_field']);

        if ($this->mode == 'edit' && $this->blocks['ID_restricting'] != '') {
            $res = $this->checkIDRestricting($h, $this->blocks['ID_restricting'], $editing_record);
            if ($res['notAllowed'] == 1) {
                \TYPO3\CMS\Core\Utility\DebugUtility::debug('not allowed');
                return $res['content'];
            }
        }

        // Hook that can be used to decide if the form can be shown or not
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['checkPermissions'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['checkPermissions'] as $_classRef) {
                $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                $res = $_procObj->checkPermissions($row, $this->pibase->piVars, $editing_record, $this);
                if ($res['notAllowed'] == 1) {
                    return $res['content'];
                }
            }
        }

        $i = 0;
        foreach ($blocks as $key => $value) {
            if ($value['type'] == '')
                continue;
            if ($this->mode != "delete" && $value['form']['when'][$this->mode] != 1)
                continue;

            if (is_array($required['required']) && in_array($key, $required['required']))
                $show_required = true;
            else
                $show_required = false;

            switch ($value['type']) {
                case 'hidden':
                case 'PHP function':
                    $blockHiddenList .= $this->showInsertField($key, $value, $h, $blockTemplate, $hiddenTemplate, $show_required, ($i % 2), $required['custom_validation'][$key]);
                    break;
                default:
                    $blockList .= $this->showInsertField($key, $value, $h, $blockTemplate, $hiddenTemplate, $show_required, ($i % 2), $required['custom_validation'][$key]);
                    break;
            }
            $i++;
        }

        $content = $this->cObj->substituteSubpart($content, '###INSERT_BLOCK###', $blockList, 0, 0);
        $content = $this->cObj->substituteSubpart($content, '###HIDDEN_BLOCK###', $blockHiddenList, 0, 0);

        $mA = array_merge($mA, $this->markerArray);

        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1) {
            $mA['###XAJAX_SUBMIT###'] = ' onsubmit="' . $this->prefixId . 'processInsertData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_form\')); return false;"';
            $mA['###XAJAX_CLEAR###'] = 'onclick="document.getElementById(\'wfqbe_destination_id\').value=\'\'; ' . $this->prefixId . 'processInsertData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_form\')); return false;"';
        } else {
            $mA['###XAJAX_SUBMIT###'] = '';
            $mA['###XAJAX_CLEAR###'] = '';
        }

        //$params = array();
        //$params['parameter'] = $GLOBALS['TSFE']->id;
        //$mA['###CONF_INSERT###'] = $this->cObj->typoLink_URL($params);

        $mA['###CONF_INSERT###'] = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->pibase->pi_getPageLink($GLOBALS['TSFE']->id));

        $mA['###CONF_DIVID###'] = $this->conf['ff_data']['div_id'];
        $mA['###INSERT_DESCRIPTION###'] = $row['description'];

        if ($this->mode == 'edit') {
            $mA['###CONF_SUBMIT_LABEL###'] = $this->pibase->pi_getLL('update_submit', 'Update');
        } elseif ($this->mode == 'delete') {
            $mA['###CONF_SUBMIT_LABEL###'] = $this->pibase->pi_getLL('delete_submit', 'Delete');
        } else {
            $mA['###CONF_SUBMIT_LABEL###'] = $this->pibase->pi_getLL('insert_submit', 'Insert');
        }

        if ($this->conf['insert.']['pageConfirmation'] == 1) {
            if (($this->old_piVars['wfqbe_add_new'] != '' || $this->old_piVars['wfqbe_editing_mode'] == 1) && $this->conf['subinsert.']['pageConfirmation'] != 1)
                $mA['###CONF_SUBMIT###'] = 'submit_insert';
            else
                $mA['###CONF_SUBMIT###'] = 'submit_confirm';
        } else {
            $mA['###CONF_SUBMIT###'] = 'submit_insert';
        }

        $mA['###LABEL_REQUIRED###'] = $this->pibase->pi_getLL('required', 'Please, complete this field');
        $mA['###LABEL_CANCEL###'] = $this->pibase->pi_getLL('cancel', 'Cancel');

        $content = $this->cObj->substituteMarkerArray($content, $mA);

        return $content;
    }


    function showInsertField($key, $value, $h, $blockTemplate, $hiddenTemplate, $show_required = false, $odd = 0, $custom_validation = "")
    {

        $blockList = "";


        $name = strtolower($value['field']) . "_" . $key;
        $rA['###INSERT_ID###'] = $name;

        if (!is_array($value['form']['label']))
            $rA['###INSERT_LABEL###'] = $value['form']['label'];
        else {
            // This will show the label in the correct language
            if ($GLOBALS['TSFE']->sys_language_uid == 0 || $value['form']['label'][$GLOBALS['TSFE']->sys_language_uid] == '')
                $rA['###INSERT_LABEL###'] = $value['form']['label']['def'];
            else
                $rA['###INSERT_LABEL###'] = $value['form']['label'][$GLOBALS['TSFE']->sys_language_uid];
        }

        if ($value['required'] == 1)
            $rA['###INSERT_LABEL###'] .= $this->conf['insert.']['requiredSymbol'];

        $rA['###INSERT_HELP_LINK###'] = $this->cObj->stdWrap('<a href="#" onclick="javascript:wfqbe_manage_help(\'help_' . $name . '\'); return false;">' . $this->conf['insert.']['help_link'] . '</a>', $this->conf['insert.']['help_link.']);
        if (!is_array($value['help']) && $value['help'] != '') {
            $rA['###INSERT_HELP###'] = preg_replace("/\r/", "<br>", $value['help']);
        } elseif (is_array($value['help'])) {
            // This will show the help text in the correct language
            if ($GLOBALS['TSFE']->sys_language_uid == 0 || $value['help'][$GLOBALS['TSFE']->sys_language_uid] == '') {
                if ($value['help']['def'] != '') {
                    $rA['###INSERT_HELP###'] = preg_replace("/\r/", "<br>", $value['help']['def']);
                } else {
                    $rA['###INSERT_HELP###'] = '';
                    $rA['###INSERT_HELP_LINK###'] = '';
                }
            } else {
                $rA['###INSERT_HELP###'] = preg_replace("/\r/", "<br>", $value['help'][$GLOBALS['TSFE']->sys_language_uid]);
            }
        } else {
            $rA['###INSERT_HELP###'] = '';
            $rA['###INSERT_HELP_LINK###'] = '';
        }

        if ($odd)
            $rA['###WFQBE_CLASS###'] = $this->conf['classes.']['odd'];
        else
            $rA['###WFQBE_CLASS###'] = $this->conf['classes.']['even'];

        $name = "field_" . $name;

        switch ($value['type']) {
            case 'display only':
                $rA['###INSERT_FIELD###'] = $this->showDisplay($value, $key, $name);
                break;
            case 'input':
                $rA['###INSERT_FIELD###'] = $this->showInput($value, $key, $name);
                break;
            case 'datetype':
                $rA['###INSERT_FIELD###'] = $this->showDatetype($value, $key, $name);
                break;
            case 'calendar':
                $rA['###INSERT_FIELD###'] = $this->showCalendar($value, $key, $name, $blockTemplate);
                break;
            case 'password':
                $rA['###INSERT_FIELD###'] = $this->showPassword($value, $key, $name);
                break;
            case 'hidden':
                $rA['###INSERT_FIELD###'] = $this->showHidden($value, $key, $name);
                break;
            case 'relation':
                $rA['###INSERT_FIELD###'] = $this->showRelation($value, $key, $h, $name);
                break;
            case 'textarea':
                $rA['###INSERT_FIELD###'] = $this->showTextarea($value, $key, $name);
                break;
            case 'radio':
                $rA['###INSERT_FIELD###'] = $this->showRadio($value, $key, $h, $name);
                break;
            case 'checkbox':
                $rA['###INSERT_FIELD###'] = $this->showCheckbox($value, $key, $h, $name);
                break;
            case 'select':
                $rA['###INSERT_FIELD###'] = $this->showSelect($value, $key, $h, $name);
                break;
            case 'Raw HTML':
                $rA['###INSERT_FIELD###'] = $this->showRawHTML($value, $key, $name);
                break;
            case 'upload':
                $rA['###INSERT_FIELD###'] = $this->showUpload($value, $key, $name);
                break;
            default:
                $rA['###INSERT_FIELD###'] = '';
                // Hook that can be used to add custom field types
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['customFieldTypesWizard'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['customFieldTypesWizard'] as $_classRef) {
                        $_params = array();
                        $_params['blocks'] = $this->blocks;
                        $_params['connection'] = $h;
                        $_params['value'] = $value;
                        $_params['key'] = $key;
                        $_params['name'] = $name;
                        $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                        $rA['###INSERT_FIELD###'] = $_procObj->showCustomFieldType($_params, $this);
                    }
                }
                break;
        }

        // This is used to add a "add new" link
        if ($value['form']['add_new'] != '') {
            if (substr($this->conf['insert.']['add_new.']['icon'], 0, 4) == 'EXT:')
                $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath("wfqbe") . substr($this->conf['insert.']['add_new.']['icon'], 10);
            else
                $path = $this->conf['insert.']['add_new.']['icon'];
            $rA['###INSERT_ADD_NEW###'] = "<a href='#' onclick=\"javascript:submitActions(); document.getElementById('wfqbe_add_new').value='" . $key . "'; document.getElementById('" . $this->conf['ff_data']['div_id'] . "_form').submit(); return false;\"><img src='" . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $path . "' /></a>";
//$rA['###INSERT_ADD_NEW###'] = "<button type='submit' name='tx_wfqbe_pi1[wfqbe_add_new]' value='".$key."'><img src='".\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL').$path."' /></button>";
            //$rA['###INSERT_ADD_NEW###'] = "<input type='image' name='tx_wfqbe_pi1[wfqbe_add_new]' value='".$key."' src='".\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL').$path."' />";
        } else {
            $rA['###INSERT_ADD_NEW###'] = '';
        }

        // This is used to add a "select wizard" link
        if ($value['form']['select_wizard'] != '') {
            if (substr($this->conf['insert.']['select_wizard.']['icon'], 0, 4) == 'EXT:')
                $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath("wfqbe") . substr($this->conf['insert.']['select_wizard.']['icon'], 10);
            else
                $path = $this->conf['insert.']['select_wizard.']['icon'];
            $rA['###INSERT_SELECT_WIZARD###'] = "<a href='#' onclick=\"javascript:submitActions(); document.getElementById('wfqbe_select_wizard').value='" . $key . "'; document.getElementById('" . $this->conf['ff_data']['div_id'] . "_form').submit(); return false;\"><img src='" . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $path . "' /></a>";
            //$rA['###INSERT_SELECT_WIZARD###'] = '<input id="'.$name.'" type="image" name="tx_wfqbe_pi1[wfqbe_select_wizard]" value="'.$key.'" src="'.\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL').$path.'" />';
        } else {
            $rA['###INSERT_SELECT_WIZARD###'] = '';
        }

        // Output of the custom validation of this field
        $rA['###CUSTOM_VALIDATION_TEXT###'] = $custom_validation;

        switch ($value['type']) {
            case 'hidden':
            case 'PHP function':
            case 'Raw HTML':
                $blockList = $this->cObj->substituteMarkerArray($hiddenTemplate, $rA);
                break;
            default:
                $blockList = $this->cObj->substituteMarkerArray($blockTemplate, $rA);
                break;
        }
        unset($rA);

        if (!$show_required)
            $blockList = $this->cObj->substituteSubpart($blockList, '###REQUIRED_TEMPLATE###', '', 0, 0);

        if ($custom_validation == "")
            $blockList = $this->cObj->substituteSubpart($blockList, '###CUSTOM_VALIDATION_TEMPLATE###', '', 0, 0);

        return $blockList;
    }


    function showDisplay($value, $name, $id)
    {
        if ($this->pibase->piVars[$name] != '')
            return $this->charToEntity($this->pibase->piVars[$name]) . '<input id="' . $id . '" type="hidden" name="tx_wfqbe_pi1[' . $name . ']" value="' . htmlspecialchars($this->pibase->piVars[$name]) . '" />';

        if ($value['form']['code'] != '')
            $displayValue = eval($value['form']['code']);
        else
            $displayValue = $value['form']['value'];

        return '<span class="value_' . $id . '">' . $displayValue . '</span><input id="' . $id . '" type="hidden" name="tx_wfqbe_pi1[' . $name . ']" value="' . htmlspecialchars($displayValue) . '" />';
    }


    function showInput($value, $name, $id)
    {
        $attributes = '';
        if ($value['form']['size'] != '')
            $attributes .= ' size="' . $value['form']['size'] . '"';
        if ($value['form']['maxlength'] != '')
            $attributes .= ' maxlength="' . $value['form']['maxlength'] . '"';
        $fieldValue = $this->pibase->piVars[$name] != '' ? $this->pibase->piVars[$name] : ($this->pibase->piVars[$value['field']] != '' ? $this->pibase->piVars[$value['field']] : '');
        return '<input' . $attributes . ' id="' . $id . '" type="text" name="tx_wfqbe_pi1[' . $name . ']" value="' . $this->charToEntity($fieldValue) . '" />';
    }


    //This Function shows a Datetype field (added by Fabian Moser)
    function showDatetype($value, $name, $id)
    {
        $attributes = '';
        if ($value['form']['size'] != '')
            $attributes .= ' size="' . $value['form']['size'] . '"';
        //if ($value['form']['maxlength']!='')
        $attributes .= ' maxlength="10"';
        $fieldValue = $this->pibase->piVars[$name] != '' ? $this->pibase->piVars[$name] : ($this->pibase->piVars[$value['field']] != '' ? $this->pibase->piVars[$value['field']] : '');
        return '<input' . $attributes . ' id="' . $id . '" type="text" name="tx_wfqbe_pi1[' . $name . ']" value="' . $this->charToEntity($fieldValue) . '" />';
    }


    function showCalendar($value, $name, $id, &$blockTemplate)
    {
        if ($value['form']['date2cal'] == 'si' && t3lib_extMgm::isLoaded('date2cal')) {
            include_once(t3lib_extMgm::siteRelPath('date2cal') . '/src/class.jscalendar.php');
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

            $fieldValue = $this->pibase->piVars[$name] != '' ? $this->pibase->piVars[$name] : ($this->pibase->piVars[$value['field']] != '' ? $this->pibase->piVars[$value['field']] : '');
            $field = $JSCalendar->render($this->charToEntity($fieldValue), 'tx_wfqbe_pi1[' . $name . ']');
            //$field = str_replace('name="tx_wfqbe_pi1['.$name.']_hr"', 'name="tx_wfqbe_pi1['.$name.']"', $field);

            // get initialisation code of the calendar
            if (($jsCode = $JSCalendar->getMainJS()) != '') {
                $GLOBALS['TSFE']->additionalHeaderData['wfqbe_date2cal'] = $jsCode;
            }

            return $field;

        } elseif ($value['form']['date2cal'] == 'si' && !t3lib_extMgm::isLoaded('date2cal')) {
            return '<br />ERROR: date2cal extension is not loaded! Please install it or switch to jQuery datepicker.<br />';

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
                $GLOBALS['TSFE']->additionalHeaderData['wfqbe_datepicker'] .= $jsCode;
                $fieldValue = $this->pibase->piVars[$name] != '' ? $this->pibase->piVars[$name] : ($this->pibase->piVars[$value['field']] != '' ? $this->pibase->piVars[$value['field']] : '');

                return '<input id="' . $id . '" type="text" name="tx_wfqbe_pi1[' . $name . ']" value="' . $this->charToEntity($fieldValue) . '" />';

            } elseif ($this->pibase->beMode) {
                // Uses extbase calendar

                $format = str_replace('dd', 'DD', $value['form']['format']);
                $format = str_replace('mm', 'MM', $format);
                $format = str_replace('yy', 'YYYY', $format);

                $fieldId = "tceforms-datetimefield-$id";


                $fieldValue = $this->pibase->piVars[$name] != '' ? $this->pibase->piVars[$name] : ($this->pibase->piVars[$value['field']] != '' ? $this->pibase->piVars[$value['field']] : '');

                $JScode = '<script type="text/javascript">
                    TYPO3.jQuery( window ).load(function() {
                         TYPO3.jQuery("#'.$fieldId.'").val("'.$this->charToEntity($fieldValue).'");
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

                return $JScode .
                 '<div class="form-control-clearable"><input name="tx_wfqbe_pi1[' . $name . ']" 
                                         data-date-type="datetime" 
                                         class="t3js-datetimepicker form-control t3js-clearable hasDefaultValue" 
                                         type="text" 
                                         id="' . $fieldId . '" 
                                         value="' . $this->charToEntity($fieldValue) . '" />' .
                    '<button type="button" class="close" tabindex="-1" aria-hidden="true" style="display: none;"><span class="fa fa-times"></span></button></div>'.
                    '<span class="input-group-btn">
                            <label class="btn btn-default" for="'.$fieldId.'">
                                '.$icon->__toString().'
                            </label>
                        </span>';
            }
        }
    }


    function showPassword($value, $name, $id)
    {
        $fieldValue = $this->pibase->piVars[$name] != '' ? $this->pibase->piVars[$name] : ($this->pibase->piVars[$value['field']] != '' ? $this->pibase->piVars[$value['field']] : '');
        return '<input id="' . $id . '" type="password" name="tx_wfqbe_pi1[' . $name . ']" value="' . $fieldValue . '" />';
    }


    function showHidden($value, $name, $id)
    {
        $hidden_value = $value['form']['value'];
        if ($value['form']['value_from_parameter'] != '' && $this->pibase->piVars[$value['form']['value_from_parameter']] != '')
            $hidden_value = $this->pibase->piVars[$value['form']['value_from_parameter']];
        elseif ($value['form']['value_from_parameter'] != '' && $this->pibase->piVars[$name] != '')
            $hidden_value = $this->pibase->piVars[$name];
        elseif ($value['form']['value_from_parameter'] != '' && $this->old_piVars[$name] != '')
            $hidden_value = $this->old_piVars[$value['form']['value_from_parameter']];

        return '<input id="' . $id . '" type="hidden" name="tx_wfqbe_pi1[' . $name . ']" value="' . $hidden_value . '" />';
    }


    function showRelation($value, $name, $h, $id)
    {
        if (is_array($this->pibase->piVars[$name])) {
            foreach ($this->pibase->piVars[$name] as $uidkey => $uidval)
                $this->pibase->piVars[$name][$uidkey] = intval($uidval);
            $uids = implode(",", $this->pibase->piVars[$name]);
        } else {
            $uids = intval($this->pibase->piVars[$name]);
            unset($this->pibase->piVars[$name]);
            $this->pibase->piVars[$name] = explode(",", $uids);
        }

        $select = $value['form']['field_insert'] . ',' . $value['form']['field_view'];
        if (is_array($value['form']['field_view_sub'])) {
            foreach ($value['form']['field_view_sub'] as $subkey => $subview) {
                if ($subview['field'] != '')
                    $select .= ',' . $subview['field'];
            }
        }

        $query = 'SELECT ' . $select . ' FROM ' . $value['form']['table'] . ' WHERE ' . $value['form']['field_insert'] . ' IN (' . $uids . ')';
        $ris = $h->Execute($query);

        if (!$ris) {
            return "";
        }

        $content = '';
        $i = 0;

        if ($value['form']['multiple'] == '1') {
            while ($array = $ris->FetchRow()) {
                if ($value['form']['allow_edit'] == 1) {
                    if (substr($this->conf['insert.']['edit_wizard.']['icon'], 0, 4) == 'EXT:')
                        $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath("wfqbe") . substr($this->conf['insert.']['edit_wizard.']['icon'], 10);
                    else
                        $path = $this->conf['insert.']['edit_wizard.']['icon'];
                    $content .= "<a href='#' onclick=\"javascript:submitActions(); document.getElementById('wfqbe_add_new').value='" . $name . "'; document.getElementById('wfqbe_edit_subrecord').value='" . $array[$value['form']['field_insert']] . "'; document.getElementById('" . $this->conf['ff_data']['div_id'] . "_form').submit(); return false;\"><img src='" . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $path . "' /></a>";
                }
                if ($value['form']['allow_delete'] == 1) {
                    if (substr($this->conf['insert.']['delete_wizard.']['icon'], 0, 4) == 'EXT:')
                        $path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath("wfqbe") . substr($this->conf['insert.']['delete_wizard.']['icon'], 10);
                    else
                        $path = $this->conf['insert.']['delete_wizard.']['icon'];
                    $content .= " <a href='#' onclick=\"javascript:submitActions(); document.getElementById('wfqbe_add_new').value='" . $name . "'; document.getElementById('wfqbe_delete_subrecord').value='" . $array[$value['form']['field_insert']] . "'; document.getElementById('" . $this->conf['ff_data']['div_id'] . "_form').submit(); return false;\"><img src='" . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $path . "' /></a>";
                }

                $content .= ' ' . $array[$value['form']['field_view']];
                if (is_array($value['form']['field_view_sub'])) {
                    foreach ($value['form']['field_view_sub'] as $subkey => $subview) {
                        if ($subview['sep'] != '')
                            $content .= $subview['sep'];
                        if ($subview['field'] != '')
                            $content .= $array[$subview['field']];
                    }
                }
                $content .= '<input id="' . $id . '" type="hidden" name="tx_wfqbe_pi1[' . $name . '][]" value="' . $array[$value['form']['field_insert']] . '" />';
                $content .= '<br />';
                $i++;
            }
        } else {
            $array = $ris->FetchRow();
            $content .= $array[$value['form']['field_view']];
            if (is_array($value['form']['field_view_sub'])) {
                foreach ($value['form']['field_view_sub'] as $subkey => $subview) {
                    if ($subview['sep'] != '')
                        $content .= $subview['sep'];
                    if ($subview['field'] != '')
                        $content .= $array[$subview['field']];
                }
            }
            $content .= '<input id="' . $id . '" type="hidden" name="tx_wfqbe_pi1[' . $name . ']" value="' . $array[$value['form']['field_insert']] . '" />';
        }
        return $content;
    }


    function showTextarea($value, $name, $id)
    {
        $wfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_wfqbe_pi1');
        if ($value['form']['rows'] == "")
            $value['form']['rows'] = 5;
        if ($value['form']['cols'] == "")
            $value['form']['cols'] = 50;

        $fieldValue = $this->pibase->piVars[$name] != '' ? $this->pibase->piVars[$name] : ($this->pibase->piVars[$value['field']] != '' ? $this->pibase->piVars[$value['field']] : '');
        $content = "<textarea id='" . $id . "' name='tx_wfqbe_pi1[" . $name . "]' rows='" . $value['form']['rows'] . "' cols='" . $value['form']['cols'] . "'>" . $fieldValue . "</textarea>";

        return $content;
    }


    function showUpload($value, $name, $id)
    {
        $html = '';
        $html .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . $value['form']['maxfilesize'] . '" />';
        $html .= '<input type="hidden" id="' . $this->conf['ff_data']['div_id'] . '_file_delete" name="tx_wfqbe_pi1[file_delete]" value="" />';
        $html .= '<input type="hidden" id="' . $this->conf['ff_data']['div_id'] . '_action_required" name="tx_wfqbe_pi1[action_required]" value="" />';
        for ($i = 0; $i < $value['form']['numofuploads']; $i++) {
            if (isset($this->pibase->piVars[$name][$i]) && $this->pibase->piVars[$name][$i] != "") {
                $html .= '<input id="' . $id . '_' . $i . '" type="hidden" name="tx_wfqbe_pi1[' . $name . '][' . $i . ']" value="' . $this->pibase->piVars[$name][$i] . '" />';
                $html .= '<a href="' . $this->blocks['fields'][$name]['form']['basedir'] . $this->pibase->piVars[$name][$i] . '">' . $this->pibase->piVars[$name][$i] . '</a>';
                if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1) {
                    $update = 'onclick=" document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_action_required\').value=\'' . $name . '\'; document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_file_delete\').value=\'' . $this->blocks['fields'][$name]['form']['basedir'] . $this->pibase->piVars[$name][$i] . '\'; document.getElementById(\'' . $id . '_' . $i . '\').value=\'\'; document.getElementById(\'wfqbe_destination_id\').value=\'' . $value['form']['onchange'] . '\'; ' . $this->prefixId . 'processInsertData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_form\')); return false;"';
                } else {
                    $params = array();
                    $params['parameter'] = $GLOBALS['TSFE']->id;
                    $action = $this->cObj->typoLink_URL($params);

                    $update = 'onclick=" document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_action_required\').value=\'' . $name . '\'; document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_file_delete\').value=\'' . $this->blocks['fields'][$name]['form']['basedir'] . $this->pibase->piVars[$name][$i] . '\'; document.getElementById(\'' . $id . '_' . $i . '\').value=\'\'; document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').action=\'' . $action . '\'; document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').onsubmit=\'\'; document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').submit(); return false;"';
                }
                $html .= '&nbsp;-&nbsp;(<a href="#"' . $update . '>delete</a>)';
            } else {
                $html .= '<input id="' . $id . '_' . $i . '" type="file" name="tx_wfqbe_pi1[' . $name . '][' . $i . ']" value="" />';
            }
            if ($i < $value['form']['numofuploads'] - 1)
                $html .= '<br />';
        }
        return $html;
    }


    function showRadio($value, $name, $h, $id)
    {
        $wfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_wfqbe_pi1');
        $listaRadio = '';

        if ($value['form']['source'] == 'static') {
            for ($i = 0; $i < $value['form']['numValues']; $i++) {
                if ($i == 0)
                    $idi = ' id="' . $id . '"';
                else
                    $idi = '';
                if ($value['form'][$i]['value'] == $this->pibase->piVars[$name] || ($this->pibase->piVars[$name] == '' && $value['form'][$i]['value'] == $this->pibase->piVars[$value['field']]))
                    $listaRadio .= '<input' . $idi . ' checked="checked" type="radio" name="tx_wfqbe_pi1[' . $name . ']" value="' . $value['form'][$i]['value'] . '" /> ' . $value['form'][$i]['label'];
                else
                    $listaRadio .= '<input' . $idi . ' type="radio" name="tx_wfqbe_pi1[' . $name . ']" value="' . $value['form'][$i]['value'] . '" /> ' . $value['form'][$i]['label'];
                if ($i < $value['form']['numValues'] - 1)
                    $listaRadio .= '<br />';
            }
        } else {
            // Source == db
            $where = "";
            if ($value['form']['where'] != "") {
                $where = 'WHERE ' . $this->substituteInsertMarkers($value['form']['where']) . " ";
            }
            $orderby = $value['form']['field_orderby'] != '' ? ($value['form']['field_orderby'] . ' ' . $value['form']['field_orderby_mod']) : $value['form']['field_view'];
            $query = 'SELECT ' . $value['form']['field_view'] . ', ' . $value['form']['field_insert'] . ' FROM ' . $value['form']['table'] . ' ' . $where . 'ORDER BY ' . $orderby;
            $ris = $h->Execute($query);

            if (!$ris) {
                return "";
            }

            $i = 0;
            while ($array = $ris->FetchRow()) {
                if ($i == 0)
                    $idi = ' id="' . $id . '"';
                else
                    $idi = '';
                if ($array[1] == $this->pibase->piVars[$name] || ($this->pibase->piVars[$name] == '' && $array[1] == $this->pibase->piVars[$value['field']]))
                    $listaRadio .= '<input' . $idi . ' checked="checked" type="radio" name="tx_wfqbe_pi1[' . $name . ']" value="' . $array[1] . '" /> ' . $array[0] . '<br />';
                else
                    $listaRadio .= '<input' . $idi . ' type="radio" name="tx_wfqbe_pi1[' . $name . ']" value="' . $array[1] . '" /> ' . $array[0] . '<br />';
                $i++;
            }
            if ($listaRadio != '')
                $listaRadio = substr($listaRadio, 0, -6);
        }

        return $listaRadio;
    }


    function showCheckbox($value, $name, $h, $id)
    {
        $wfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_wfqbe_pi1');
        $listaRadio = '';

        if ($value['form']['source'] == 'static') {
            for ($i = 0; $i < $value['form']['numValues']; $i++) {
                if ($i == 0)
                    $idi = ' id="' . $id . '"';
                else
                    $idi = '';
                if ((is_array($this->pibase->piVars[$name]) && in_array($value['form'][$i]['value'], $this->pibase->piVars[$name])) || (!is_array($this->pibase->piVars[$name]) && $this->pibase->piVars[$value['field']] != '' && in_array($value['form'][$i]['value'], explode(',', $this->pibase->piVars[$value['field']]))))
                    $listaRadio .= '<input' . $idi . ' checked="checked" type="checkbox" name="tx_wfqbe_pi1[' . $name . '][]" value="' . $value['form'][$i]['value'] . '" /> ' . $value['form'][$i]['label'];
                else
                    $listaRadio .= '<input' . $idi . ' type="checkbox" name="tx_wfqbe_pi1[' . $name . '][]" value="' . $value['form'][$i]['value'] . '" /> ' . $value['form'][$i]['label'];
                if ($i < $value['form']['numValues'] - 1)
                    $listaRadio .= '<br />';
            }
        } else {
            // Source == db
            $where = "";
            if ($value['form']['where'] != "") {
                $where = 'WHERE ' . $this->substituteInsertMarkers($value['form']['where']) . " ";
            }
            $orderby = $value['form']['field_orderby'] != '' ? ($value['form']['field_orderby'] . ' ' . $value['form']['field_orderby_mod']) : $value['form']['field_view'];
            $query = 'SELECT ' . $value['form']['field_view'] . ', ' . $value['form']['field_insert'] . ' FROM ' . $value['form']['table'] . ' ' . $where . 'ORDER BY ' . $orderby;
            $ris = $h->Execute($query);
            $i = 0;
            while ($array = $ris->FetchRow()) {
                if ($i == 0)
                    $idi = ' id="' . $id . '"';
                else
                    $idi = '';
                if ((is_array($this->pibase->piVars[$name]) && in_array($array[1], $this->pibase->piVars[$name])) || (!is_array($this->pibase->piVars[$name]) && $this->pibase->piVars[$value['field']] != '' && in_array($array[1], explode(',', $this->pibase->piVars[$value['field']]))))
                    $listaRadio .= '<input' . $idi . ' checked="checked" type="checkbox" name="tx_wfqbe_pi1[' . $name . '][]" value="' . $array[1] . '" /> ' . $array[0];
                else
                    $listaRadio .= '<input' . $idi . ' type="checkbox" name="tx_wfqbe_pi1[' . $name . '][]" value="' . $array[1] . '" /> ' . $array[0];
                $listaRadio .= '<br />';
                $i++;
            }
            if ($listaRadio != '')
                $listaRadio = substr($listaRadio, 0, -6);
        }

        return $listaRadio;
    }


    function showSelect($value, $name, $h, $id)
    {

        if ($value['form']['onchange'] != "") {
            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1) {
                $update = ' onchange="document.getElementById(\'wfqbe_destination_id\').value=\'' . $value['form']['onchange'] . '\'; ' . $this->prefixId . 'processInsertData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_form\')); return false;"';
            } else {
                $params = array();
                $params['parameter'] = $GLOBALS['TSFE']->id;
                $action = $this->cObj->typoLink_URL($params);
                $update = ' onchange="document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').action=\'' . $action . '\'; document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_form\').onsubmit=\'\'; submit(); return false;"';
            }
        } else {
            $update = '';
        }

        if ($value['form']['multiple'] == 'si') {
            $size = $value['form']['size'] > 0 ? $value['form']['size'] : 5;
            $listaSelect = '<select' . $update . ' id="' . $id . '" name="tx_wfqbe_pi1[' . $name . '][]" size="' . $size . '" multiple="multiple">';
        } else
            $listaSelect = '<select' . $update . ' id="' . $id . '" name="tx_wfqbe_pi1[' . $name . '][]">';

        if ($value['required'] != 1)
            $listaSelect .= '<option value="">' . $value['form']['labelEmptyValue'] . '</option>';

        if ($value['form']['source'] == 'static') {
            for ($i = 0; $i < $value['form']['numValues']; $i++) {
                if ((is_array($this->pibase->piVars[$name]) && in_array($value['form'][$i]['value'], $this->pibase->piVars[$name])) || (!is_array($this->pibase->piVars[$name]) && $this->pibase->piVars[$value['field']] != '' && in_array($value['form'][$i]['value'], explode(',', $this->pibase->piVars[$value['field']]))))
                    $listaSelect .= '<option selected="selected" value="' . $value['form'][$i]['value'] . '"> ' . $value['form'][$i]['label'] . '</option>';
                else
                    $listaSelect .= '<option value="' . $value['form'][$i]['value'] . '"> ' . $value['form'][$i]['label'] . '</option>';
            }
        } else {
            // Source == db
            $where = "";
            if ($value['form']['where'] != "") {
                $where = 'WHERE ' . $this->substituteInsertMarkers($value['form']['where']) . " ";
            }

            if (is_array($value['form']['field_view_sub'])) {
                foreach ($value['form']['field_view_sub'] as $subkey => $subview) {
                    if ($subview['field'] != '')
                        $select .= ',' . $subview['field'];
                }
            }

            $orderby = $value['form']['field_orderby'] != '' ? ($value['form']['field_orderby'] . ' ' . $value['form']['field_orderby_mod']) : $value['form']['field_view'];
            $query = 'SELECT ' . $value['form']['field_view'] . ', ' . $value['form']['field_insert'] . $select . ' FROM ' . $value['form']['table'] . ' ' . $where . 'ORDER BY ' . $orderby;
            $ris = $h->Execute($query);

            if ($ris !== false) {
                while ($array = $ris->FetchRow()) {
                    $label = $array[$value['form']['field_view']];
                    if (is_array($value['form']['field_view_sub'])) {
                        foreach ($value['form']['field_view_sub'] as $subkey => $subview) {
                            if ($subview['sep'] != '')
                                $label .= $subview['sep'];
                            if ($subview['field'] != '')
                                $label .= $array[$subview['field']];
                        }
                    }

                    if ((is_array($this->pibase->piVars[$name]) && in_array($array[1], $this->pibase->piVars[$name])) || (!is_array($this->pibase->piVars[$name]) && $this->pibase->piVars[$value['field']] != '' && in_array($array[1], explode(',', $this->pibase->piVars[$value['field']]))))
                        $listaSelect .= '<option selected="selected" value="' . $array[1] . '"> ' . $label . '</option>';
                    else
                        $listaSelect .= '<option value="' . $array[1] . '"> ' . $label . '</option>';
                }
            } else {
                return '<div id="' . $id . '">Query failed: ' . $query . '</div>';
            }
        }

        return $listaSelect . '</select>';
    }


    function showRawHTML($value, $name, $id)
    {
        return $value['form']['code'];
    }


    function substituteInsertMarkers($where)
    {
        // Query parameters management
        // This function substitutes the markers like ###WFQBE_X### with \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wfqbe[x]')
        // where x is the position of the field in the insert form (starting from 0)
        $parametri = $this->pibase->piVars;
        $markerParametri = array();
        if (is_array($parametri)) {
            foreach ($parametri as $key => $value) {
                if (!is_array($value)) {
                    $markerParametri["###WFQBE_" . $key . "###"] = strip_tags($value);
                } elseif (sizeof($value) == 1) {
                    $markerParametri["###WFQBE_" . $key . "###"] = strip_tags($value[0]);
                } else {
                    $i = 0;
                    foreach ($value as $sel) {
                        if ($i > 0)
                            $markerParametri["###WFQBE_" . $key . "###"] .= "'";
                        $markerParametri["###WFQBE_" . $key . "###"] .= strip_tags($sel);
                        if ($i < sizeof($value) - 1)
                            $markerParametri["###WFQBE_" . $key . "###"] .= "',";
                        $i++;
                    }
                }
            }
            $where = $this->cObj->substituteMarkerArray($where, $markerParametri);
        }

        // This is used to parse the query and to retrieve the TS markers (like ###TS_WFQBE_xxx###) and non-substituted markers (like ###WFQBE_xxx###)
        // This markers are replaced with the output of TS objects defined in your TS template
        $tsMarkers = $this->getTSMarkers($where);
        if (is_array($tsMarkers)) {
            foreach ($tsMarkers as $marker) {
                if ($this->conf['customInsert.'][$this->row['uid'] . '.'][$marker] != "" && (($markerParametri["###" . $marker . "###"] == '' && $this->conf['customInsert.'][$this->row['uid'] . '.'][$marker . "."]["overrideIfEmpty"] == 1) || $this->conf['customInsert.'][$this->row['uid'] . '.'][$marker . "."]["overrideAlways"] == 1)) {
                    $confArray = $this->conf["customInsert."][$this->row['uid'] . "."][$marker . "."];
                    //$confArray = $this->parseTypoScriptConfiguration($confArray, $wfqbeArray);
                    $func = $this->conf["customInsert."][$this->row["uid"]."."][$marker];
                    $markerParametri["###" . $marker . "###"] = "{$this->cObj->$func($confArray)}";
                }
            }
            //$query = $this->cObj->substituteMarkerArray($query, $markerParametri);
            $where = $this->cObj->substituteMarkerArray($where, $markerParametri);
        }

        return preg_replace("/(###)+[a-z,A-Z,0-9,@,!,_]+(###)/", "", $where);
    }


    /**
     * This function is used to retrieve the markers from a query
     */
    function getTSMarkers($query)
    {
        if (preg_match_all("/([#]{3})([a-z,A-Z,0-9,@,!,_]*)([#]{3})/", $query, $markers))
            return $markers[2];
        else
            return null;
    }


    /**
     * Upload files function
     */
    function uploadFiles($blocks)
    {
        $content = '';
        foreach ($_FILES['tx_wfqbe_pi1']['error'] as $field_key => $field) {
            foreach ($field as $key => $error) {
                switch ($error) {
                    case 0:
                        if ($this->pibase->beMode == 1) {
                            global $BACK_PATH;
                            $blocks['fields'][strtoupper($field_key)]['form']['basedir'] = $BACK_PATH . '../' . $blocks['fields'][strtoupper($field_key)]['form']['basedir'];
                        }
                        $dirs = explode('/', $blocks['fields'][strtoupper($field_key)]['form']['basedir']);
                        $basedir = '';
                        foreach ($dirs as $dir) {
                            if ($dir != "") {
                                $basedir .= $dir . '/';
                                $upDir = $basedir;
                                if (!is_dir($upDir)) {
                                    mkdir($upDir);
                                    if (!is_dir($upDir))
                                        $content .= '<br />Error: directory "' . $upDir . '" not created.';
                                }
                            }
                        }
                        if (is_dir($upDir)) {
                            if ($blocks['fields'][strtoupper($field_key)]['form']['donotrename'] == 1) {
                                $upFile = $upDir . $_FILES['tx_wfqbe_pi1']['name'][$field_key][$key];
                                if ($blocks['fields'][strtoupper($field_key)]['form']['overwrite'] == 1) {
                                    if (file_exists($upFile)) {
                                        unlink($upFile);
                                    }
                                } else {
                                    if (file_exists($upFile)) {
                                        $i = 1;
                                        do {
                                            $fileParts = explode('.', $_FILES['tx_wfqbe_pi1']['name'][$field_key][$key]);
                                            if (count($fileParts) == 1)
                                                $filename = $fileParts[0] . '_' . ($i < 10 ? '0' . $i : $i);
                                            else {
                                                $fileParts[count($fileParts) - 2] .= '_' . ($i < 10 ? '0' . $i : $i);
                                                $filename = implode('.', $fileParts);
                                            }
                                            $upFile = $upDir . $filename;
                                            $i++;
                                        } while (file_exists($upFile));
                                    }
                                }
                            } else {
                                $upFile = $upDir . time() . "_" . $_FILES['tx_wfqbe_pi1']['name'][$field_key][$key];
                            }

                            if (move_uploaded_file($_FILES['tx_wfqbe_pi1']['tmp_name'][$field_key][$key], $upFile)) {
                                //$content .= '<br />File '.$_FILES['tx_wfqbe_pi1']['name'][$field_key][$key].' has been uploaded correctly';
                                $this->pibase->piVars[$field_key][] = str_replace($blocks['fields'][strtoupper($field_key)]['form']['basedir'], "", $upFile);
                            } else
                                $content .= '<br />Error: file ' . $_FILES['tx_wfqbe_pi1']['name'][$field_key][$key] . ' has NOT been uploaded correctly';
                        }
                        break;
                    case 1:
                    case 2:
                        $content .= '<br />Error: ' . $_FILES['tx_wfqbe_pi1']['name'][$field_key][$key] . ' is too big.';
                        break;
                    case 3:
                        $content .= '<br />Error: ' . $_FILES['tx_wfqbe_pi1']['name'][$field_key][$key] . ' has been partially uploaded.';
                        break;
                    case 4:
                        //$content .= '<br />Error: '.$_FILES['tx_wfqbe_pi1']['name'][$field_key][$key].', no such file.';
                        break;
                    default:
                        $content .= '<br />Error: ' . $_FILES['tx_wfqbe_pi1']['name'][$field_key][$key] . ', unknown problem.';
                        break;
                }
            }
        }
        return $content;
    }


    /**
     * Function used to delete files
     * @param string file
     */
    function deleteFiles($file)
    {
        if ($this->pibase->beMode != '') {
            global $BACK_PATH;
            $fileToDelete = $BACK_PATH . '../' . $file;
            unlink($fileToDelete);
        } else {
            unlink($file);
        }
    }


    function showPageConfirmation($data, $blocks, $h, $file, $rowQuery, $final = false)
    {
        $content = '';
        $required = $this->checkRequired($data, $blocks);

        if (sizeof($required['required']) == 0 && sizeof($required['custom_validation']) == 0) {
            if ($final)
                $file = $this->cObj->getSubpart($file, '###INSERT_FINAL_TEMPLATE###');
            else
                $file = $this->cObj->getSubpart($file, '###INSERT_CONFIRMATION_TEMPLATE###');
            $blockTemplate = $this->cObj->getSubpart($file, '###INSERT_BLOCK###');
            $blockList = '';

            foreach ($data as $key => $value) {
                if (!is_numeric($key) && !$final) {
                    $blockList .= "<input type='hidden' name='tx_wfqbe_pi1[" . $key . "]' value='" . $this->charToEntity($value) . "' />";
                }
            }

            foreach ($blocks['fields'] as $key => $config) {
                if ($blocks['fields'][$key]['type'] != 'PHP function' && $blocks['fields'][$key]['type'] != 'Raw HTML') {

                    $value = $data[$key];

                    $mA = array();
                    $mA['###INSERT_ID###'] = $key;
                    $mA['###INSERT_VALUE###'] = '';

                    if (!is_array($blocks['fields'][$key]['form']['label']))
                        $mA['###INSERT_LABEL###'] = $blocks['fields'][$key]['form']['label'];
                    else {
                        // This will show the label in the correct language
                        if ($GLOBALS['TSFE']->sys_language_uid == 0 || $blocks['fields'][$key]['form']['label'][$GLOBALS['TSFE']->sys_language_uid] == '')
                            $mA['###INSERT_LABEL###'] = $blocks['fields'][$key]['form']['label']['def'];
                        else
                            $mA['###INSERT_LABEL###'] = $blocks['fields'][$key]['form']['label'][$GLOBALS['TSFE']->sys_language_uid];
                    }

                    if (is_array($value) && $value[0] != '') {
                        if ($blocks['fields'][$key]['form']['source'] == 'db' || $blocks['fields'][$key]['type'] == 'relation') {
                            $query = 'SELECT ' . $blocks['fields'][$key]['form']['field_view'] . ' FROM ' . $blocks['fields'][$key]['form']['table'] . ' WHERE ' . $blocks['fields'][$key]['form']['field_insert'] . ' IN (' . implode(",", $value) . ')';
                            $res = $h->Execute($query);
                            if ($res !== false)
                                while ($row = $res->FetchRow())
                                    $mA['###INSERT_VALUE###'] .= $row[$blocks['fields'][$key]['form']['field_view']] . ',';
                            if (strlen($mA['###INSERT_VALUE###']) > 0)
                                $mA['###INSERT_VALUE###'] = substr($mA['###INSERT_VALUE###'], 0, -1);
                        } elseif ($blocks['fields'][$key]['form']['source'] == 'static') {
                            foreach ($value as $i => $v) {
                                $mA['###INSERT_VALUE###'] .= $this->searchSelectStaticLabel($blocks['fields'][$key]['form'], $v) . ',';
                            }
                            if (strlen($mA['###INSERT_VALUE###']) > 0)
                                $mA['###INSERT_VALUE###'] = substr($mA['###INSERT_VALUE###'], 0, -1);
                        } else {
                            $mA['###INSERT_VALUE###'] = implode(',', $value);
                        }
                        foreach ($value as $i => $v) {
                            $mA['###INSERT_FIELD###'] .= "<input type='hidden' name='tx_wfqbe_pi1[" . $key . "][" . $i . "]' value='" . $this->charToEntity($v) . "' />";
                        }
                    } else {
                        if ($blocks['fields'][$key]['type'] == 'password') {
                            $mA['###INSERT_VALUE###'] = "********";
                        } elseif (($blocks['fields'][$key]['form']['source'] == 'db' || $blocks['fields'][$key]['type'] == 'relation') && $value != '' && !is_array($value)) {
                            if (!is_numeric($value))
                                $value = addslashes($value);
                            $query = 'SELECT ' . $blocks['fields'][$key]['form']['field_view'] . ' FROM ' . $blocks['fields'][$key]['form']['table'] . ' WHERE ' . $blocks['fields'][$key]['form']['field_insert'] . '="' . $value . '"';
                            $res = $h->Execute($query);
                            $mA['###INSERT_VALUE###'] = '';
                            while ($res !== false && $row = $res->FetchRow())
                                $mA['###INSERT_VALUE###'] = $row[$blocks['fields'][$key]['form']['field_view']];
                            /*}	elseif ($blocks['fields'][$key]['form']['source']=='static')	{
                            $mA['###INSERT_VALUE###'] .= $blocks['fields'][$key]['form'][$value]['label'];*/
                        } elseif ($blocks['fields'][$key]['type'] == 'hidden' || $blocks['fields'][$key]['type'] == 'PHP function') {
                            $mA['###INSERT_VALUE###'] = '';
                        } else {
                            if ($blocks['fields'][$key]['form']['clear'] != "") {
                                $func = str_replace('|', str_replace(array("\\", "'"), array("\\\\", "\\'"), $value), $blocks['fields'][$key]['form']['clear']);
                                $value = eval('return ' . $func . ';');
                            }
                            if (is_array($value)) {
                                $value = '';
                            }
                            $mA['###INSERT_VALUE###'] = $value;
                        }
                        $mA['###INSERT_FIELD###'] = "<input type='hidden' name='tx_wfqbe_pi1[" . $key . "]' value='" . $this->charToEntity($value) . "' />";
                    }

                    $blockList .= $this->cObj->substituteMarkerArray($blockTemplate, $mA);
                } elseif ($blocks['fields'][$key]['type'] == 'Raw HTML') {
                    $blockList .= $blocks['fields'][$key]['form']['code'];
                }
            }


            if ($final && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1)
                $blockList .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_this_query]" value="' . $data['wfqbe_this_query'] . '" />';

            $content = $this->cObj->substituteSubpart($file, '###INSERT_BLOCK###', $blockList, 0, 0);

            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax') && $this->conf['enableXAJAX'] == 1) {
                $mA['###XAJAX_SUBMIT###'] = ' onsubmit="' . $this->prefixId . 'processInsertData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_confirmation_form\')); return false;"';
                $mA['###XAJAX_CLEAR_CONFIRM###'] = 'onclick="document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_insert_modify\').value=\'wfqbe_no\';  document.getElementById(\'wfqbe_destination_id\').value=\'\'; ' . $this->prefixId . 'processInsertData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_confirmation_form\')); return false;"';
                $mA['###XAJAX_CLEAR_MODIFY###'] = 'onclick="document.getElementById(\'' . $this->conf['ff_data']['div_id'] . '_insert_confirm\').value=\'wfqbe_no\';  document.getElementById(\'wfqbe_destination_id\').value=\'\'; ' . $this->prefixId . 'processInsertData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_confirmation_form\')); return false;"';
                $mA['###XAJAX_CLEAR###'] = 'onclick="' . $this->prefixId . 'processInsertData(xajax.getFormValues(\'' . $this->conf['ff_data']['div_id'] . '_confirmation_form\')); return false;"';
            } else {
                $mA['###XAJAX_SUBMIT###'] = '';
                $mA['###XAJAX_CLEAR_CONFIRM###'] = '';
                $mA['###XAJAX_CLEAR_MODIFY###'] = '';
                $mA['###XAJAX_CLEAR###'] = '';
            }

            $mA['###CONF_SUBMIT###'] = 'submit_insert';
            $mA['###CONF_DIVID###'] = $this->conf['ff_data']['div_id'];

            $params = array();
            $params['parameter'] = $GLOBALS['TSFE']->id;
            $mA['###CONF_INSERT###'] = $this->cObj->typoLink_URL($params);
            $mA['###CONF_SUBMIT_LABEL###'] = $this->pibase->pi_getLL('insert_confirm', 'Insert');
            $mA['###LABEL_PLEASE_CONFIRM###'] = $this->pibase->pi_getLL('please_confirm', 'Please confirm');


            $mA['###INSERT_HIDDEN_FIELDS###'] = '';
            if ($this->pibase->piVars['wfqbe_add_new'] != '') {
                $API = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_utils");
                if (is_array($this->pibase->piVars['orig']))
                    $mA['###INSERT_HIDDEN_FIELDS###'] = $API->getHiddenFields($this->pibase->piVars['orig'], 'orig');
                else
                    $mA['###INSERT_HIDDEN_FIELDS###'] = $API->getHiddenFields($this->pibase->piVars, 'orig');
                $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_add_new]" value="' . $this->pibase->piVars['wfqbe_add_new'] . '" />';
                $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_edit_subrecord]" value="' . $this->pibase->piVars['wfqbe_edit_subrecord'] . '" />';
                $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_delete_subrecord]" value="' . $this->pibase->piVars['wfqbe_delete_subrecord'] . '" />';

                if ($this->mode == 'edit') {
                    $mA['###CONF_SUBMIT_LABEL###'] = $this->pibase->pi_getLL('update_confirm', 'Update');
                } elseif ($this->mode == 'delete') {
                    $mA['###CONF_SUBMIT_LABEL###'] = $this->pibase->pi_getLL('delete_confirm', 'Delete');
                }
                unset($this->pibase->piVars);

            } else {
                if ($this->mode == 'edit') {
                    $mA['###CONF_SUBMIT_LABEL###'] = $this->pibase->pi_getLL('update_confirm', 'Update');

                    $mA['###LABEL_COMMIT_RESULT###'] = $this->pibase->pi_getLL('commit_result_' . $this->mode, 'Record updated');

                } elseif ($this->mode == 'delete') {
                    $mA['###CONF_SUBMIT_LABEL###'] = $this->pibase->pi_getLL('delete_confirm', 'Delete');
                    $mA['###LABEL_COMMIT_RESULT###'] = $this->pibase->pi_getLL('commit_result_' . $this->mode, 'Record deleted');
                    if ($this->blocks['ID_field'] == '')
                        return "";
                    else {
                        if ($this->pibase->piVars['wfqbe_id_field'] != "" && is_int(intval($this->pibase->piVars['wfqbe_id_field']))) {
                            $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_id_field]" value="' . $this->pibase->piVars['wfqbe_id_field'] . '" />';
                        } else {
                            $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_id_field]" value="' . $this->pibase->piVars[$this->blocks['ID_field']] . '" />';
                        }
                        $mA['###INSERT_HIDDEN_FIELDS###'] .= '<input type="hidden" name="tx_wfqbe_pi1[wfqbe_deleting_mode]" value="1" />';
                    }
                } else {
                    $mA['###CONF_SUBMIT_LABEL###'] = $this->pibase->pi_getLL('insert_confirm', 'Insert');
                    $mA['###LABEL_COMMIT_RESULT###'] = $this->pibase->pi_getLL('commit_result_' . $this->mode, 'Record inserted');
                }
            }

            $mA['###INSERT_DESCRIPTION###'] = $row['description'];
            $mA['###LABEL_MODIFY###'] = $this->pibase->pi_getLL('modify', 'Modify');
            $mA['###LABEL_NEW_RECORD###'] = $this->pibase->pi_getLL('new_record', 'New record');

            $content = $this->cObj->substituteMarkerArray($content, $mA);

        } else {
            $file = $this->cObj->getSubpart($file, '###INSERT_TEMPLATE###');
            $content = $this->showInsertModule($file, $blocks['fields'], $h, $rowQuery, $required);
        }

        return $content;
    }


    function executeQuery($data, $blocks, $h)
    {
        $content = '';
        $results = array();

        if ($this->mode == 'delete') {
            // DELETE
            $id = 0;
            if ($this->pibase->piVars['wfqbe_id_field'] == '' && $this->pibase->piVars['wfqbe_delete_subrecord'] == '') {
                $results['inserted'] = false;
                $results['id'] = "";
                $content .= '<br />ID not set.<br />';
                $results['content'] = $content;
                return $results;
            } else {
                if ($this->pibase->piVars['wfqbe_delete_subrecord'] != '' && $this->pibase->piVars['wfqbe_add_new'] != '')
                    $id = intval($this->pibase->piVars['wfqbe_delete_subrecord']);
                elseif ($this->pibase->piVars['wfqbe_id_field'] != '')
                    $id = intval($this->pibase->piVars['wfqbe_id_field']);
            }

            $query = "SELECT * FROM " . $blocks['table'] . " WHERE " . $blocks['ID_field'] . "=" . $id;
            $res = $h->Execute($query);
            if ($res !== false && $res->recordCount() == 1)
                $results['insert_data'] = $res->FetchRow();

            $sql = "DELETE FROM " . $blocks['table'] . " WHERE " . $blocks['ID_field'] . "=" . $id;
            if ($this->conf['debugQuery'] == 1)
                $content .= '<br />Update SQL: ' . $sql;
            if ($h->Execute($sql) === false)
                $content .= '<br />ERROR DELETING: ' . $h->ErrorMsg() . '<br />';
            else {
                $results['inserted'] = true;
                $results['id'] = -2;
                $results['deleted_id'] = $id;
            }
            // It exits after delete operation
            return $results;
        }

        $required = $this->checkRequired($data, $blocks);
        if (sizeof($required['required']) == 0 && sizeof($required['custom_validation']) == 0) {
            $insert_data = array();
            $insert_data_row = array();

            if (is_array($blocks['fields'])) {
                foreach ($blocks['fields'] as $key => $config) {
                    if ($config['form']['when'][$this->mode] == 1) {
                        if ($config['field'] == 'wfqbe_custom')
                            continue;
                        elseif ($config['type'] == 'PHP function') {
                            $insert_data[$blocks['fields'][$key]['field']] = eval($config['form']['code']);;
                            $insert_data_row[$blocks['fields'][$key]['field']] = eval($config['form']['code']);;
                        } elseif ($config['type'] == 'display only') {
                            continue;
                        } elseif ($blocks['fields'][$key]['field'] == "") {
                            continue;
                        } else {
                            $val = $data[$key];
                            $raw_val = $val;

                            // This is necessary for removing the path of the file. We want to save only the name of the file
                            if ($blocks['fields'][$key]['type'] == "upload" && $val != "") {
                                $val = str_replace($blocks['fields'][$key]['form']['basedir'], "", $val);
                            }

                            //This is necessary to convert the human readable time to a timestamp for the database (added by Fabian Moser)
                            if ($blocks['fields'][$key]['type'] == "datetype" && $val != "") {
                                $val = $this->get_timestamp($val, $blocks['fields'][$key]);
                            }

                            //	This is necessary to convert the human readable time to a timestamp for the database
                            if ($blocks['fields'][$key]['type'] == "calendar" && $blocks['fields'][$key]['form']['convert_timestamp'] == 'si' && $val != "") {
                                $val = $this->get_timestamp($val, $blocks['fields'][$key]);
                            }

                            if ($blocks['fields'][$key]['type'] == "calendar" && $blocks['fields'][$key]['form']['convert_to_date_oracle'] == 'si' && $val != "") {
                                $val = "TO_DATE('" . $val . "', '" . strtoupper(str_replace('yy', 'yyyy', $blocks['fields'][$key]['form']['format'])) . "')";
                            }

                            if (is_array($val))
                                $val = implode(",", $val);

                            if ($blocks['fields'][$key]['form']['clear'] != "") {
                                $func = str_replace('|', str_replace(array("\\", "'"), array("\\\\", "\\'"), $val), $blocks['fields'][$key]['form']['clear']);
                                $val = eval('return ' . $func . ';');
                            }

                            if ($blocks['fields'][$key]['type'] == "calendar" && $blocks['fields'][$key]['form']['convert_to_date_oracle'] == 'si') {
                                if ($val == '') {
                                    $insert_data[$blocks['fields'][$key]['field']] = "''";
                                    $insert_data_row[$blocks['fields'][$key]['field']] = '';
                                } else {
                                    $insert_data[$blocks['fields'][$key]['field']] = $val;
                                    $insert_data_row[$blocks['fields'][$key]['field']] = $raw_val;
                                }
                            } else {
                                if ($val == '' && $blocks['fields'][$key]['form']['insert_NULL'] == 1) {
                                    $insert_data[$blocks['fields'][$key]['field']] = 'NULL';
                                } else {
                                    $insert_data[$blocks['fields'][$key]['field']] = $h->qstr($this->entityToChar($val));
                                }
                                $insert_data_row[$blocks['fields'][$key]['field']] = $val;
                            }

                        }
                    }
                }
            }

            // Hook that can be used to pre-process a parameter (from an insert form) before inserting the new row
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['processProcessInsertValues'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['processProcessInsertValues'] as $_classRef) {
                    $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                    $insert_data = $_procObj->process_insert_values($insert_data, $data, $blocks, $this);
                }
            }

            $results['insert_data'] = $insert_data_row;

            if ($this->mode == 'edit') {
                // UPDATE
                $id = 0;
                if ($this->pibase->piVars['wfqbe_id_field'] == '' && $this->pibase->piVars['wfqbe_edit_subrecord'] == '') {
                    $results['inserted'] = false;
                    $results['id'] = "";
                    $content .= '<br />ID not set';
                    $results['content'] = $content;
                    return $results;
                } else {
                    if ($this->pibase->piVars['wfqbe_edit_subrecord'] != '' && $this->pibase->piVars['wfqbe_add_new'] != '')
                        $id = intval($this->pibase->piVars['wfqbe_edit_subrecord']);
                    elseif ($this->pibase->piVars['wfqbe_id_field'] != '')
                        $id = intval($this->pibase->piVars['wfqbe_id_field']);
                }
                $update_query = "";
                foreach ($insert_data as $col => $val) {
                    $update_query .= $col . '=' . $val . ', ';
                }

                if (strlen($update_query) > 1) {
                    $update_query = substr($update_query, 0, -2) . ' ';
                    $sql = "UPDATE " . $blocks['table'] . " SET " . $update_query . " WHERE " . $blocks['ID_field'] . "=" . $id;
                    if ($this->conf['debugQuery'] == 1)
                        $content .= '<br />Update SQL: ' . $sql;

                    if ($h->Execute($sql) === false)
                        $content .= '<br />ERROR UPDATING: ' . $h->ErrorMsg() . '<br />QUERY: ' . $sql . '<br />';
                    else {
                        $results['inserted'] = true;
                        $results['id'] = $id;
                        unset($results['insert_data']);

                        $query = "SELECT * FROM " . $blocks['table'] . " WHERE " . $blocks['ID_field'] . "=" . $id;
                        $res = $h->Execute($query);
                        if ($res !== false && $res->recordCount() == 1)
                            $results['insert_data'] = $res->FetchRow();
                    }
                }


            } else {
                // INSERT
                $columns = '(';
                $values = '(';
                foreach ($insert_data as $col => $val) {
                    $values .= $val . ",";
                    $columns .= $col . ',';
                }

                if (strlen($columns) > 1) {
                    $columns = substr($columns, 0, -1) . ')';
                    $values = substr($values, 0, -1) . ')';
                    $sql = "INSERT INTO " . $blocks['table'] . " " . $columns . " VALUES " . $values;

                    if ($this->conf['debugQuery'] == 1)
                        $content .= '<br />Insert SQL: ' . $sql;

                    if ($h->Execute($sql) === false)
                        $content .= '<br />ERROR INSERTING: ' . $h->ErrorMsg() . '<br />';
                    else {
                        $results['inserted'] = true;
                        $results['id'] = $h->insert_ID();
                    }
                }
            }
        } else {
            $results['inserted'] = false;
        }


        /*
         * Array = (
         * 		"content" 		=> $content
         * 		"inserted"		=> true/false
         * 		"id"	  		=> $new_id
         * 		"insert_data"	=> $insert_data
         * )
         */
        $results['content'] = $content;
        return $results;
    }


    function checkRequired($data, $blocks)
    {
        $required = array();
        $checked = array();

        if ($this->mode == 'delete')
            return $required;

        // Hook that can be used to pre-process a parameter (from an insert form) before inserting the new row
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['processCheckInsertValues'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['processCheckInsertValues'] as $_classRef) {
                $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                $required['custom_validation'] = $_procObj->custom_validation_insert_values($data, $blocks, $this);
            }
        }

        // This is to check if all the passed fieds contain a value
        foreach ($data as $key => $value) {
            if (!in_array(strtoupper($key), array_keys($blocks['fields']))) {
                unset($data[$key]);
            } else {
                if (is_array($value)) {
                    if (sizeof($value) == 1)
                        $data[$key] = $value[0];
                    else
                        $data[$key] = implode(',', $value);
                }
                if ($blocks['fields'][strtoupper($key)]['required'] == 1 && $data[$key] == "") {
                    $required['required'][] = strtoupper($key);
                    //$content .= '<br />You must fill in the field '.$blocks['fields'][strtoupper($key)]['form']['label'].'.';
                }

                //This is neccessary to check if the entered date is correct or not (Fabian Moser)
                if ($blocks['fields'][strtoupper($key)]['type'] == "datetype" && $data[$key] != '' && !$this->is_dateValid($data[$key], $blocks['fields'][strtoupper($key)])) {
                    $required['required'][] = strtoupper($key);
                }

                //	This is neccessary to check if the entered date is correct or not
                if ($blocks['fields'][strtoupper($key)]['type'] == "calendar" && $data[$key] != '' && !$this->is_dateValid($data[$key], $blocks['fields'][strtoupper($key)])) {
                    $required['required'][] = strtoupper($key);
                }

                unset($blocks['fields'][strtoupper($key)]);
            }
        }

        // This is used to check if there are empty fields required
        foreach ($blocks['fields'] as $key => $value) {
            if ($value['type'] != "" && $value['required'] == 1 && $value['form']['when'][$this->mode] == 1)
                $required['required'][] = $key;
        }

        return $required;
    }


    /**
     * Function used to show the original form after an add_new operation
     */
    function showOriginalForm($h, $results)
    {
        $new_id = $results['id'];
        if (!$new_id || $this->mode == 'delete') {
            // insert_ID() not supported -> it's not possible to set the new value option automatically
            $new_insert = array($this->pibase->piVars['wfqbe_add_new'], $results['deleted_id'], 'unset');
        } else {
            // It retrieves the original form values and substitutes the add_new source with the new inserted value
            $new_insert = array($this->pibase->piVars['wfqbe_add_new'], $new_id);
        }
        $this->pibase->piVars = $this->pibase->piVars['orig'];
        unset($this->pibase->piVars['wfqbe_add_new']);
        $this->main($this->conf, $this->cObj, $this->pibase);
        return $this->do_sGetFormResult($this->pibase->original_row, $h, $new_insert);
    }


    /**
     * This function is used to initialize the piVars array with the values to modify
     */
    function initValues($h, $id = '')
    {
        // First of all I need to retrieve the data from the DB
        $row = $this->getEditingRecord($h, $id);

        // Now I have to associate the values to the editing inputs needed
        // TODO: pay attention to multiple selections
        foreach ($this->blocks['fields'] as $key => $config) {
            if ($this->mode == "delete" || $config['form']['when'][$this->mode] == 1) {
                if ($config['type'] == 'select' || $config['type'] == 'checkbox' || $config['type'] == 'upload') {
                    $values = explode(",", $row[$config['field']]);
                    $this->pibase->piVars[$key] = array();
                    foreach ($values as $v)
                        $this->pibase->piVars[$key][] = $v;
                } elseif ($config['type'] == 'datetype') {
                    //convert the date in a human readable format (added by Fabian Moser)
                    $this->pibase->piVars[$key] = $this->get_dateFromTimestamp($row[$config['field']], $config);
                } elseif ($config['type'] == 'calendar' && $config['form']['convert_timestamp'] == 'si') {
                    //	convert the date in a human readable format
                    $this->pibase->piVars[$key] = $this->get_dateFromTimestamp($row[$config['field']], $config);
                } elseif ($config['type'] == "calendar" && $config['form']['convert_to_date_oracle'] == 'si' && $row[$config['field']] != "") {
                    // convert date from DB format to form format
                    $explodedDate = explode('-', $row[$config['field']]);
                    $this->pibase->piVars[$key] = $this->get_dateFromTimestamp(mktime(0, 0, 0, $explodedDate[1], $explodedDate[2], $explodedDate[0]), $config);
                } else {
                    $this->pibase->piVars[$key] = $row[$config['field']];
                }
            }
        }

        // Hook that can be used to pre-process a value before showing it in the form field
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['preProcessFormValues'])) {
            $_params = array();
            $_params['row'] = $row;
            $_params['h'] = $h;
            $_params['blocks'] = $this->blocks;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['preProcessFormValues'] as $_classRef) {
                $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                $row = $_procObj->pre_process_form_values($_params, $this);
            }
        }

        return $row;
    }


    /**
     * Function used to retrieve the correct label for select fields with static values in the confirmation page
     */
    function searchSelectStaticLabel($field_conf, $v)
    {
        $label = '';
        if (is_array($field_conf)) {
            foreach ($field_conf as $key => $value) {
                if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($key)) {
                    if ($value['value'] == $v && $value['label'] != '') {
                        $label = $value['label'];
                        break;
                    }
                }
            }
        }
        return $label;
    }


    function getEditingRecord($h, $id = '')
    {
        if ($id == '')
            $id = intval($this->pibase->piVars[$this->blocks['ID_field']]);
        $query = "SELECT * FROM " . $this->blocks['table'] . " WHERE " . $this->blocks['ID_field'] . "=" . $id;
        $res = $h->Execute($query);
        if ($res === false)
            return;
        elseif ($res->RecordCount() > 1)
            return;
        $row = $res->FetchRow();
        return $row;
    }


    /**
     * This function is used to replace single quotes with the entity
     */
    function charToEntity($str)
    {
        $str = str_replace("'", "&#039;", $str);
        $str = str_replace('"', "&quot;", $str);
        return $str;
    }

    /**
     * This function is used to replace the entity with the single quotes
     */
    function entityToChar($str)
    {
        $str = str_replace("&#039;", "'", $str);
        $str = str_replace("&quot;", '"', $str);
        return $str;
    }


    //This function convertes a human readable date (e.g.: "02.02.08" or "1.1.2008") in a timestamp (Fabian Moser and Mauro Lorenzutti)
    function get_timestamp($date, $form)
    {
        //list($d, $m, $y) = explode('.', $date);
        $val = $this->parseDate($date, $form['form']['format']);

        if (is_array($val) && $val['error_count'] == 0)
            return mktime($val['hour'], $val['minute'], $val['second'], $val['month'], $val['day'], $val['year']);
        else
            return 0;
    }

    //This function convertes a timestamp in an human readable date (dd.mm.yyyy)  (Fabian Moser and Mauro Lorenzutti)
    function get_dateFromTimestamp($timestamp, $form)
    {
        if ($form['type'] == 'datetype') {
            $format = 'd.m.Y';
        } elseif ($form['type'] == "calendar" && $form['form']['convert_to_date_oracle'] == 'si') {
            $format = str_replace('%M', '%i', $form['form']['format']);
            $format = str_replace('dd', 'd', $format);
            $format = str_replace('mm', 'm', $format);
            $format = str_replace('yy', 'Y', $format);
            $format = str_replace('%', '', $format);
        }

        return date($format, $timestamp);
    }

    //This function checks the correctness of a date given as an human readable date (e.g.: "02.02.08" or "1.1.2008") (Fabian Moser and Mauro Lorenzutti)
    //It checks the semantical correctness too. e.g.: That exists no 29.02.2005 -> return false
    function is_dateValid($date, $form)
    {
        $val = $this->parseDate($date, $form['form']['format']);
        if (is_array($val) && $val['error_count'] == 0) {
            if (!checkdate($val['month'], $val['day'], $val['year'])) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }


    function parseDate($date, $format)
    {
        $array = array();

        $calendarDateFormats = array(
            '%d/%m/%Y',
            '%d-%m-%Y',
            '%d.%m.%Y',
            '%m/%d/%Y',
            '%m-%d-%Y',
            '%m.%d.%Y',
            '%Y-%m-%d',
            'dd-mm-yy',
            'dd.mm.yy',
            'dd/mm/yy',
            'mm-dd-yy',
            'mm.dd.yy',
            'mm/dd/yy'
        );
        $calendarDateTimeFormats = array(
            '%H:%M %d/%m/%Y',
            '%H:%M %d-%m-%Y',
            '%H:%M %d.%m.%Y',
            '%H:%M %m/%d/%Y',
            '%H:%M %m-%d-%Y',
            '%H:%M %m.%d.%Y',
        );

        if (\TYPO3\CMS\Core\Utility\ArrayUtility::inArray($calendarDateTimeFormats, $format)) {
            $temp = explode(' ', $date);
            $temp2 = explode(' ', $format);
            $date = $temp[1];
            $format = $temp2[1];
            $temp = explode(':', $temp[0]);
            $array['hour'] = $temp[0];
            $array['minute'] = $temp[1];
            $array['second'] = 0;
        }

        switch ($format) {
            case 'dd/mm/yy':
                $temp = explode('/', $date);
                $array['day'] = $temp[0];
                $array['month'] = $temp[1];
                $array['year'] = $temp[2];
                break;
            case 'dd-mm-yy':
                $temp = explode('-', $date);
                $array['day'] = $temp[0];
                $array['month'] = $temp[1];
                $array['year'] = $temp[2];
                break;
            case 'dd.mm.yy':
                $temp = explode('.', $date);
                $array['day'] = $temp[0];
                $array['month'] = $temp[1];
                $array['year'] = $temp[2];
                break;
            case 'mm/dd/yy':
                $temp = explode('/', $date);
                $array['day'] = $temp[1];
                $array['month'] = $temp[0];
                $array['year'] = $temp[2];
                break;
            case 'mm.dd.yy':
                $temp = explode('.', $date);
                $array['day'] = $temp[1];
                $array['month'] = $temp[0];
                $array['year'] = $temp[2];
                break;
            case 'mm-dd-yy':
                $temp = explode('-', $date);
                $array['day'] = $temp[1];
                $array['month'] = $temp[0];
                $array['year'] = $temp[2];
                break;
            case '%d/%m/%Y':
                $temp = explode('/', $date);
                $array['day'] = $temp[0];
                $array['month'] = $temp[1];
                $array['year'] = $temp[2];
                break;
            case '%d-%m-%Y':
                $temp = explode('-', $date);
                $array['day'] = $temp[0];
                $array['month'] = $temp[1];
                $array['year'] = $temp[2];
                break;
            case '%m/%d/%Y':
                $temp = explode('/', $date);
                $array['day'] = $temp[1];
                $array['month'] = $temp[0];
                $array['year'] = $temp[2];
                break;
            case '%m-%d-%Y':
                $temp = explode('-', $date);
                $array['day'] = $temp[1];
                $array['month'] = $temp[0];
                $array['year'] = $temp[2];
                break;
            case '%m.%d.%Y':
                $temp = explode('.', $date);
                $array['day'] = $temp[1];
                $array['month'] = $temp[0];
                $array['year'] = $temp[2];
                break;
            case '%Y-%m-%d':
                $temp = explode('-', $date);
                $array['day'] = $temp[2];
                $array['month'] = $temp[1];
                $array['year'] = $temp[0];
                break;
            case '%d.%m.%Y':
            default:
                $temp = explode('.', $date);
                $array['day'] = $temp[0];
                $array['month'] = $temp[1];
                $array['year'] = $temp[2];
                break;
        }

        return $array;
    }


    /**
     *
     * Function used to check if editing ID is into a correct range based on ID_restricting query
     * @param unknown_type $idRestrictQuery
     * @param unknown_type $editing_record
     */
    function checkIDRestricting($h, $idRestrictQuery, $editing_record)
    {
        require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . '/Classes/class.tx_wfqbe_results.php';
        $API = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_wfqbe_results');
        $tsMarkers = $API->getTSMarkers($idRestrictQuery);
        if (is_array($tsMarkers)) {
            foreach ($tsMarkers as $marker) {
                $emptyCase = false;
                if ($this->conf['customRestrictingQuery.'][$this->row['uid'] . '.'][$marker] != "" && (($markerParametri["###" . $marker . "###"] == '' && $this->conf['customRestrictingQuery.'][$this->row['uid'] . '.'][$marker . "."]["overrideIfEmpty"] == 1) || ($markerParametri["###" . $marker . "###"] != '' && $this->conf['customRestrictingQuery.'][$this->row['uid'] . '.'][$marker . "."]["overrideIfNotEmpty"] == 1) || $this->conf['customRestrictingQuery.'][$this->row['uid'] . '.'][$marker . "."]["overrideAlways"] == 1)) {
                    if ($markerParametri["###" . $marker . "###"] == '' && $this->conf['customRestrictingQuery.'][$this->row['uid'] . '.'][$marker . "."]["overrideIfEmpty"] == 1)
                        $emptyCase = true;
                    $confArray = $this->conf["customRestrictingQuery."][$this->row['uid'] . "."][$marker . "."];
                    $confArray = $API->parseTypoScriptConfiguration($confArray, $markerParametri);
                    $func = $this->conf["customRestrictingQuery."][$this->row["uid"]."."][$marker];
                    $markerParametri["###" . $marker . "###"] = "{$this->cObj->$func($confArray)}";
                } elseif ($this->conf['globalcustomRestrictingQuery.'][$marker]) {
                    $confArray = $this->conf["globalcustomRestrictingQuery."][$marker . "."];
                    $confArray = $API->parseTypoScriptConfiguration($confArray, $markerParametri);
                    $func = $this->conf["globalcustomRestrictingQuery."][$marker];
                    $markerParametri["###" . $marker . "###"] = "{$this->cObj->$func($confArray)}";
                } elseif ($this->conf['customGlobalQuery.'][$marker]) {
                    $confArray = $this->conf["customGlobalQuery."][$marker . "."];
                    $confArray = $API->parseTypoScriptConfiguration($confArray, $markerParametri);
                    $func = $this->conf["customGlobalQuery."][$marker];
                    $markerParametri["###" . $marker . "###"] = "{$this->cObj->$func($confArray)}";
                }

                if (!$emptyCase && $this->conf['customRestrictingQuery.'][$this->row['uid'] . '.'][$marker . '.'] != "" && $this->conf['customRestrictingQuery.'][$this->row['uid'] . '.'][$marker . "."]["wfqbe."]['intval'] == 1) {
                    $markerParametri["###" . $marker . "###"] = intval($markerParametri["###" . $marker . "###"]);
                } elseif (!$emptyCase && $this->conf['customRestrictingQuery.'][$this->row['uid'] . '.'][$marker . '.'] != "" && $this->conf['customRestrictingQuery.'][$this->row['uid'] . '.'][$marker . "."]["wfqbe."]['floatval'] == 1) {
                    $markerParametri["###" . $marker . "###"] = floatval($markerParametri["###" . $marker . "###"]);
                }
            }
            //$query = $this->cObj->substituteMarkerArray($query, $markerParametri);
        }

        if (sizeof($markerParametri) > 0) {
            // Hook that can be used to pre-process a parameter (from a search form) before makeing the query
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['processSubstituteSearchParametersClass'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['processSubstituteSearchParametersClass'] as $_classRef) {
                    $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                    $markerParametri = $_procObj->parse_search_markers($markerParametri, $parametri, $this);
                }
            }
            $idRestrictQuery = $this->cObj->substituteMarkerArray($idRestrictQuery, $markerParametri);
        }


        $ris = $h->Execute($idRestrictQuery);

        if (!$ris) {
            if ($this->pibase->beMode == 1) {
                global $LANG;
                $content = $LANG->getLL('not_allowed_idrestricting');
            } else {
                $content = $this->pibase->pi_getLL('not_allowed_idrestricting');
            }
            return array("notAllowed" => 1, "content" => $content);
        }

        $i = 0;
        $auth = false;
        while ($array = $ris->FetchRow()) {
            if ($array[0] == $editing_record[$this->blocks['ID_field']]) {
                $auth = true;
                break;
            }
        }

        if ($auth) {
            return true;
        } else {
            if ($this->pibase->beMode == 1) {
                global $LANG;
                $content = $LANG->getLL('not_allowed_idrestricting');
            } else {
                $content = $this->pibase->pi_getLL('not_allowed_idrestricting');
            }
            return array("notAllowed" => 1, "content" => $content);
        }
    }


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/pi1/class.tx_wfqbe_insert.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/pi1/class.tx_wfqbe_insert.php']);
}
