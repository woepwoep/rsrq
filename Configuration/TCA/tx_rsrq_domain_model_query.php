<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:rsrq/Resources/Private/Language/tx_rsrq_domain_model_query.xlf:title',
        'label' => 'query',
        'iconfile' => 'EXT:rsrq/Resources/Public/Icons/Query.svg'
    ],
    'columns' => [
        'title' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:rsrq/Resources/Private/Language/tx_rsrq_domain_model_query.xlf:title',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ]
        ],
        'description' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:rsrq/Resources/Private/Language/tx_rsrq_domain_model_query.xlf:description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ]
        ],
        'query' => [
            'label' => 'LLL:EXT:rsrq/Resources/Private/Language/tx_rsrq_domain_model_query.xlf:query',
            'config' => [
                'type' => 'text',
                'eval' => 'trim'
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'query'
        ],
    ],
];
