<?php
namespace RedSeadog\Rsrq\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

class IsVerplichtViewHelper extends AbstractConditionViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument(
            'waarde',
            'string',
            'inhoud van het veld',
            $mandatory = true,
            $defaultValue = ""
        );
    }

    public static function verdict(
        array $arguments,
        RenderingContextInterface $renderingContext
    ): bool {
        $veldLen = strlen($arguments['waarde']);
        if ($veldLen > 0) {
            return true;
        }
        return false;
    }
}
