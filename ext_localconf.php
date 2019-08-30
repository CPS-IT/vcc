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

    if ($extensionConfiguration['cacheControl'] === 'manual') {
        // Register hook to add the cache clear button to configured items in different views
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook']['vcc'] =
            \CPSIT\Vcc\Hooks\ClearCacheIconHook::class . '->addButton';
    } elseif ($extensionConfiguration['cacheControl'] === 'automatic') {
        if (empty($extensionConfiguration['ajaxRequestQueue'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['vcc'] =
                \CPSIT\Vcc\Hooks\RecordSavedPostProcessHook::class;
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['vcc'] =
                \CPSIT\Vcc\Hooks\ClearCachePostProcessHook::class . '->clearCacheByCommand';
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['vcc'] =
                \CPSIT\Vcc\Hooks\AjaxRequestQueue\RecordSavedPostProcessHook::class;
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['vcc'] =
                \CPSIT\Vcc\Hooks\AjaxRequestQueue\ClearCachePostProcessHook::class . '->clearCacheByCommand';
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['vcc'] =
                \CPSIT\Vcc\Hooks\AjaxRequestQueue\WriteJavascriptHook::class . '->addAjaxRequestQueueDataFromSession';
        }
    }

    if (!empty($extensionConfiguration['pidHeader'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']['vcc'] =
            \CPSIT\Vcc\Hooks\AddFrontendHeaderHook::class . '->addHeader';
    }

    $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][1498036520] =
        \CPSIT\Vcc\Backend\ToolbarItems\AjaxRequestQueueToolbarItem::class;

    $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][1498036612] =
        \CPSIT\Vcc\Backend\ToolbarItems\CacheClearToolbarItem::class;

    // Register scheduler tasks
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\CPSIT\Vcc\Task\ClearCachePages::class] = [
        'extension' => 'vcc',
        'title' => 'Clear Varnish cache for pages',
        'description' => 'Clears Varnish cache for chosen pages',
        'additionalFields' => \CPSIT\Vcc\Task\AdditionalFieldsPages::class,
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\CPSIT\Vcc\Task\ClearCachePlugins::class] = [
        'extension' => 'vcc',
        'title' => 'Clear Varnish cache by plugin',
        'description' => 'Clears Varnish cache for pages containing the chosen plugins',
        'additionalFields' => \CPSIT\Vcc\Task\AdditionalFieldsPlugins::class,
    ];

    // Register hook to add the cache clear button to files
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook']['vcc'] =
        CPSIT\Vcc\Hooks\EditIconsHook::class;

    if (!empty($extensionConfiguration['esiSupport'])) {

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-PostProc']['vcc'] =
            \CPSIT\Vcc\Renderer\EsiRenderer::class . '->restoreRequestParameter';

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']['vcc'] =
            \CPSIT\Vcc\Hooks\ContentPostProcessHook::class . '->replaceIntScripts';

        // respect global setting if present
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tx_vcc_esi'])) {
 
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
