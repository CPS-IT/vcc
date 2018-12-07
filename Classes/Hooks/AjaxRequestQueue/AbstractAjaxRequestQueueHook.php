<?php
namespace CPSIT\Vcc\Hooks\AjaxRequestQueue;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adds the cache clear button to the edit form
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 */
abstract class AbstractAjaxRequestQueueHook
{
    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;

    /**
     * @var string
     */
    protected $sessionIdentifier = 'vcc.ajaxRequestQueue';

    public function __construct(BackendUserAuthentication $backendUser = null)
    {
        $this->backendUser = $backendUser ?: $GLOBALS['BE_USER'];
    }

    /**
     * @param string $cacheCmd
     * @return void
     */
    protected function pushAjaxRequestQueueItem($cacheCmd)
    {
        $code = 'top.TYPO3.VccAjaxRequestQueue.push(' . GeneralUtility::quoteJSvalue($cacheCmd) . ');';
        $sessionData = (array)$this->backendUser->getSessionData($this->sessionIdentifier);
        $sessionData[$cacheCmd] = $code;
        $this->backendUser->setAndSaveSessionData($this->sessionIdentifier, $sessionData);
    }
}
