<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vcc']);

	if ($extensionConfiguration['cacheControl'] === 'manual') {
		// Register hook to add the cache clear button to configured items in different views
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['docHeaderButtonsHook']['vcc'] =
			'EXT:vcc/Classes/Hook/Tx_Vcc_Hook_ClearCacheIconHook.php:Tx_Vcc_Hook_ClearCacheIconHook->addButton';
	} else {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['vcc'] =
			'EXT:vcc/Classes/Hook/Tx_Vcc_Hook_RecordSavedPostProcessHook.php:Tx_Vcc_Hook_RecordSavedPostProcessHook';

		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['vcc'] =
			'EXT:vcc/Classes/Hook/Tx_Vcc_Hook_ClearCachePostProcessHook.php:&Tx_Vcc_Hook_ClearCachePostProcessHook->clearCacheByCommand';
	}

	// Initialize array for internal hooks
	if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['communicationService'])) {
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['communicationService'] = array();
	}
}

?>