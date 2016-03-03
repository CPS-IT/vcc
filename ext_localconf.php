<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {
    $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vcc']);

    if ($extensionConfiguration['cacheControl'] === 'manual') {
        // Register hook to add the cache clear button to configured items in different views
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook']['vcc'] =
            \CPSIT\Vcc\Hooks\ClearCacheIconHook::class . '->addButton';
    } elseif ($extensionConfiguration['cacheControl'] === 'automatic') {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['vcc'] =
            \CPSIT\Vcc\Hooks\RecordSavedPostProcessHook::class;

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['vcc'] =
            \CPSIT\Vcc\Hooks\ClearCachePostProcessHook::class . '->clearCacheByCommand';
    }

    // Initialize array for internal hooks
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['communicationService'])) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['communicationService'] = array();
    }
}

?>