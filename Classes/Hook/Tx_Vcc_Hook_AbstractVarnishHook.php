<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Nicole Cordes <cordes@cps-it.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Adds the cache clear button to the edit form
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 * @package TYPO3
 * @subpackage vcc
 */
abstract class Tx_Vcc_Hook_AbstractVarnishHook {

	/**
	 * @var tx_vcc_service_communicationService|NULL
	 */
	protected $communicationService = NULL;

	/**
	 * @var tx_vcc_service_tsConfigService|NULL
	 */
	protected $tsConfigService = NULL;

	/**
	 * Initialize the object
	 */
	public function __construct() {
		$communicationService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_vcc_service_communicationService');
		$this->injectCommunicationService($communicationService);

		$tsConfigService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_vcc_service_tsConfigService');
		$this->injectTsConfigService($tsConfigService);
	}

	/**
	 * Injects the communication service
	 *
	 * @param tx_vcc_service_communicationService $communicationService
	 * @return void
	 */
	protected function injectCommunicationService(tx_vcc_service_communicationService $communicationService) {
		$this->communicationService = $communicationService;
	}

	/**
	 * Injects the TSConfig service
	 *
	 * @param tx_vcc_service_tsConfigService $tsConfigService
	 * @return void
	 */
	protected function injectTsConfigService(tx_vcc_service_tsConfigService $tsConfigService) {
		$this->tsConfigService = $tsConfigService;
	}

	/**
	 * Checks if the button could be inserted
	 *
	 * @param int $pageId
	 * @param string $table
	 * @return bool
	 */
	protected function isHookAccessible($pageId, $table) {
		$access = FALSE;

		// Check edit rights for page as cache can be flushed then only
		if ($table === 'pages') {
			$permsClause = $GLOBALS['BE_USER']->getPagePermsClause(2);
		} else {
			$permsClause = $GLOBALS['BE_USER']->getPagePermsClause(16);
		}
		$pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($pageId, $permsClause);
		if ($pageinfo !== FALSE) {
			// Get TSconfig for extension
			$tsConfig = $this->tsConfigService->getConfiguration($pageId);
			if (isset($tsConfig[$table]) && !empty($tsConfig[$table])) {
				$access = TRUE;
			}
		}

		return $access;
	}

	/**
	 * @param string $name
	 * @param array $resultArray
	 * @return void
	 */
	protected function attachResultArrayToPageRenderer($name, $resultArray) {
		$message = $this->communicationService->generateBackendMessage($resultArray, FALSE);
		/** @var t3lib_PageRenderer $pageRenderer */
		$pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_PageRenderer');
		$pageRenderer->addJsInlineCode($name, $message);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/vcc/Classes/Hook/AbstractHookObject.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/vcc/Classes/Hooks/AbstractHookObject.php']);
}

?>