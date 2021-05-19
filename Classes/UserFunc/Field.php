<?php
namespace RedSeadog\Rsrq\UserFunc;

/*
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Core\Environment;
use RedSeadog\Rsrq\Service\SqlService;
use RedSeadog\Rsrq\Service\FlexformInfoService;

/**
 * QueryController
 *
 * @author Ronald Wopereis <woepwoep@gmail.com>
 */
class Field
{
    /**
     * @param array $config configuration array
     * @param $parentObject parent object
     * @return array
     */
    public function getColumnNames(array &$config, &$parentObject)
    {
        $targetTable = $config['row']['targetTable'][0];
        $rows = $this->showColumns($targetTable);
        $fieldList = array();
        $options = [];
        foreach ($rows as $row) {
            //$options[] = [$value,$value];
            $options[] = [$row['Field'], $row['Field']];
        }
        //DebugUtility::debug($options);exit(1);
        $config['items'] = $options;
    }

    /**
     * PopulateTargetTable - used to populate 'select targetTable' in the Cud/database.xml flexform
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return array
     */
    public function populateTargetTable(array &$config, &$parentObject)
    {
        $options = [];

        /** $label = Locale::translate("please_select", \RedSeadog\rsrq\Configuration\ExtensionConfiguration::EXTENSION_KEY); **/
        $label = "Select targetTable";
        $options[] = [0 => $label, 1 => ""];

        $sqlService = new SqlService('SHOW TABLES');
        $tables = $sqlService->getRows();

        foreach ($tables as $_table) {
            $tableName = reset($_table);
            $options[] = [0 => $tableName, 1 => $tableName];
        }

        $config["items"] = $options;

        return $config;
    }

    /**
     * Populate flexform fieldlist
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return array
     */
    public function populateFieldNames(array &$config, &$parentObject)
    {
        $options = [];
        $pi_flexform = $config['flexParentDatabaseRow']['pi_flexform'];
        $fields = $pi_flexform['data']['database']['lDEF']['fieldlist']['vDEF'];

        $fieldlist = [];
        if (is_string($fields) && strpos($fields, ',')) {
            $fieldlist = explode(',', $fields);
            foreach ($fieldlist as $_field) {
                $fieldName = $_field;
                $options[] = [0 => $fieldName, 1 => $fieldName];
            }
        }

        $config["items"] = $options;

        return $config;
    }

    /**
     * Display a rendered template from a
     * given path by parameters -> template
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return array
     */
    public function displayTemplate(array &$config, &$parentObject)
    {
        $parameters = $config["parameters"];
        $template = isset($parameters["template"])
            ? $parameters["template"]
            : null;

        if (!is_null($template)) {
            $templateFile = GeneralUtility::getFileAbsFileName($template);
            if (file_exists($templateFile)) {
                /* @var StandaloneView $view */
                //$view = $this->objectManager->get(StandaloneView::class);
                //$view->setTemplatePathAndFilename($templateFile);
                //$view->assignMultiple($parameters);
                //$view->assign("config", $config);

                //return $view->render();
                $tekst = "Hier moet de inhoud van een template staan";
                return $tekst;
            }
        }

        return;
    }

    /**
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return array
     */
    public function showQueryColumns(array &$config, &$parentObject)
    {
        $columnList = $config['flexParentDatabaseRow']['pi_flexform']['data']['database']['lDEF']['columnNames']['vDEF'];
		$columnNames = explode(",",$columnList);
		// DebugUtility::debug($columnNames,'columnNames in showQueryColumns');
        $options = [];
        foreach ($columnNames as $columnName) {
			$trimmedColumnName = trim($columnName);
            $options[] = [0 => $trimmedColumnName, 1 => $trimmedColumnName];
        }
        $config['items'] = $options;
		// DebugUtility::debug($config['items'],'items in showQueryColumns');
	}

    /**
     * PopulateFieldTypes - showing the Partials dir with a partial for each fieldType, both in CUD and in Query/database.xml flexform
     *
     *  fieldtypes are arrays of { name, veldtype }
     *  the names are populated in showQueryColumns (for Query) and populateFieldNames (for Cud)
     *  the veldtypes are populated
     *
     * @param array $config Configuration Array
     * @param array $parentObject Parent Object
     * @return array
     */
    public function populateDisplayFieldTypes(array &$config, &$parentObject)
    {
        $config["items"] = $this->populateFieldTypes('Display');
        return $config;
    }

    public function populateInputFieldTypes(array &$config, &$parentObject)
    {
        $config["items"] = $this->populateFieldTypes('Input');
        return $config;
    }

    public function populateFilterFieldTypes(array &$config, &$parentObject)
    {
        $config["items"] = $this->populateFieldTypes('Filter');
        return $config;
    }

    /**
     *
     */
    protected function populateFieldTypes($subdir)
    {
        $options = [];

        $label = "--- Please Select field ---";
        $options[] = [0 => $label, 1 => ""];

        $partialDir =
            Environment::getPublicPath() .
            '/typo3conf/ext/rsrq/Resources/Private/Partials/Fieldtypes/'.$subdir;
        $filelist = array_diff(scandir($partialDir), array('..', '.'));

        foreach ($filelist as $_file) {
            $fileName = basename($_file, '.html');
            $options[] = [0 => $fileName, 1 => $fileName];
        }

        return $options;
    }
    protected function showColumns($targetTable)
    {
        $rows = array();
        if (!empty($targetTable)) {
            $statement = "SHOW COLUMNS FROM " . $targetTable;
            $sqlService = new SqlService($statement);
            $rows = $sqlService->getRows();
        }
        return $rows;
    }
}

