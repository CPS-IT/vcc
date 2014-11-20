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
 * Service to send the cache command to server
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 * @package TYPO3
 * @subpackage vcc
 */
class tx_vcc_service_communicationService implements t3lib_Singleton {

	/**
	 * @var tslib_cObj|NULL
	 */
	protected $contentObject = NULL;

	/**
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * @var tx_vcc_service_extensionSettingService|NULL
	 */
	protected $extensionSettingService = NULL;

	/**
	 * @var array
	 */
	protected $hookObjects = array();

	/**
	 * @var tx_vcc_service_loggingService|NULL
	 */
	protected $loggingService = NULL;

	/**
	 * @var tx_vcc_service_tsConfigService|NULL
	 */
	protected $tsConfigService = NULL;

	/**
	 * Initialize the object
	 */
	public function __construct() {
		$extensionSettingService = t3lib_div::makeInstance('tx_vcc_service_extensionSettingService');
		$this->injectExtensionSettingService($extensionSettingService);

		$loggingService = t3lib_div::makeInstance('tx_vcc_service_loggingService');
		$this->injectLoggingService($loggingService);

		$tsConfigService = t3lib_div::makeInstance('tx_vcc_service_tsConfigService');
		$this->injectTsConfigService($tsConfigService);

		$this->configuration = $this->extensionSettingService->getConfiguration();
		$this->contentObject = t3lib_div::makeInstance('tslib_cObj');

		// Initialize hook objects
		$this->initializeHookObjects();
	}

	/**
	 * Injects the extension setting service
	 *
	 * @param tx_vcc_service_extensionSettingService $extensionSettingService
	 * @return void
	 */
	public function injectExtensionSettingService(tx_vcc_service_extensionSettingService $extensionSettingService) {
		$this->extensionSettingService = $extensionSettingService;
	}

	/**
	 * Injects the logging service
	 *
	 * @param tx_vcc_service_loggingService $loggingService
	 * @return void
	 */
	public function injectLoggingService(tx_vcc_service_loggingService $loggingService) {
		$this->loggingService = $loggingService;
	}

	/**
	 * Injects the TSConfig service
	 *
	 * @param tx_vcc_service_tsConfigService $tsConfigService
	 * @return void
	 */
	public function injectTsConfigService(tx_vcc_service_tsConfigService $tsConfigService) {
		$this->tsConfigService = $tsConfigService;
	}

	/**
	 * @return void
	 */
	protected function initializeHookObjects() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['communicationService'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['communicationService'] as $classReference) {
				$hookObject = t3lib_div::getUserObj($classReference);

				// Hook objects have to implement interface
				if ($hookObject instanceof tx_vcc_hook_communicationServiceHookInterface) {
					$this->hookObjects[] = $hookObject;
				}
			}
			unset($classReference);
		}
	}

	/**
	 * @return bool
	 */
	public function displayBackendMessage() {
		return ($this->configuration['loggingMode'] & tx_vcc_service_loggingService::MODE_DEBUG || $this->configuration['cacheControl'] === 'manual');
	}

	/**
	 * Generates the flash messages for the requests
	 *
	 * @param array $resultArray
	 * @param bool $wrapWithTags
	 * @return string
	 */
	public function generateBackendMessage(array $resultArray, $wrapWithTags = TRUE) {
		$content = '';

		if (is_array($resultArray)) {
			foreach ($resultArray as $result) {
				$header = 'Server: ' . $result['server'] . ' // Host: ' . $result['host'];
				$message = 'Request: ' . $result['request'];
				switch ($result['status']) {
					case t3lib_FlashMessage::OK:
						$content .= 'parent.TYPO3.Flashmessage.display(
								TYPO3.Severity.ok,
								"' . $header . '",
								"' . $message . '<br />Message: ' . $result['message'][0] . '",
								5
							);';
						break;

					default:
						$content .= 'parent.TYPO3.Flashmessage.display(
								TYPO3.Severity.error,
								"' . $header . '",
								"' . $message . '<br />Message: ' . implode('<br />', $result['message']) .
									'<br />Sent:<br />' . implode('<br />', $result['requestHeader']) . '",
								10
							);';
						break;
				}
			}
			unset($result);
		}

		if (!empty($content) && $wrapWithTags) {
			$content = '<script type="text/javascript">' . $content . '</script>';
		}

		return $content;
	}

	/**
	 * Stores the flash messages for the requests in the session
	 *
	 * @param array $resultArray
	 * @return string
	 */
	public function storeBackendMessage(array $resultArray) {
		$content = '';

		if (is_array($resultArray)) {
			foreach ($resultArray as $result) {
				$header = 'Varnish Cache Control';
				$message = 'Server: ' . $result['server'] . ' // Host: ' . $result['host'] . '<br />Request: ' . $result['request'];
				switch ($result['status']) {
					case t3lib_FlashMessage::OK:
						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$message . '<br />Message: ' . $result['message'][0],
							$header,
							t3lib_FlashMessage::OK
						);
						break;

					default:
						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$message . '<br />Message: ' . implode('<br />', $result['message']) . '<br />Sent:<br />' . implode('<br />', $result['requestHeader']),
							$header,
							t3lib_FlashMessage::ERROR
						);
						break;
				}

				$flashMessage->setStoreInSession(TRUE);
				if (class_exists('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService')) {
					/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
					$flashMessageService = t3lib_div::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
					/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
					$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
					$defaultFlashMessageQueue->enqueue($flashMessage);
				} else {
					t3lib_FlashMessageQueue::addMessage($flashMessage);
				}
			}
			unset($result);
		}

		return $content;
	}

	/**
	 * Send clear cache commands for pages to defined server
	 *
	 * @param string $fileName
	 * @param string $host
	 * @param bool $quote
	 * @return array
	 */
	public function sendClearCacheCommandForFiles($fileName, $host = '', $quote = TRUE) {
		// Log debug information
		$logData = array(
			'fileName' => $fileName,
			'host' => $host
		);
		$this->loggingService->debug('CommunicationService::sendClearCacheCommandForFiles arguments', $logData);

		// If no host was given get all
		if ($host === '') {
			$hostArray = array();

			// Get all domain records and check page access
			$domainArray = t3lib_BEfunc::getRecordsByField('sys_domain', 'redirectTo', '', ' AND hidden=0');
			if (is_array($domainArray) && !empty($domainArray)) {
				$permsClause = $GLOBALS['BE_USER']->getPagePermsClause(2);
				foreach ($domainArray as $domain) {
					$pageinfo = t3lib_BEfunc::readPageAccess($domain['pid'], $permsClause);
					if ($pageinfo !== FALSE) {
						$hostArray[] = $domain['domainName'];
					}
				}
				unset($domain);
			}
			$host = implode(',', $hostArray);

			// Log debug information
			$logData['host'] = $host;
			$this->loggingService->debug('CommunicationService::sendClearCacheCommandForFiles built host', $logData);
		}

		return $this->processClearCacheCommand($fileName, 0, $host, $quote);
	}

	/**
	 * Send clear cache commands for pages to defined server
	 *
	 * @param string $table
	 * @param int $uid
	 * @param string $host
	 * @param bool $quote
	 * @return array
	 */
	public function sendClearCacheCommandForTables($table, $uid, $host = '', $quote = TRUE) {
		// Get current record to process
		$record = t3lib_BEfunc::getRecord($table, $uid);

		// Build request
		if ($table === 'pages') {
			$pid = $record['uid'];
		} else {
			$pid = $record['pid'];
		}

		// Log debug information
		$logData = array(
			'table' => $table,
			'uid' => $uid,
			'host' => $host,
			'pid' => $pid
		);
		$this->loggingService->debug('CommunicationService::sendClearCacheCommandForTables arguments', $logData, $pid);

		$this->createTSFE($pid);
		$tsConfig = $this->tsConfigService->getConfiguration($pid);
		$typolink = $tsConfig[$table . '.']['typolink.'];
		$this->contentObject->data = $record;

		$url = $this->contentObject->typoLink_URL($typolink);
		$LD = $this->contentObject->lastTypoLinkLD;

		// Check for root site
		if ($url === '' && $table === 'pages') {
			$rootline = t3lib_BEfunc::BEgetRootLine($uid);
			if (is_array($rootline) && count($rootline) > 1) {
				// If uid equals the site root we have to process
				if ($uid == $rootline[1]['uid']) {
					$url = '/';
				}
			}
		}

		// Log debug information
		$logData['url'] = $url;
		$this->loggingService->debug('CommunicationService::sendClearCacheCommandForTables built url', $logData, $pid);

		// Process only for valid URLs
		if ($url !== '') {

			$url = $this->removeHost($url);
			$responseArray = $this->processClearCacheCommand($url, $pid, $host, $quote);

			// Check support of index.php script
			if ($this->configuration['enableIndexScript']) {
				$url = $LD['url'] . $LD['linkVars'];
				$url = $this->removeHost($url);

				$indexResponseArray = $this->processClearCacheCommand($url, $pid, $host, $quote);
				$responseArray = array_merge($responseArray, $indexResponseArray);
			}

			return $responseArray;
		}

		return array(
			array(
				'status' => t3lib_FlashMessage::ERROR,
				'message' => array('No valid URL was generated.', 'table: ' . $table, 'uid: ' . $uid, 'host: ' . $host),
				'requestHeader' => array($url)
			)
		);
	}

	/**
	 * Load a faked frontend to be able to use stdWrap.typolink
	 *
	 * @param int $id
	 * @return void
	 */
	protected function createTSFE($id) {
		if (!is_object($GLOBALS['TT'])) {
			$GLOBALS['TT'] = t3lib_div::makeInstance('t3lib_TimeTrackNull');
		}

		$GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], $id, 0);
		$GLOBALS['TSFE']->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getPageAndRootline();
		//$GLOBALS['TSFE']->tmpl->start($GLOBALS['TSFE']->sys_page->getRootline($id));
		$GLOBALS['TSFE']->getConfigArray();
		if (TYPO3_MODE == 'BE') {
			// Set current backend language
			$GLOBALS['TSFE']->getPageRenderer()->setLanguage($GLOBALS['LANG']->lang);
		}
		$GLOBALS['TSFE']->newcObj();

		TSpagegen::pagegenInit();
	}

	/**
	 * Processes the CURL request and sends action to Varnish server
	 *
	 * @param string $url
	 * @param int $pid
	 * @param string $host
	 * @param bool $quote
	 * @return array
	 */
	protected function processClearCacheCommand($url, $pid, $host = '', $quote = TRUE) {
		$responseArray = array();

		$serverArray = t3lib_div::trimExplode(',', $this->configuration['server'], 1);
		foreach ($serverArray as $server) {
			$response = array(
				'server' => $server
			);

			// Build request
			if ($this->configuration['stripSlash']) {
				$url = rtrim($url, '/');
			}
			$request = $server . '/' . ltrim($url, '/');
			$response['request'] = $request;

			// Check for curl functions
			if (!function_exists('curl_init')) {
				// TODO: Implement fallback to file_get_contents()
				$response['status'] = t3lib_FlashMessage::ERROR;
				$response['message'] = 'No curl_init available';
			} else {
				// If no host was given we need to loop over all
				$hostArray = array();
				if ($host !== '') {
					$hostArray = t3lib_div::trimExplode(',', $host, 1);
				} else {
					// Get all (non-redirecting) domains from root
					$rootLine = t3lib_BEfunc::BEgetRootLine($pid);
					foreach ($rootLine as $row) {
						$domainArray = t3lib_BEfunc::getRecordsByField('sys_domain', 'pid', $row['uid'], ' AND redirectTo="" AND hidden=0');
						if (is_array($domainArray) && !empty($domainArray)) {
							foreach ($domainArray as $domain) {
								$hostArray[] = $domain['domainName'];
							}
							unset($domain);
						}
					}
					unset($row);
				}

				// Fallback to current server
				if (empty($hostArray)) {
					$domain = rtrim(t3lib_div::getIndpEnv('TYPO3_SITE_URL'), '/');
					$hostArray[] = substr($domain, strpos($domain, '://') + 3);
				}

				// Loop over hosts
				foreach ($hostArray as $xHost) {
					$response['host'] = $xHost;

					// Curl initialization
					$ch = curl_init();

					// Disable direct output
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

					// Only get header response
					curl_setopt($ch, CURLOPT_HEADER, 1);
					curl_setopt($ch, CURLOPT_NOBODY, 1);

					// Set url
					curl_setopt($ch, CURLOPT_URL, $request);

					// Set method and protocol
					$httpMethod = trim($this->configuration['httpMethod']);
					$httpProtocol = trim($this->configuration['httpProtocol']);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
					curl_setopt($ch, CURLOPT_HTTP_VERSION, ($httpProtocol === 'http_10') ? CURL_HTTP_VERSION_1_0 : CURL_HTTP_VERSION_1_1);

					// Set X-Host and X-Url header
					$headerArray = array();
					$headerArray[] = 'X-Host: ' . (($quote) ? preg_quote($xHost) : $xHost);
					if ($url) {
						$headerArray[] = 'X-Url: ' . (($quote) ? preg_quote($url) : $url);
					}
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);

					// Store outgoing header
					curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

					// Include preProcess hook (e.g. to set some alternative curl options
					foreach ($this->hookObjects as $hookObject) {
						/** @var tx_vcc_hook_communicationServiceHookInterface $hookObject */
						$hookObject->preProcess($ch, $request, $response, $this);
					}
					unset($hookObject);

					$header = curl_exec($ch);
					if (!curl_error($ch)) {
						$response['status'] = (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) ? t3lib_FlashMessage::OK : t3lib_FlashMessage::ERROR;
						$response['message'] = preg_split('/(\r|\n)+/m', trim($header));
					} else {
						$response['status'] = t3lib_FlashMessage::ERROR;
						$response['message'] = array(curl_error($ch));
					}
					$response['requestHeader'] = preg_split('/(\r|\n)+/m', trim(curl_getinfo($ch, CURLINFO_HEADER_OUT)));

					// Include postProcess hook (e.g. to start some other jobs)
					foreach ($this->hookObjects as $hookObject) {
						/** @var tx_vcc_hook_communicationServiceHookInterface $hookObject */
						$hookObject->postProcess($ch, $request, $response, $this);
					}
					unset($hookObject);

					curl_close($ch);

					// Log debug information
					$logData = array(
						'url' => $url,
						'pid' => $pid,
						'host' => $host,
						'response' => $response
					);
					$logType = ($response['status'] == t3lib_FlashMessage::OK) ? tx_vcc_service_loggingService::OK : tx_vcc_service_loggingService::ERROR;
					$this->loggingService->log('CommunicationService::processClearCacheCommand', $logData, $logType, $pid, 3);

					$responseArray[] = $response;
				}
				unset($xHost);
			}
		}
		unset($server);

		return $responseArray;
	}

	/**
	 * Remove any host from an url
	 *
	 * @param string $url
	 * @return string
	 */
	protected function removeHost($url) {
		if (strpos($url, '://') !== FALSE) {
			$urlArray = parse_url($url);
			$url = substr($url, strlen($urlArray['scheme'] . '://' . $urlArray['host']));
		}

		return $url;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/vcc/Classes/Service/CommunicationService.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/vcc/Classes/Service/CommunicationService.php']);
}

?>