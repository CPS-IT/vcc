<?php
namespace CPSIT\Vcc\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Nicole Cordes <cordes@cps-it.de>
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

use CPSIT\Vcc\Service\CommunicationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class to handle backend ajax requests
 */
class AjaxRequest
{
    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;

    public function __construct(BackendUserAuthentication $backendUser = null)
    {
        $this->backendUser = $backendUser ?: $GLOBALS['BE_USER'];
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function clearCacheAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $queryParams = $request->getQueryParams();
        if (empty($queryParams['path'])) {
            return $response->withStatus(404);
        }

        $path = $queryParams['path'];
        $host = '';
        $scheme = @parse_url($path);
        if (isset($scheme['host'])) {
            $host = $scheme['host'];
            // Extract host from file
            $path = substr($path, strpos($path, $host) + strlen($host) + 1);
        }

        $communicationService = GeneralUtility::makeInstance(CommunicationService::class);
        $resultArray = $communicationService->sendClearCacheCommandForFiles($path, $host, false);

        $response->getBody()->write(json_encode(['result' => $communicationService->generateBackendMessage($resultArray)]));

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function processAjaxRequestQueueItemAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $queryParams = $request->getParsedBody();
        if (empty($queryParams['pageId'])) {
            return $response->withStatus(404);
        }

        $cacheCmd = $queryParams['pageId'];
        $communicationService = GeneralUtility::makeInstance(CommunicationService::class);
        if (in_array(strtolower($cacheCmd), ['all', 'pages'], true)) {
            $communicationService->sendClearCacheCommandForFiles('');
        } elseif (MathUtility::canBeInterpretedAsInteger($cacheCmd)) {
            // Check edit rights for page as cache can be flushed then only
            $pageInfo = BackendUtility::readPageAccess($cacheCmd, $this->backendUser->getPagePermsClause(2));
            if ($pageInfo !== false) {
                // Get TSconfig for extension
                $modTsConfig = BackendUtility::getModTSconfig($cacheCmd, 'mod.vcc');
                $tsConfig = $modTsConfig['properties'];
                if (isset($tsConfig['pages']) && !empty($tsConfig['pages'])) {
                    $communicationService->sendClearCacheCommandForTables('pages', $cacheCmd);
                }
            }
        }

        return $response;
    }
}
