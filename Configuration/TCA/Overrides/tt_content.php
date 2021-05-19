<?php
defined('TYPO3_MODE') or die();

/**
 * Plugins
 */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'rsrq',
    'Piquery',
    'LLL:EXT:rsrq/Resources/Private/Language/Plugin.xlf:piquery.title'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'rsrq',
    'Picud',
    'LLL:EXT:rsrq/Resources/Private/Language/Plugin.xlf:picud.title'
);

/**
 * Remove unused fields
 */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][
    'rsrq_piquery'
] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][
    'rsrq_picud'
] = 'layout,select_key,pages,recursive';

/**
 * Add Flexform for query plugin
 */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][
    'rsrq_piquery'
] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'rsrq_piquery',
    'FILE:EXT:rsrq/Configuration/FlexForms/Flexform_query.xml'
);

/**
 * Add Flexform for cud plugin
 */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][
    'rsrq_picud'
] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'rsrq_picud',
    'FILE:EXT:rsrq/Configuration/FlexForms/Flexform_cud.xml'
);

/**
 * Default TypoScript
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'rsrq',
    'Configuration/TypoScript',
    'DB Integration for TYPO3 v10'
);

