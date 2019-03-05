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


class tx_wfqbe_utils
{
    var $extKey = 'wfqbe';    // The extension key.


    /**
     * This function is used to add a list of hidden inputs to pass values from a page to another
     * @param array vars: array to convert in list of hidden inputs
     * @return string list of hidden inputs
     */
    function getHiddenFields($vars, $sub = '', $exclude = -1)
    {
        $html = '';
        if ($sub != '')
            $sub = '[' . $sub . ']';
        foreach ($vars as $key => $value) {
            if ($key . "" != $exclude) {
                if (is_array($value)) {
                    foreach ($value as $k => $v)
                        $html .= '<input type="hidden" name="tx_wfqbe_pi1' . $sub . '[' . $key . '][' . $k . ']" value="' . $v . '" />';
                } else {
                    $html .= '<input type="hidden" name="tx_wfqbe_pi1' . $sub . '[' . $key . ']" value="' . $value . '" />';
                }
            }
        }
        return $html;
    }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/lib/class.tx_wfqbe_utils.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/lib/class.tx_wfqbe_utils.php']);
}


