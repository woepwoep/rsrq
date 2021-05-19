<?php
defined('TYPO3_MODE') or die('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'RedSeadog.wfqbe',
    'Piquery',
    [
        'Query' => 'list,detailForm,filter'
    ],
    // non-cacheable actions
    [
        'Query' => 'list,detailForm,filter'
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'RedSeadog.wfqbe',
    'Picud',
    [
        'Cud' => 'show,addForm,add,updateForm,update,deleteForm,delete'
    ],
    // non-cacheable actions
    [
        'Cud' => 'show,addForm,add,updateForm,update,deleteForm,delete'
    ]
);
