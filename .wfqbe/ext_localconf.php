<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
    options.saveDocNew.tx_wfqbe_credentials=1
');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
    options.saveDocNew.tx_wfqbe_query=1
');

## Extending TypoScript from static template uid=43 to set up userdefined tag:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript($_EXTKEY, 'editorcfg', '
    tt_content.CSS_editor.ch.tx_wfqbe_pi1 = < plugin.tx_wfqbe_pi1.CSS_editor
', 43);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'pi1/class.tx_wfqbe_pi1.php', '_pi1', 'list_type', 0);


$TYPO3_CONF_VARS['BE']['AJAX']['tx_wfqbe_mod1_ajax::fieldTypeHelp'] = 'typo3conf/ext/wfqbe/mod1/class.tx_wfqbe_mod1_ajax.php:tx_wfqbe_mod1_ajax->ajaxFieldTypeHelp';


