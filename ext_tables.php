<?php
defined('TYPO3_MODE') or die ('Access denied.');

// Allow editing of BE records on sysfolder pages
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages("tx_rsrq_domain_model_query");

// Add FlexForm
$TCA['tt_content']['types']['list']['subtypes_addlist']['rsrqs_query'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'rsrq_query',
    'FILE:EXT:rsrq/Configuration/FlexForms/FF_Rsrq_Query.xml'
);
