<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(function () {
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

    // Register sprite icons
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'vcc-clearVarnishCache',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        [
            'source' => 'EXT:vcc/Resources/Public/Icons/CachePlugin.png',
        ]
    );

    // Add default module settings
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
mod.vcc {
    pages = 1
    pages {
        typolink {
            parameter.field = uid
        }
    }
    
    pages_language_overlay = 1
    pages_language_overlay {
        typolink {
            parameter.field = pid
            additionalParams = &L={field:sys_language_uid}
            additionalParams.insertData = 1
        }
    }
    
    tt_content = 1
    tt_content {
        typolink {
            parameter.field = pid
            additionalParams = &L={field:sys_language_uid}
            additionalParams.insertData = 1
        }
    }
}
');
});
