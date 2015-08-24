<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

// Register sprite icons
$icons = array(
	'clearVarnishCache' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('vcc') . 'Resources/Public/Icons/CachePlugin.png',
);
\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, 'vcc');

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

?>