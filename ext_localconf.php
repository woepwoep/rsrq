<?php
defined('TYPO3_MODE') || die('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'RedSeadog.Rsrq',
    'Query',
    [
        'Query' => 'list',
    ],
    // non-cacheable actions
    [
        'Query' => '',
    ],
);
