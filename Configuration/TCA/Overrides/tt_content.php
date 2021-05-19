<?php
defined('TYPO3_MODE') or die();

/**
 * Plugins
 */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'wfqbe',
    'Piquery',
    'LLL:EXT:wfqbe/Resources/Private/Language/Plugin.xlf:piquery.title'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'wfqbe',
    'Picud',
    'LLL:EXT:wfqbe/Resources/Private/Language/Plugin.xlf:picud.title'
);

/**
 * Remove unused fields
 */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][
    'wfqbe_piquery'
] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][
    'wfqbe_picud'
] = 'layout,select_key,pages,recursive';

/**
 * Add Flexform for query plugin
 */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][
    'wfqbe_piquery'
] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'wfqbe_piquery',
    'FILE:EXT:wfqbe/Configuration/FlexForms/Flexform_query.xml'
);

/**
 * Add Flexform for cud plugin
 */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][
    'wfqbe_picud'
] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'wfqbe_picud',
    'FILE:EXT:wfqbe/Configuration/FlexForms/Flexform_cud.xml'
);

/**
 * Default TypoScript
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'wfqbe',
    'Configuration/TypoScript',
    'DB Integration for TYPO3 v10'
);

