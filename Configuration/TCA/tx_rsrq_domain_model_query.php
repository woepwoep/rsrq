<?php
return [
   'ctrl' => [
      'title' => 'LLL:EXT:rsrq/Resources/Private/Language/locallang_db.xlf:tx_rsrq_domain_model_query',
      'label' => 'query',
      'iconfile' => 'EXT:rsrq/Resources/Public/Icons/Product.svg'
   ],
   'columns' => [
      'query' => [
         'label' => 'LLL:EXT:rsrq/Resources/Private/Language/locallang_db.xlf:tx_rsrq_domain_model_query.item_description',
         'config' => [
            'type' => 'text',
            'eval' => 'trim'
         ],
      ],
   ],
   'types' => [
      '0' => ['showitem' => 'query'],
   ],
];
