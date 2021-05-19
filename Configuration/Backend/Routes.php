<?php
/*
 *  Copyright notice
 *
 *  (c) 2006-2017 WEBFORMAT
 *
 *  All rights reserved
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Definitions for routes provided by EXT:rsrq
 */
return [
    'xMOD_tx_rsrq_query_insertwiz' => [
        'path' => '/rsrq/tx_rsrq_query_insert/tx_rsrq_insertform_generator/',
        'target' => tx_rsrq_query_insertwiz::class . '::main'
    ],

    'xMOD_tx_rsrq_query_querywiz' => [
        'path' => '/rsrq/tx_rsrq_query_query/tx_rsrq_queryform_generator/',
        'target' => tx_rsrq_tx_rsrq_query_querywiz::class . '::main'
    ],

    'xMOD_tx_rsrq_query_searchwiz' => [
        'path' => '/rsrq/tx_rsrq_query_search/tx_rsrq_searchform_generator/',
        'target' => tx_rsrq_tx_rsrq_query_searchwiz::class . '::main'
    ]
];
