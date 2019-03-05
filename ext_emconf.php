<?php

########################################################################
# Extension Manager/Repository config file for ext "rsrq".
#
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'Red-Seadog Raw Query',
    'description' => 'This extension is a lightweight version of WFQBE for TYPO3 v9. Features: raw query, extbase code, fluid templates.',
    'category' => 'plugin',
    'version' => '9.5.5',
    'state' => 'alpha',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearcacheonload' => true,
    'author' => 'Ronald Wopereis',
    'author_email' => 'woepwoep@gmail.com',
    'author_company' => 'Red-Seadog',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-7.2.99',
            'typo3' => '9.5.0-9.5.999',
            'vhs' => '5.1.1-5.1.999',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
