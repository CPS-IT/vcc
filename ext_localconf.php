<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(function () {
    $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vcc']);

    // Initialize array for internal hooks
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['communicationService'])) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['communicationService'] = [];
    }

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

    if (!empty($extensionConfiguration['cookieSupport'])) {
        // Register hook to handle user session
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['vcc'] =
            \CPSIT\Vcc\Hooks\EndOfFrontendHook::class . '->checkSessionIsInUse';

        // Initialize array for hooks
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['checkSession'])
            || !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['checkSession'])
        ) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['checkSession'] = [];
        }

        // Support felogin
        if (!empty($extensionConfiguration['feloginEnable'])) {
            // Register own hook handler for rsa session cookie
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['checkSession']['vcc_felogin'] =
                \CPSIT\Vcc\Hooks\CheckSession\Felogin::class;
        }

        // Support powermail >= 2.0
        if (!empty($extensionConfiguration['powermailEnable'])
            && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('powermail')
            && version_compare(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('powermail'), '2.0', '>=')
        ) {
            // Register hook for powermail
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['checkSession']['vcc_powermail'] =
                \CPSIT\Vcc\Hooks\CheckSession\Powermail::class;
        }

        // Support sr_freecap
        if (!empty($extensionConfiguration['srfreecapEnable'])
            && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('sr_freecap')
        ) {
            // Register hook for sr_freecap
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['checkSession']['vcc_sr_freecap'] =
                \CPSIT\Vcc\Hooks\CheckSession\SrFreecap::class;
        }
    }
});
