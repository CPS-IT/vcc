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
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Clears caches after a record was saved
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 */
class WriteJavascriptHook extends AbstractAjaxRequestQueueHook
{
    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    public function __construct(
        BackendUserAuthentication $backendUser = null,
        PageRenderer $pageRenderer = null
    ) {
        parent::__construct($backendUser);

        $this->pageRenderer = $pageRenderer ?: GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * @param array $params
     * @return void
     */
    public function addAjaxRequestQueueDataFromSession(&$params)
    {
        if (empty($params['jsInline'])
            || !empty($params['jsInline']['RequireJS-Module-TYPO3/CMS/Vcc/AjaxRequestQueue'])
        ) {
            return;
        }

        $sessionData = (array)$this->backendUser->getSessionData($this->sessionIdentifier);
        if (!empty($sessionData)) {
            $params['jsInline']['vcc'] = [
                'code' => implode(LF, $sessionData) . LF,
                'section' => PageRenderer::PART_HEADER,
                'compress' => true,
                'forceOnTop' => false,
            ];
            $this->backendUser->setAndSaveSessionData($this->sessionIdentifier, null);
        }
    }
}
