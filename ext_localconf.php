<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(function () {
    $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vcc']);

    if (!empty($extensionConfiguration['esiSupport'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']['vcc'] =
            \CPSIT\Vcc\Hooks\ContentPostProcessHook::class . '->replaceIntScripts';

        // Register cache frontend for proxy class generation
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_vcc_esi'] = [
            'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
            'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
            'options' => [
                'compression' => true,
                'defaultLifetime' => 864000, // 10 days
            ],
            'groups' => ['system'],
        ];
    }
});
