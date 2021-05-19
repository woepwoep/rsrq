mod.wizards {
    newContentElement.wizardItems {
        plugins {
            elements {
                plugins_tx_rsrq_query {
                    icon = EXT:rsrq/Resources/Public/Icons/ce_wiz.gif
                    title = LLL:EXT:rsrq/Resources/Private/Language/locallang.xml:query_title
                    description = LLL:EXT:rsrq/Resources/Private/Language/locallang.xml:query_plus_wiz_description
                    tt_content_defValues {
                        CType = list
                        list_type = rsrq_query
                    }
                }
            }
        }
    }
}
