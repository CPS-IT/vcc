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
use TYPO3\CMS\Core\Messaging;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\TimeTracker\NullTimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page;
use TYPO3\CMS\Core\Error\Http;

/**
 * Service to send the cache command to server
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 * @package TYPO3
 * @subpackage vcc
 */
class CommunicationService implements SingletonInterface
{

    /**
     * @var ContentObjectRenderer|NULL
     */
    protected $contentObject = null;

    /**
     * @var array
     */
    protected $configuration = array();

    /**
     * @var ExtensionSettingService|NULL
     */
    protected $extensionSettingService = null;

    /**
     * @var array
     */
    protected $hookObjects = array();

    /**
     * @var LoggingService|NULL
     */
    protected $loggingService = null;

    /**
     * @var TsConfigService|NULL
     */
    protected $tsConfigService = null;

    /**
     * Initialize the object
     */
    public function __construct()
    {
        $extensionSettingService = GeneralUtility::makeInstance(ExtensionSettingService::class);
        $this->injectExtensionSettingService($extensionSettingService);

        $loggingService = GeneralUtility::makeInstance(LoggingService::class);
        $this->injectLoggingService($loggingService);

        $tsConfigService = GeneralUtility::makeInstance(TsConfigService::class);
        $this->injectTsConfigService($tsConfigService);

        $this->configuration = $this->extensionSettingService->getConfiguration();
        $this->contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        // Initialize hook objects
        $this->initializeHookObjects();
    }

    /**
     * Injects the extension setting service
     *
     * @param \CPSIT\Vcc\Service\ExtensionSettingService $extensionSettingService
     * @return void
     */
    public function injectExtensionSettingService(ExtensionSettingService $extensionSettingService)
    {
        $this->extensionSettingService = $extensionSettingService;
    }

    /**
     * Injects the logging service
     *
     * @param \CPSIT\Vcc\Service\LoggingService $loggingService
     * @return void
     */
    public function injectLoggingService(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Injects the TSConfig service
     *
     * @param \CPSIT\Vcc\Service\TsConfigService $tsConfigService
     * @return void
     */
    public function injectTsConfigService(TsConfigService $tsConfigService)
    {
        $this->tsConfigService = $tsConfigService;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return ContentObjectRenderer
     */
    public function getContentObject()
    {
        return $this->contentObject;
    }

    /**
     * @return LoggingService
     */
    public function getLoggingService()
    {
        return $this->loggingService;
    }

    /**
     * @return TsConfigService
     */
    public function getTsConfigService()
    {
        return $this->tsConfigService;
    }

    /**
     * @return void
     */
    protected function initializeHookObjects()
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['communicationService'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['communicationService'] as $classReference) {
                $hookObject = GeneralUtility::getUserObj($classReference);

                // Hook objects have to implement interface
                if ($hookObject instanceof CommunicationServiceHookInterface) {
                    $this->hookObjects[] = $hookObject;
                }
            }
            unset($classReference);
        }
    }

    /**
     * @return bool
     */
    public function displayBackendMessage()
    {
        return ($this->configuration['loggingMode'] & LoggingService::MODE_DEBUG || $this->configuration['cacheControl'] === 'manual');
    }

    /**
     * Generates the flash messages for the requests
     *
     * @param array $resultArray
     * @param bool $wrapWithTags
     * @return string
     */
    public function generateBackendMessage(array $resultArray, $wrapWithTags = true)
    {
        $content = '';

        if (is_array($resultArray)) {
            foreach ($resultArray as $result) {
                $header = 'Server: ' . $result['server'] . ' // Host: ' . $result['host'];
                $message = 'Request: ' . $result['request'];
                switch ($result['status']) {
                    case Messaging\FlashMessage::OK:
                        $content .= 'top.TYPO3.Notification.success(
								"' . $header . '",
								"' . $message . '\nMessage: ' . $result['message'][0] . '",
								5
							);';
                        break;

                    default:
                        $content .= 'top.TYPO3.Notification.error(
								"' . $header . '",
								"' . $message . '\nMessage: ' . implode('\n', $result['message']) .
                            '\nSent:\n' . implode('\n', $result['requestHeader']) . '",
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
    public function storeBackendMessage(array $resultArray)
    {
        $content = '';

        if (is_array($resultArray)) {
            foreach ($resultArray as $result) {
                $header = 'Varnish Cache Control';
                $message = 'Server: ' . $result['server'] . ' // Host: ' . $result['host'] . '<br />Request: ' . $result['request'];
                switch ($result['status']) {
                    case Messaging\FlashMessage::OK:
                        $flashMessage = GeneralUtility::makeInstance(
                            Messaging\FlashMessage::class,
                            $message . '<br />Message: ' . $result['message'][0],
                            $header,
                            Messaging\FlashMessage::OK
                        );
                        break;

                    default:
                        $flashMessage = GeneralUtility::makeInstance(
                            Messaging\FlashMessage::class,
                            $message . '<br />Message: ' . implode('<br />', $result['message']) . '<br />Sent:<br />' . implode('<br />', $result['requestHeader']),
                            $header,
                            Messaging\FlashMessage::ERROR
                        );
                        break;
                }

                $flashMessage->setStoreInSession(true);
                /** @var $flashMessageService Messaging\FlashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(Messaging\FlashMessageService::class);
                /** @var $defaultFlashMessageQueue Messaging\FlashMessageQueue */
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
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
    public function sendClearCacheCommandForFiles($fileName, $host = '', $quote = true)
    {
        // Log debug information
        $logData = array(
            'fileName' => $fileName,
            'host' => $host,
        );
        $this->loggingService->debug('CommunicationService::sendClearCacheCommandForFiles arguments', $logData);

        // If no host was given get all
        if ($host === '') {
            $hostArray = array();

            // Get all domain records and check page access
            $domainArray = BackendUtility::getRecordsByField('sys_domain', 'redirectTo', '', ' AND hidden=0');
            if (is_array($domainArray) && !empty($domainArray)) {
                $permsClause = $GLOBALS['BE_USER']->getPagePermsClause(2);
                foreach ($domainArray as $domain) {
                    $pageinfo = BackendUtility::readPageAccess($domain['pid'], $permsClause);
                    if ($pageinfo !== false) {
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
    public function sendClearCacheCommandForTables($table, $uid, $host = '', $quote = true)
    {
        // Get current record to process
        $record = BackendUtility::getRecord($table, $uid);

        foreach ($this->hookObjects as $hookObject) {
            $params = array(
                'record' => &$record,
                'table' => $table,
                'uid' => $uid,
                'host' => $host,
            );
            /** @var CommunicationServiceHookInterface $hookObject */
            $hookObject->sendClearCacheCommandForTablesGetRecord($params, $this);
        }

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
            'pid' => $pid,
        );
        $this->loggingService->debug('CommunicationService::sendClearCacheCommandForTables arguments', $logData, $pid);

        if (!$this->createTSFE($pid)) {
            return array();
        }
        $tsConfig = $this->tsConfigService->getConfiguration($pid);
        $typolink = $tsConfig[$table . '.']['typolink.'];
        $this->contentObject->data = $record;

        $url = $this->contentObject->typoLink_URL($typolink);
        $LD = $this->contentObject->lastTypoLinkLD;

        // Check for root site
        if ($url === '' && $table === 'pages') {
            $rootline = BackendUtility::BEgetRootLine($uid);
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
                'status' => Messaging\FlashMessage::ERROR,
                'message' => array('No valid URL was generated.', 'table: ' . $table, 'uid: ' . $uid, 'host: ' . $host),
                'requestHeader' => array($url),
            ),
        );
    }

    /**
     * Load a faked frontend to be able to use stdWrap.typolink
     *
     * @param int $id
     * @return bool
     */
    protected function createTSFE($id)
    {
        if (!is_object($GLOBALS['TT'])) {
            $GLOBALS['TT'] = GeneralUtility::makeInstance(NullTimeTracker::class);
        }

        $TYPO3_CONF_VARS = $GLOBALS['TYPO3_CONF_VARS'];
        $TYPO3_CONF_VARS['FE']['pageNotFound_handling'] = false;
        $TYPO3_CONF_VARS['FE']['pageUnavailable_handling'] = false;
        $TYPO3_CONF_VARS['FE']['pageNotFoundOnCHashError'] = false;
        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(TypoScriptFrontendController::class, $TYPO3_CONF_VARS, $id, 0);
        $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(Page\PageRepository::class);
        try {
            $GLOBALS['TSFE']->initFEuser();
            $GLOBALS['TSFE']->initUserGroups();
            $GLOBALS['TSFE']->initTemplate();
            $GLOBALS['TSFE']->getPageAndRootline();
            $GLOBALS['TSFE']->getConfigArray();
            //$GLOBALS['TSFE']->tmpl->start($GLOBALS['TSFE']->sys_page->getRootline($id));
            if (TYPO3_MODE == 'BE') {
                // Set current backend language
                $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
                $pageRenderer->setLanguage($GLOBALS['LANG']->lang);
            }
            $GLOBALS['TSFE']->newcObj();

            Page\PageGenerator::pagegenInit();
        } catch (Http\PageNotFoundException $e) {
            return false;
        } catch (Http\ServiceUnavailableException $e) {
            return false;
        }

        return true;
    }

    /**
     * Processes the CURL request and sends action to Varnish server
     *
     * @param string $url
     * @param int $pageId
     * @param string $host
     * @param bool $quote
     * @return array
     */
    protected function processClearCacheCommand($url, $pageId, $host = '', $quote = true)
    {
        $responseArray = array();

        foreach ($this->hookObjects as $hookObject) {
            $params = array(
                'url' => &$url,
                'pageId' => $pageId,
                'host' => $host,
            );
            /** @var CommunicationServiceHookInterface $hookObject */
            $hookObject->processClearCacheCommandGetUrl($params, $this);
        }

        $serverArray = GeneralUtility::trimExplode(',', $this->configuration['server'], 1);
        foreach ($serverArray as $server) {
            $response = array(
                'server' => $server,
            );

            // Build request
            if ($this->configuration['stripSlash'] && $url !== '/') {
                $url = rtrim($url, '/');
            }
            $request = $server . '/' . ltrim($url, '/');
            $response['request'] = $request;

            // Check for curl functions
            if (!function_exists('curl_init')) {
                // TODO: Implement fallback to file_get_contents()
                $response['status'] = Messaging\FlashMessage::ERROR;
                $response['message'] = 'No curl_init available';
            } else {
                // If no host was given we need to loop over all
                $hostArray = array();
                if ($host !== '') {
                    $hostArray = GeneralUtility::trimExplode(',', $host, 1);
                } else {
                    // Get all (non-redirecting) domains from root
                    $rootLine = BackendUtility::BEgetRootLine($pageId);
                    foreach ($rootLine as $row) {
                        $domainArray = BackendUtility::getRecordsByField('sys_domain', 'pid', $row['uid'], ' AND redirectTo="" AND hidden=0');
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
                    $domain = rtrim(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), '/');
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

                    // Set X-Host header
                    $headerArray = array();
                    $headerArray[] = 'X-Host: ' . (($quote) ? preg_quote($xHost) : $xHost);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);

                    // Store outgoing header
                    curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

                    // Include preProcess hook (e.g. to set some alternative curl options
                    $params = array(
                        'host' => $xHost,
                        'pageId' => $pageId,
                        'quote' => $quote,
                        'server' => $server,
                        'url' => $url,
                    );
                    foreach ($this->hookObjects as $hookObject) {
                        /** @var CommunicationServiceHookInterface $hookObject */
                        $hookObject->processClearCacheCommandPreProcess($params, $ch, $request, $response, $this);
                    }
                    unset($hookObject);

                    $header = curl_exec($ch);
                    if (!curl_error($ch)) {
                        $response['status'] = (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) ? Messaging\FlashMessage::OK : Messaging\FlashMessage::ERROR;
                        $response['message'] = preg_split('/(\r|\n)+/m', trim($header));
                    } else {
                        $response['status'] = Messaging\FlashMessage::ERROR;
                        $response['message'] = array(curl_error($ch));
                    }
                    $response['requestHeader'] = preg_split('/(\r|\n)+/m', trim(curl_getinfo($ch, CURLINFO_HEADER_OUT)));

                    // Include postProcess hook (e.g. to start some other jobs)
                    foreach ($this->hookObjects as $hookObject) {
                        /** @var CommunicationServiceHookInterface $hookObject */
                        $hookObject->processClearCacheCommandPostProcess($params, $ch, $request, $response, $this);
                    }
                    unset($hookObject);

                    curl_close($ch);

                    // Log debug information
                    $logData = array(
                        'url' => $url,
                        'pageId' => $pageId,
                        'host' => $host,
                        'response' => $response,
                    );
                    $logType = ($response['status'] == Messaging\FlashMessage::OK) ? LoggingService::OK : LoggingService::ERROR;
                    $this->loggingService->log('CommunicationService::processClearCacheCommand', $logData, $logType, $pageId, 3);

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
    protected function removeHost($url)
    {
        if (strpos($url, '://') !== false) {
            $urlArray = parse_url($url);
            $url = substr($url, strlen($urlArray['scheme'] . '://' . $urlArray['host']));
        }

        return $url;
    }
}

?>