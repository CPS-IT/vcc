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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service to log requests and responses
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 * @package TYPO3
 * @subpackage vcc
 */
class LoggingService implements SingletonInterface {

	const MODE_DISABLED = 0;
	const MODE_MINIMAL = 1;
	const MODE_DEBUG = 2;

	const DEBUG = 99;
	const ERROR = 4;
	const INFO = 2;
	const NOTICE = 1;
	const OK = 0;
	const WARNING = 3;

	/**
	 * @var ExtensionSettingService|NULL
	 */
	protected $extensionSettingService = NULL;

	/**
	 * @var int
	 */
	protected $loggingMode = 0;

	/**
	 * @var string
	 */
	protected $hash = '';

	/**
	 * @var int
	 */
	protected $maxLogAge = 0;

	/**
	 * Initialize the object
	 */
	public function __construct() {
		$extensionSettingService = GeneralUtility::makeInstance(ExtensionSettingService::class);
		$this->injectExtensionSettingService($extensionSettingService);

		$configuration = $this->extensionSettingService->getConfiguration();
		$this->loggingMode = $configuration['loggingMode'];
		$this->maxLogAge = $configuration['maxLogAge'];

		$this->hash = md5(uniqid('LoggingService', TRUE));
	}

	/**
	 * Injects the extension setting service
	 *
	 * @param \CPSIT\Vcc\Service\ExtensionSettingService $extensionSettingService
	 * @return void
	 */
	public function injectExtensionSettingService(ExtensionSettingService $extensionSettingService) {
		$this->extensionSettingService = $extensionSettingService;
	}

	/**
	 * @param string $message
	 * @param array $logData
	 * @param int $pid
	 * @param int $callerDepth
	 * @param null $caller
	 * @return void
	 */
	public function debug($message, $logData = array(), $pid = 0, $callerDepth = 2, $caller = NULL) {
		if ($this->loggingMode & self::MODE_DEBUG) {
			// Adjust callerDepth due to debug function
			$callerDepth++;
			$this->log($message, $logData, self::DEBUG, $pid, $callerDepth, $caller);
		}
	}

	/**
	 * @param string $message
	 * @param array $logData
	 * @param int $type
	 * @param int $pid
	 * @param int $callerDepth
	 * @param null $caller
	 */
	public function log($message, $logData = array(), $type = self::INFO, $pid = 0, $callerDepth = 2, $caller = NULL) {
		if ($this->loggingMode & self::MODE_MINIMAL) {
			// Get caller if not already set
			if ($caller === NULL) {
				$caller = $this->getCallerFromBugtrace($callerDepth);
			}

			$insertArray = array(
				'pid' => $pid,
				'tstamp' => time(),
				'be_user' => $GLOBALS['BE_USER']->user['uid'],
				'type' => $type,
				'message' => $message,
				'log_data' => serialize($logData),
				'caller' => serialize($caller),
				'hash' => $this->hash
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_vcc_log', $insertArray);

			// Remove old entries
			$month = date('m', time());
			$day = 0 - $this->maxLogAge;
			$year = date('Y', time());
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_vcc_log', 'tstamp<' . mktime(0, 0, 0, $month, $day, $year));
		}
	}

	/**
	 * @param int $callerDepth
	 *
	 * @return array
	 */
	protected function getCallerFromBugtrace($callerDepth) {
		// Get trace array
		$trace = debug_backtrace();

		// Adjust callerDepth due to separate function
		$callerDepth++;
		if (isset($trace[$callerDepth])) {
			return $trace[$callerDepth];
		}

		return array();
	}
}

?>