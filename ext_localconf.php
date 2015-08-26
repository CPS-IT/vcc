<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vcc']);

	if ($extensionConfiguration['cacheControl'] === 'manual') {
		// Register hook to add the cache clear button to configured items in different views
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['docHeaderButtonsHook']['vcc'] =
			'CPSIT\Vcc\Hooks\ClearCacheIconHook->addButton';
	} elseif ($extensionConfiguration['cacheControl'] === 'automatic') {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['vcc'] =
			'CPSIT\Vcc\Hooks\RecordSavedPostProcessHook';

		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['vcc'] =
			'CPSIT\Vcc\Hooks\ClearCachePostProcessHook->clearCacheByCommand';
	}

	// Initialize array for internal hooks
	if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['communicationService'])) {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['communicationService'] = array();
	}
}

?>