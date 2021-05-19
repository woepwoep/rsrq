<?php
defined('TYPO3_MODE') or die('Access denied.');

if (TYPO3_MODE === 'BE') {
    $TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"][
        "tx_rsrq_query_wizicon"
    ] =
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('rsrq') .
        'Configuration/TypoScript/class.tx_rsrq_query_wizicon.php';

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'RedSeadog.rsrq',
        'web', // Main area
        'tx_rsrq_m1', // Name of the module
        '', // Position of the module
        [
            // Allowed controller action combinations
            'Backend' => 'list'
        ],
        [
            // Additional configuration
            'access' => 'user,group',
            'icon' => 'EXT:rsrq/Resources/Public/Icons/mod1_moduleicon.gif',
            'labels' => 'LLL:EXT:rsrq/Resources/Private/Language/Backend.xlf'
        ]
    );
}
