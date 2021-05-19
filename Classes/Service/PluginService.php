<?php
namespace RedSeadog\Rsrq\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Core\Environment;

/**
 *  PluginService
 */
class PluginService implements \TYPO3\CMS\Core\SingletonInterface
{
    protected $extName;
    protected $pluginSettings;
    protected $fullTsArray;

    /**
     * Constructs an instance of PluginService.
     *
     * @param string $extName
     */
    public function __construct($extName)
    {
        $this->extName = $extName;

        $objectManager = GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
        );
        $configurationManager = $objectManager->get(
            'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager'
        );
        $this->fullTsConf = $configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        $tsService = new TypoScriptService();
        $this->fullTsArray = $tsService->convertTypoScriptArrayToPlainArray(
            $this->fullTsConf
        );
        $this->pluginSettings = $this->fullTsArray['plugin'][$extName];
        if (!is_array($this->pluginSettings)) {
            DebugUtility::debug(
                'PluginService: no such extension plugin found: ' . $extName
            );
            DebugUtility::debug($this->fullTsArray);
            //exit(1);
        }
    }

    /**
     * @param string $templateName
     * @return string
     */
    public function getTemplatePathAndFilename($templateName)
    {
        // find the template file
        $foundFile = '';
        $pathNames = $this->pluginSettings['view']['templateRootPaths'];
        if (empty($pathNames)) {
            DebugUtility::debug(
                'No templateRootPaths set for plugin ' . $this->extName
            );
            DebugUtility::debug(
                'PluginService: no such extension plugin found: ' . $extName
            );
            exit(1);
        }
        foreach ($pathNames as $pathName) {
            $tryFile =
                GeneralUtility::getFileAbsFileName($pathName) .
                '/' .
                $templateName;
            DebugUtility::debug(
                'PluginService: trying ' . $tryFile . '=' . $tryFile
            );
            if (file_exists($tryFile)) {
                $foundFile = $tryFile;
            }
        }
        if (!$foundFile) {
            DebugUtility::debug($pathNames);
            DebugUtility::debug(
                'PluginService: could not find template ' . $templateName . '.'
            );
            exit(1);
        } else {
            DebugUtility::debug(
                'PluginService: found file ' . $foundFile . '.'
            );
            exit(1);
        }
        return $foundFile;
    }

    /**
     * Returns the plugin settings
     */
    public function getSettings()
    {
        return $this->pluginSettings;
    }
}

