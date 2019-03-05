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

class tx_wfqbe_tca_credentials_connection_localconf_preprocessing
{

    public function main(&$params, &$pObj)
    {

        global $TYPO3_CONF_VARS;

        if (is_array($TYPO3_CONF_VARS['EXTCONF']['wfqbe']['__CONNECTIONS'])) {
            foreach ($TYPO3_CONF_VARS['EXTCONF']['wfqbe']['__CONNECTIONS'] as $key => $value) {
                $params['items'][] = array($value['name'], $key);
            }
        }

    }

}