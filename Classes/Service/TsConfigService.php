<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Nicole Cordes <cordes@cps-it.de>
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
 * Service to handle TSConfig settings
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 * @package TYPO3
 * @subpackage vcc
 */
class tx_vcc_service_tsConfigService implements t3lib_Singleton {

	/**
	 * @var array
	 */
	var $configurationArray = array();

	/**
	 * @var tx_vcc_service_loggingService|NULL
	 */
	var $loggingService = NULL;

	/**
	 * Initialize the object
	 *
	 * @return void
	 */
	public function __construct() {
		$loggingService = t3lib_div::makeInstance('tx_vcc_service_loggingService');
		$this->injectLoggingService($loggingService);
	}

	/**
	 * Injects the logging service
	 *
	 * @param tx_vcc_service_loggingService $loggingService
	 *
	 * @return void
	 */
	public function injectLoggingService(tx_vcc_service_loggingService $loggingService) {
		$this->loggingService = $loggingService;
	}

	/**
	 * Returns the configuration
	 *
	 * @param integer $id
	 *
	 * @return array
	 */
	public function getConfiguration($id) {
		if (!isset($this->configurationArray[$id])) {
			$modTsConfig = t3lib_BEfunc::getModTSconfig($id, 'mod.vcc');
			$this->configurationArray[$id] = $modTsConfig['properties'];

			// Log debug information
			$logData = array(
				'id' => $id,
				'configuration' => $modTsConfig['properties']
			);
			$this->loggingService->debug('TsConfigService::getConfiguration id: ' . $id, $logData);
		}

		return $this->configurationArray[$id];
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/vcc/Classes/Service/TsConfigService.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/vcc/Classes/Service/TsConfigService.php']);
}

?>