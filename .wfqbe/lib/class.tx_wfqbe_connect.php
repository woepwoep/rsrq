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

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility:: extPath('adodb') . 'adodb/adodb.inc.php');

class tx_wfqbe_connect
{
    var $extKey = 'wfqbe'; // The extension key.

    function connect($where)
    {
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_wfqbe_query', $where . 'tx_wfqbe_query.hidden!=1 AND tx_wfqbe_query.deleted!=1');
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
            if (!isset($GLOBALS['WFQBE'][$row['credentials']])) {
                $h = $this->connectNow($row['credentials'], $row['dbname']);
                $h->SetFetchMode(ADODB_FETCH_BOTH);
                $GLOBALS['WFQBE'][$row['credentials']] = $h;
            } else {
                $h = $GLOBALS['WFQBE'][$row['credentials']];
            }
            if ($h !== false)
                return array("conn" => $h, "row" => $row);
            else
                return false;
        }
        return false;
    }


    function connectNow($credentials = 0, $dbname = '')
    {

        if ($credentials == 0) {
            // Local TYPO3 DB
            $h = NewADOConnection("mysqli"); // TODO: correct this for DBAL compatibility
            $resultConnection = $h->Connect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password, TYPO3_db);
            if ($resultConnection) {
                global $TYPO3_CONF_VARS;
                if ($TYPO3_CONF_VARS['SYS']['setDBinit'] != '') {
                    //$h->query($TYPO3_CONF_VARS['SYS']['setDBinit']);
                    $statements = explode(';', $TYPO3_CONF_VARS['SYS']['setDBinit']);
                    foreach ($statements as $statement) {
                        $h->query($statement);
                    }
                }
                return $h;
            } else {
                $content = $h->ErrorMsg() . "<br /><br />";
                echo($content);
                return false;
            }
        } else {
            $res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('host,dbms,username,passw,conn_type,setdbinit,dbname,type,connection_uri,connection_localconf', 'tx_wfqbe_credentials', 'tx_wfqbe_credentials.uid=' . intval($credentials), '', '', '');
            while ($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2)) {
                if ($row2['type'] == 'localconf') {
                    global $TYPO3_CONF_VARS;
                    // Overwrites standard configuration array with values from localconf. Useful to avoid rewriting connection calls
                    if ($row2['connection_localconf'] != '' && is_array($TYPO3_CONF_VARS['EXTCONF']['wfqbe']['__CONNECTIONS']) && is_array($TYPO3_CONF_VARS['EXTCONF']['wfqbe']['__CONNECTIONS'][$row2['connection_localconf']])) {
                        foreach ($TYPO3_CONF_VARS['EXTCONF']['wfqbe']['__CONNECTIONS'][$row2['connection_localconf']] as $key => $value)
                            $row2[$key] = $value;
                    }
                }

                if ($row2['type'] == 'uri') {
                    // Alternative uri connection type
                    $h = NewADOConnection($row2['connection_uri']);
                    $resultConnection = true;

                } else {
                    // Standard connection type
                    if ($row2['dbname'] != '')
                        $dbname = $row2['dbname'];

                    $h = NewADOConnection($row2['dbms']); //starts a new connection
                    if ($row2['dbms'] == 'access') {
                        $dsn = "Driver={Microsoft Access Driver (*.mdb)};Dbq=" . $row2['host'] . ";Uid=" . $row2['username'] . ";Pwd=" . $row2['passw'] . ";";
                        if ($row2['conn_type'] == "PConnect")
                            $resultConnection = $h->PConnect($dsn);
                        elseif ($row2['conn_type'] == "NConnect")
                            $resultConnection = $h->NConnect($dsn);
                        else
                            $resultConnection = $h->Connect($dsn);
                    } else {
                        if ($row2['conn_type'] == "PConnect")
                            $resultConnection = $h->PConnect($row2['host'], $row2['username'], $row2['passw'], $dbname);
                        elseif ($row2['conn_type'] == "NConnect")
                            $resultConnection = $h->NConnect($row2['host'], $row2['username'], $row2['passw'], $dbname);
                        else {
                            $resultConnection = $h->Connect($row2['host'], $row2['username'], $row2['passw'], '' . $dbname);
                        }
                    }
                }

                if ($resultConnection) {
                    if ($row2['setdbinit'] != '') {
                        //$h->query($row2['setdbinit']);
                        $statements = explode(';', $row2['setdbinit']);
                        foreach ($statements as $statement) {
                            $h->query($statement);
                        }
                    }
                    return $h;
                } else {
                    $content = $h->ErrorMsg() . "<br /><br />";
                    echo($content);
                    return false;
                }
            }
        }

        return false;
    }


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/lib/class.tx_wfqbe_connect.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/lib/class.tx_wfqbe_connect.php']);
}

