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

require_once('class.tx_wfqbe_api_xml2data_structure.php');

class tx_wfqbe_api_xml2array
{
    var $extKey = 'tx_wfqbe_api';    // The extension key.

    var $cObj;
    var $conf;

    var $parser;
    var $node_stack = array();


    /**
     * Main function
     */
    function main($conf, $cObj)
    {
        $this->conf = $conf;
        $this->cObj = $cObj;
        return;
    }


    /**
     * If a string is passed in, parse it right away.
     */
    function xml2array($xmlstring = "")
    {

        if ($xmlstring) {
            if (strpos($xmlstring, '<contentwfqbe>') !== false || strpos($xmlstring, '<searchwfqbe>') !== false || strpos($xmlstring, '<insertwfqbe>') !== false) {
                $API = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_api_xml2data_structure");
                $data_structure = $API->parse($xmlstring);
                return $this->convert($data_structure);
            } else {
                return unserialize(stripslashes($xmlstring));
            }
        }
        return true;
    }


    /**
     * Converts the structure in an associative array
     */
    function convert($struttura)
    {
        if (sizeof($struttura["_ELEMENTS"]) == 0) {
            return trim($struttura["_DATA"]);
        } else {
            foreach ($struttura["_ELEMENTS"] as $key => $value) {
                if (($value["_NAME"] == "item" && $value["number"] != "") || ($value["_NAME"] == "content" && $value["number"] != ""))
                    $data[$value["number"]] = $this->convert($value);
                else
                    $data[$value["_NAME"]] = $this->convert($value);

            }
        }
        return $data;
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/lib/class.tx_wfqbe_api_xml2array.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/lib/class.tx_wfqbe_api_xml2array.php']);
}


