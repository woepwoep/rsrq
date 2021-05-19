<?php
namespace RedSeadog\Rsrq\ViewHelpers;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * This ViewHelper takes two arrays and returns
 * the `array_merge`d result.
 */
class PrependStringViewHelper extends AbstractViewHelper
{
    /**
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('array','mixed','Array for which key is to be prepended by string',true);
        $this->registerArgument('string','string','String to be prepended',true);
    }

    /**
     * if array, prepends array_keys with string
	 * if string, prepends parameter array with string
     *
     * @return mixed
     */
    public function render()
    {
        $array = $this->arguments['array'];
        $string = $this->arguments['string'];
        // DebugUtility::debug($array,'array in PrependStringViewHelper');
        // DebugUtility::debug($string,'string in PrependStringViewHelper');

		// are parameters valid?
		if (empty($array)) return $array;
		if (empty($string)) return $array;

		// if arg1 is a string, we're done fast
		if (!is_array($array)) {
			return $string . $array;
		}

		$newArray = array();
		foreach($array AS $key => $value) {
			$newArray['RSRQ_'.$key] = $value;
		}
        // DebugUtility::debug($newArray,'newArray in PrependStringViewHelper');
        return $newArray;
    }
}
