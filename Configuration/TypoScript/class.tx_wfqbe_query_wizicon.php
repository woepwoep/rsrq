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
 * Class that adds the wizard icon.
 */
class tx_wfqbe_query_wizicon
{
    /**
     * Processing the wizard items array
     *
     * @param	array		$wizardItems: The wizard items
     * @return	Modified array with wizard items
     */
    function proc($wizardItems)
    {
        global $LANG;

        $LL = $this->includeLocalLang();

        $wizardItems['plugins_tx_wfqbe_query'] = array(
            'icon' => 'EXT:wfqbe/Resources/Public/Icons/ce_wiz.gif',
            'title' => $LANG->getLLL('query_title', $LL),
            'description' => $LANG->getLLL('query_plus_wiz_description', $LL),
            'params' =>
                '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=wfqbe_query'
        );

        return $wizardItems;
    }

    /**
     * Reads the [extDir]/locallang.xlf and returns the \$LOCAL_LANG array found in that file.
     *
     * @return	The array with language labels
     */
    function includeLocalLang()
    {
        global $LANG;

        $LOCAL_LANG = $LANG->includeLLFile('EXT:wfqbe/locallang.xlf', false);
        return $LOCAL_LANG;
    }
}

