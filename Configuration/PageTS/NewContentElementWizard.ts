mod.wizards {
    newContentElement.wizardItems {
        plugins {
            elements {
                plugins_tx_wfqbe_query {
                    icon = EXT:wfqbe/Resources/Public/Icons/ce_wiz.gif
                    title = LLL:EXT:wfqbe/Resources/Private/Language/locallang.xml:query_title
                    description = LLL:EXT:wfqbe/Resources/Private/Language/locallang.xml:query_plus_wiz_description
                    tt_content_defValues {
                        CType = list
                        list_type = wfqbe_query
                    }
                }
            }
        }
    }
}
