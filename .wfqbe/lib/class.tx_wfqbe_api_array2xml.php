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


class tx_wfqbe_api_array2xml
{
    var $extKey = 'tx_wfqbe_api';    // The extension key.

    var $cObj;
    var $conf;

    var $errore;

    /**
     * Main function
     */
    function main($conf, $cObj)
    {
        $this->conf = $conf;
        $this->cObj = $cObj;
        //$this->logged = $GLOBALS["TSFE"]->fe_user->user;
        return;
    }

    /**
     * Funzione per la codifica di un array in una stringa
     */
    function array2xml($data)
    {
        global $TYPO3_CONF_VARS;
        $config = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['wfqbe']);

        if ($config['mode'] == 'xml') {
            $line = "";

            foreach ($data as $key => $value) {

                if (is_numeric($key) && is_array($value)) {
                    $key = "content number='" . $key . "'";
                    $key2 = "content";
                } elseif (is_numeric($key)) {
                    $key = "item number='" . $key . "'";
                    $key2 = "item";
                } else {
                    $key2 = $key;
                }
                if (is_array($value)) {
                    $value = $this->array2xml($value);
                }
                if ($value != "")
                    $line = $line . "<$key>" . $value . "</$key2>";
            }

            //$line = substr($line, 1);
            return $line;
        } else {
            return addslashes(serialize($data));
        }
    }
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/lib/class.tx_wfqbe_api_array2xml.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/lib/class.tx_wfqbe_api_array2xml.php']);
}


