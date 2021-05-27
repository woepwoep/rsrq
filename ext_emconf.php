<?php

########################################################################
# Extension Manager/Repository config file for ext "rsrq".
#
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF['rsrq'] = [
    'title' => 'DB Integration for TYPO3 v10',
    'description' =>
        'This extension is based on the DB Integration (rsrq) extension using TYPO3 v10 standards.',
    'category' => 'plugin',
    'version' => '10.4.16',
    'state' => 'beta',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearcacheonload' => true,
    'author' => 'Ronald Wopereis',
    'author_email' => 'woepwoep@gmail.com',
    'author_company' => 'Red-Seadog',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-7.3.99',
            'typo3' => '10.4.0-10.9.99',
            'vhs' => '6.0.5-6.9.9'
        ],
        'conflicts' => [],
        'suggests' => []
    ]
];
