<?php
namespace CPSIT\Vcc\Service;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service to handle TSConfig settings
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 * @package TYPO3
 * @subpackage vcc
 */
class TsConfigService implements SingletonInterface {

	/**
	 * @var array
	 */
	protected $configurationArray = array();

	/**
	 * @var LoggingService|NULL
	 */
	protected $loggingService = NULL;

	/**
	 * Initialize the object
	 */
	public function __construct() {
		$loggingService = GeneralUtility::makeInstance(LoggingService::class);
		$this->injectLoggingService($loggingService);
	}

	/**
	 * Injects the logging service
	 *
	 * @param \CPSIT\Vcc\Service\LoggingService $loggingService
	 * @return void
	 */
	public function injectLoggingService(LoggingService $loggingService) {
		$this->loggingService = $loggingService;
	}

	/**
	 * Returns the configuration
	 *
	 * @param int $id
	 * @return array
	 */
	public function getConfiguration($id) {
		if (!isset($this->configurationArray[$id])) {
			$modTsConfig = BackendUtility::getModTSconfig($id, 'mod.vcc');
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

?>