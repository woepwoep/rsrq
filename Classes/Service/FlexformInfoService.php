<?php
namespace RedSeadog\Rsrq\Service;

use TYPO3\CMS\Core\Service\FlexformService;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 *  FlexformInfoService
 */
class FlexformInfoService extends
    \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    protected $ffdata;

    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
        );
        $configurationManager = $objectManager->get(
            'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager'
        );

        // Get the data object (contains the tt_content fields)
        $cObj = $configurationManager->getContentObject();

        // Retrieve flexform values
        $flexformService = new FlexformService();
        $this->ffdata = $flexformService->convertFlexFormContentToArray(
            $cObj->data['pi_flexform']
        );
    }

    public function getData()
    {
        // Return the values
        return $this->ffdata;
    }

    public function mergeFieldTypes($fieldlist = null)
    {
        if (empty($fieldlist)) {
            $fieldlist = explode(',', $this->getFieldlist());
        }

        $fieldtypes = $this->getFieldtypes();
        $validators = $this->getValidators();
        $valutas = $this->getValutas();
        $linkfields = $this->getLinkfields();

        $columnNames = array();
        foreach ($fieldlist as $field) {
            // name is important
            $columnNames[$field]['name'] = $field;

            // add select info from flexform
            $parameter = 'typesection';
            $columnNames[$field]['type'] = 'TEXT';
            if (is_array($fieldtypes)) {
                foreach ($fieldtypes as $key => $value) {
                    if (
                        !strcasecmp(
                            $columnNames[$field]['name'],
                            $value[$parameter]['name']
                        )
                    ) {
                        $columnNames[$field]['type'] =
                            $value[$parameter]['veldtype'];
                        $columnNames[$field]['nodisplay'] =
                            $value[$parameter]['nodisplay'];
                        $columnNames[$field]['argument'] =
                            $value[$parameter]['argument'];
                        $columnNames[$field]['additionalargument'] =
                            $value[$parameter]['additionalargument'];
                        $columnNames[$field]['targetfield'] =
                            $value[$parameter]['targetfield'];

                        // if type = radio or type == selectarray
                        if (
                            !strcmp('radio', $columnNames[$field]['type']) ||
                            !strcmp('selectarray', $columnNames[$field]['type'])
                        ) {
                            $TSparserObject = GeneralUtility::makeInstance(
                                \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class
                            );
                            $TSparserObject->parse(
                                $value[$parameter]['optionlist']
                            );
                            $options = $TSparserObject->setup;
                            $columnNames[$field]['select'] = $options;
                        }

                        // if type = select then arguments holds the query to fetch the foreign table rows
                        if (!strcmp('select', $columnNames[$field]['type'])) {
                            $columnNames[$field]['select'] = $this->getOptionList($value[$parameter]['argument']);
                        }
                    }
                };
            }

            // add validation info from flexform
            $parameter = 'validation';
            $columnNames[$field][$parameter] = '';
            if (is_array($validators)) {
                foreach ($validators as $key => $value) {
                    if (
                        !strcasecmp(
                            $columnNames[$field]['name'],
                            $value[$parameter]['name']
                        )
                    ) {
                        $columnNames[$field][$parameter] =
                            $value[$parameter]['validator'];
                    }
                };
            }

            // <<<EW>>> add valuta currency info from flexform
            $parameter = 'valuta';
            $columnNames[$field][$parameter] = '';
            if (is_array($valutas)) {
                foreach ($valuta as $key => $value) {
                    if (
                        !strcasecmp(
                            $columnNames[$field]['name'],
                            $value[$parameter]['name']
                        )
                    ) {
                        $columnNames[$field][$parameter] =
                            $value[$parameter]['valuta'];
                    }
                };
            }

            // add linkfields info from flexform
            $parameter = 'linksection';
            $columnNames[$field]['relationField'] = '';
            $columnNames[$field]['childPage'] = '';
            if (is_array($linkfields)) {
                foreach ($linkfields as $key => $value) {
                    if (
                        !strcasecmp(
                            $columnNames[$field]['name'],
                            $value[$parameter]['linkField']
                        )
                    ) {
                        $columnNames[$field]['relationField'] =
                            $value[$parameter]['relationField'];
                        $columnNames[$field]['childPage'] =
                            $value[$parameter]['childPage'];
                    }
                };
            }
        }

        return $columnNames;
    }

    public function getChartFieldList()
	{
        // add select info from flexform
        $chartfields = $this->getChartfields();
        $parameter = 'typesection';

        $fieldList = array();
        if (is_array($chartfields)) {
            foreach ($chartfields as $key => $value) {
                $name = $value[$parameter]['name'];
				$type = $value[$parameter]['chartType'];
				$color = $value[$parameter]['chartColor'];
                $fieldList[$name]['name'] = $name;
                $fieldList[$name]['chartType'] = $type;
                $fieldList[$name]['chartColor'] = $color;
            };
        }
        // DebugUtility::debug($fieldList,'fieldList in getChartFieldList');
        return $fieldList;
	}

    public function getFilterFieldList()
    {
        // add select info from flexform
        $filterfields = $this->getFilterfields();
        $parameter = 'typesection';

        $fieldList = array();
        if (is_array($filterfields)) {
            foreach ($filterfields as $key => $value) {
                $name = $value[$parameter]['filterFieldName'];
				$type = $value[$parameter]['filterFieldType'];
				$where = $value[$parameter]['filterFieldWhere'];
				$options = $value[$parameter]['optionlist'];
                $fieldList[$name]['filterFieldName'] = $name;
                $fieldList[$name]['filterFieldType'] = $type;
                $fieldList[$name]['filterFieldWhere'] = $where;

			    switch($type) {
				default:
					$fieldList[$name]['optionlist'] = '';
					break;
				case 'select':
					$fieldList[$name]['optionlist'] = $this->getOptionList($value[$parameter]['optionlist']);
					break;
				}
            };
        }
        // DebugUtility::debug($fieldList,'fieldList in getFilterFieldList');
        return $fieldList;
    }

    /**
     * retrieve the query from the flexform data array
     * (query is required in all QUERY actions)
     */
    public function getQuery()
    {
        return $this->getRequiredElement('query');
    }

    public function andWhere($rsrq_names)
    {
        $whereClause = ' 1=1 ';

        // DebugUtility::debug($rsrq_names,'rsrq_names in andWhere');
        if (!is_array($rsrq_names)) {
            return $whereClause;
        }

        $fieldList = $this->getFilterFieldList();
        // DebugUtility::debug($fieldList,'fieldList in andWhere');
        if (!is_array($fieldList)) {
            return $whereClause;
        }

        // without passed-on value we don't need to filter the search. therefore only the rsrq_names are processed
        foreach ($rsrq_names as $key => $value) {
            // skip if no filtervalue has been entered
            if (!strlen($value)) {
                continue;
            }

            // select the flexform info (filter tab)
            $temp = $fieldList[$key];
            if (!strcmp($key, $temp['filterFieldName'])) {
                // replace the ### with empty string
                $search = '###' . $key . '###';
                $replace = $value;
                $subject = $temp['filterFieldWhere'];
                $and = str_replace($search, $replace, $subject);

                // add to the where-clause
                $whereClause .= ' AND (' . $and . ')';
            }
        }

        // DebugUtility::debug($whereClause,'whereClause in addwhere');
        return $whereClause;
    }

	// getSortObject
	public function getSortObject($rsrq_names)
	{
        // DebugUtility::debug($rsrq_names,'rsrq_names in getSortObject');

		// sortField and sortOrder in flexform
        $sortField = $this->getOptionalElement('sortField');
        $sortOrder = $this->getOptionalElement('sortOrder');
        // DebugUtility::debug($sortField.' '.$sortOrder,'sortField and sortOrder in flexform');

		// sortField and sortOrder overrule
		if ($rsrq_names['sortField']) $sortField = $rsrq_names['sortField'];
		if ($rsrq_names['sortOrder']) $sortOrder = $rsrq_names['sortOrder'];
        // DebugUtility::debug($sortField.' '.$sortOrder,'sortField and sortOrder overrule');

		$statement = '';
		if ($sortField) $statement .= 'ORDER BY '.$this->backquoted($sortField);
		if ($statement && $sortOrder) $statement .= ' '.$sortOrder;

		// change order for fluid
		if ($rsrq_names['sortOrder']) $sortOrder = $this->inverseSortOrder($rsrq_names['sortOrder']);

		// return values
		$sortObject = [
			'sortField' => $sortField,
			'sortOrder' => $sortOrder,
			'statement' => $statement,
		];
        // DebugUtility::debug($sortObject,'sortObject in getSortObject');
		return $sortObject;
	}

    /**
     * retrieve the targetTable from the flexform data array
     * (targetTable is required in all CRUD actions)
     */
    public function getTargetTable()
    {
        return $this->getRequiredElement('targetTable');
    }

    /**
     * retrieve the keyField from the flexform data array
     * (keyField is required in all CRUD actions)
     */
    public function getKeyField()
    {
        return $this->getRequiredElement('identifiers');
    }

    /**
     * retrieve the fieldlist from the flexform data array
     * (fieldlist is required in all CRUD actions)
     */
    public function getFieldlist()
    {
        return $this->getOptionalElement('fieldlist');
    }

    /**
     * retrieve the fieldtypes from the flexform data array
     * (fieldtypes are optional in all CRUD actions)
     */
    public function getFieldtypes()
    {
        // introduce the fieldtype array
        /**
         * no longer TypoScript
         *	  $TSparserObject = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
         *	  $TSparserObject->parse($this->getOptionalElement('fieldtypes'));
         *	  $fieldtypes = $TSparserObject->setup;
         */

        return $this->getOptionalElement('fieldtypes');
    }

    /**
     * retrieve the validators from the flexform data array
     * (validators is optional in all CRUD actions)
     */
    public function getValidators()
    {
        return $this->getOptionalElement('field');
    }

    //<<<EW>>>
    /**
     * retrieve the validators from the flexform data array
     * (validators is optional in all CRUD actions)
     */
    public function getValutas()
    {
        return $this->getOptionalElement('valuta');
    }

    /**
     * retrieve the templateFile from the flexform data array
     * (templateFile is optional in all CRUD actions)
     */
    public function getTemplateFile()
    {
        $templateFile = $this->getOptionalElement('templateFile');
        if (!empty($templateFile)) {
            $templateFile = GeneralUtility::getFileAbsFilename($templateFile);
        }
        return $templateFile;
    }

    /**
     * retrieve a required array element $key from the flexform data array
     */
    protected function getRequiredElement($key)
    {
        $value = $this->getOptionalElement($key);

        if (empty($value)) {
            DebugUtility::debug(
                $this->ffdata,
                'Required key ' . $key . ' is empty or notfound in flexform.'
            );
            exit(1);
        }

        return $value;
    }

    /**
     * retrieve an array element $key from the flexform data array
     * default value is returned if array element is not found
     */
    protected function getOptionalElement($key, $default = '')
    {
        $value = $this->ffdata[$key];

        if (empty($value)) {
            $value = $default;
        }

        return $value;
    }

    public function getLinkfields()
    {
        return $this->getOptionalElement('linkfields');
    }

    public function getChartfields()
    {
        return $this->getOptionalElement('chartFields');
    }

    public function getFilterfields()
    {
        return $this->getOptionalElement('filterFields');
    }

	protected function getOptionList($statement)
	{
        // DebugUtility::debug($statement,'statement in getOptionList');
        $service = new SqlService($statement);
        $rows = $service->getRows();
        // DebugUtility::debug($rows,'rows in getOptionList');
        $fieldNameArray = array_keys($rows[0]);
        $rowArr = array();
        foreach ($rows as $key => $value) {
            $rowArr[$value[$fieldNameArray[0]]] = $value[$fieldNameArray[1]];
        }
                            
		return $rowArr;
	}

	protected function inverseSortOrder($sortOrder)
	{
		$inverse='';
		switch($sortOrder) {
		case 'DESC':
			$inverse = 'ASC';
			break;
		case 'ASC':
		default:
			$inverse = 'DESC';
			break;
		}
		return $inverse;
	}

	/**
	 *	backquoted - surround $text with single backquote
	 */
	protected function backquoted($text)
	{
		$quote = "`";
		return $quote.$text.$quote;
	}
}

