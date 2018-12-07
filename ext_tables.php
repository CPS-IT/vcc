<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(function () {
    // Register sprite icons
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'vcc-clearVarnishCache',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        [
            'source' => 'EXT:vcc/Resources/Public/Icons/CachePlugin.png',
        ]
    );
    $iconRegistry->registerIcon(
        'vcc-ajax-request-queue-cleared',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        [
            'source' => 'EXT:vcc/Resources/Public/Icons/AjaxRequestQueueCleared.png',
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
