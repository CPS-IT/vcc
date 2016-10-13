<?php
namespace CPSIT\Vcc\Hooks;

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

use CPSIT\Vcc\Service\CommunicationService;
use CPSIT\Vcc\Service\TsConfigService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adds the cache clear button to the edit form
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 * @package TYPO3
 * @subpackage vcc
 */
abstract class AbstractVarnishHook
{

    /**
     * @var CommunicationService|NULL
     */
    protected $communicationService = null;

    /**
     * @var TsConfigService|NULL
     */
    protected $tsConfigService = null;

    /**
     * Initialize the object
     */
    public function __construct()
    {
        $communicationService = GeneralUtility::makeInstance(CommunicationService::class);
        $this->injectCommunicationService($communicationService);

        $tsConfigService = GeneralUtility::makeInstance(TsConfigService::class);
        $this->injectTsConfigService($tsConfigService);
    }

    /**
     * Injects the communication service
     *
     * @param \CPSIT\Vcc\Service\CommunicationService $communicationService
     * @return void
     */
    protected function injectCommunicationService(CommunicationService $communicationService)
    {
        $this->communicationService = $communicationService;
    }

    /**
     * Injects the TSConfig service
     *
     * @param \CPSIT\Vcc\Service\TsConfigService $tsConfigService
     * @return void
     */
    protected function injectTsConfigService(TsConfigService $tsConfigService)
    {
        $this->tsConfigService = $tsConfigService;
    }

    /**
     * Checks if the button could be inserted
     *
     * @param int $pageId
     * @param string $table
     * @return bool
     */
    protected function isHookAccessible($pageId, $table)
    {
        $access = false;

        // Check edit rights for page as cache can be flushed then only
        if ($table === 'pages') {
            $permsClause = $GLOBALS['BE_USER']->getPagePermsClause(2);
        } else {
            $permsClause = $GLOBALS['BE_USER']->getPagePermsClause(16);
        }
        $pageinfo = BackendUtility::readPageAccess($pageId, $permsClause);
        if ($pageinfo !== false) {
            // Get TSconfig for extension
            $tsConfig = $this->tsConfigService->getConfiguration($pageId);
            if (isset($tsConfig[$table]) && !empty($tsConfig[$table])) {
                $access = true;
            }
        }

        return $access;
    }

    /**
     * @param string $name
     * @param array $resultArray
     * @return void
     */
    protected function attachResultArrayToPageRenderer($name, $resultArray)
    {
        $message = $this->communicationService->generateBackendMessage($resultArray, false);
        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsInlineCode($name, $message);
    }
}

?>