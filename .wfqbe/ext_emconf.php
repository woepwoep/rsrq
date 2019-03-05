<?php

########################################################################
# Extension Manager/Repository config file for ext "wfqbe".
#
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
    'title' => 'DB Integration',
    'description' => 'This extension allows to generate queries (with a little sql knowledge), search forms and insert forms to generic databases through a wizard. The results visualization is template-based and fully configurable via TS. The extension uses ADOdb.',
    'category' => 'plugin',
    'version' => '7.6.4',
    'state' => 'beta',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearcacheonload' => true,
    'author' => 'Mauro Lorenzutti',
    'author_email' => 'support@webformat.com',
    'author_company' => 'WEBFORMAT Srl',
    'constraints' =>
        array(
            'depends' =>
                array(
                    'adodb' => '',
                    'php' => '5.5.0-7.0.0',
                    'typo3' => '7.6.0-7.6.999',
                ),
            'conflicts' =>
                array(),
            'suggests' =>
                array(),
        ),
);
