<?php
namespace RedSeadog\Rsrq\ViewHelpers;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * This ViewHelper takes two arrays and returns
 * the `array_merge`d result.
 */
class ArrayMergeViewHelper extends AbstractViewHelper
{
    /**
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('array1','mixed','First array to be array_merge',true);
        $this->registerArgument('array2','mixed','Second array to be array_merge',true);
    }

    /**
     * Merges the two arrays .
     *
     * @return array
     */
    public function render()
    {
        $array1 = $this->arguments['array1'];
        $array2 = $this->arguments['array2'];
        // DebugUtility::debug($array1,'array1 in ArrayMergeViewHelper');
        // DebugUtility::debug($array2,'array2 in ArrayMergeViewHelper');
		if (empty($array1)) return $array2;
		if (empty($array2)) return $array1;
        return array_merge($array1, $array2);
    }
}
