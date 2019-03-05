<?php
defined('TYPO3_MODE') or die ('Access denied.');

// Allow editing of BE records on sysfolder pages
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages("tx_rsrq_domain_model_query");
