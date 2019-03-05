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

class tx_wfqbe_mod1_ajax
{

    function tx_wfqbe_mod1_ajax()
    {
    }

    public function ajaxFieldTypeHelp($params, &$ajaxObj)
    {
        //$this->init();
        $LANG = $GLOBALS['LANG'];
        $LANG->includeLLFile('EXT:wfqbe/mod1/locallang.xml');

        // the content is an array that can be set through $key / $value pairs as parameter
        $ajaxObj->addContent('help', $LANG->getLL('help_' . \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('field')) . '<br />');
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/mod1/class.tx_wfqbe_mod1_ajax.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/mod1/class.tx_wfqbe_mod1_ajax.php']);
}


