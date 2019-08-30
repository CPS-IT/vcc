<?php
namespace CPSIT\Vcc\Hooks;


    /**
    * @param string $currentIdentifier
    * @param object $intScriptConfiguration
    * @return string
    */
interface CustomIdentifierInterface
{
    public function modifyCacheIdentifier($currentIdentifier, $intScriptConfiguration);
}
