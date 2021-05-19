<?php
namespace RedSeadog\Wfqbe\ViewHelpers;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * This ViewHelper transposes $rows[...][$label] into $newRows[$label][...]
 */
class Array2ChartViewHelper extends AbstractViewHelper
{
    /**
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('rows','mixed','Rows array to be transposed',true);
    }

    /**
     * Transposes the array of arrays
	 *
	 *	assumption:
	 *	the first column values are labels on the x-axis
	 *	the second and following columns each are a dataset with numbers
     *
     * @return array
     */
    public function render()
    {
        $rows = $this->arguments['rows'];
        // DebugUtility::debug($rows,'rows in Array2ChartViewHelper');

		if (!is_array($rows)) {
			DebugUtility::debug($rows,'rows is not an array in Array2ChartViewHelper');
			exit(1);
		}

		// transpose
		$newRows = array();
		foreach ($rows as $key => $subarr) {
			foreach ($subarr as $subkey => $subvalue) {
				$newRows[$subkey][$key] = $subvalue;
			}
		}
        // DebugUtility::debug($newRows,'newRows in Array2ChartViewHelper');

		// array2chart - first column are the labels, text therefore quoted
		$chartstrings = array();
		// $quote = "";
		$quote = "'";
		foreach ($newRows AS $key => $value) {
			$chartstrings[$key] = $this->mkstring($value,$quote);
			$quote = "";
		}
        // DebugUtility::debug($chartstrings,'chartstrings in Array2ChartViewHelper');
		return $chartstrings;
	}

	/**
	 *	mkstring - an array of $elements decorated with $quote, separated by comma, enclosed by square brackets
	 */
	protected function mkstring($elements,$quote)
	{
		$decoration = $quote.','.$quote;
		$str = sprintf("[%s%s%s]", $quote,implode($decoration, $elements ),$quote);
		return $str;
	}
}
