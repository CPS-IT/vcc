<?php
namespace CPSIT\Vcc\Hooks\CheckSession;

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

use CPSIT\Vcc\Hooks\EndOfFrontendHook;
use CPSIT\Vcc\Hooks\SessionInUseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Checks if the captcha code can be deleted to free the cookie
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 */
class SrFreecap implements SessionInUseInterface
{
    /**
     * @var string
     */
    protected $sessionKey = 'tx_srfreecap';

    /**
     * @var array
     */
    protected $sessionData;

    /**
     * @var TypoScriptFrontendController
     */
    protected $typoScriptFrontendController;

    /**
     * @param bool $isSessionUsed
     * @param array $_params
     * @param TypoScriptFrontendController $typoScriptFrontendController
     * @param EndOfFrontendHook $pObj
     * @return bool
     */
    public function checkSessionIsInUse($isSessionUsed, $_params, $typoScriptFrontendController, $pObj)
    {
        if ($isSessionUsed
            || !isset($_params['sessionData'][$this->sessionKey])
        ) {
            return true;
        }

        $this->typoScriptFrontendController = $typoScriptFrontendController;
        $this->sessionData = &$_params['sessionData'];

        if (!empty($this->sessionData[$this->sessionKey])) {
            // Check if data can be removed
            if (!empty($this->sessionData[$this->sessionKey . '_vcc_pid_storage'])) {
                // Split pid list and compare with extension settings
                $pidList = GeneralUtility::intExplode(',', $this->sessionData[$this->sessionKey . '_vcc_pid_storage'], true);
                if (count($pidList) >= $_params['extConf']['srfreecapMoves']) {
                    // Unset cookie information
                    $this->destroySessionData();
                } else {
                    // Add current id to list
                    if (!in_array($typoScriptFrontendController->id, $pidList, true)) {
                        $pidList[] = $typoScriptFrontendController->id;
                        $this->sessionData[$this->sessionKey . '_vcc_pid_storage'] = implode(',', $pidList);
                        $typoScriptFrontendController->fe_user->setKey('ses', $this->sessionKey . '_vcc_pid_storage', $this->sessionData[$this->sessionKey . '_vcc_pid_storage']);
                    }
                    $isSessionUsed = true;
                }
            } elseif (empty($_params['extConf']['srfreecapMoves'])) {
                $this->destroySessionData();
            } else {
                // Add current pid to storage list
                $this->sessionData[$this->sessionKey]['_vcc_pid_storage'] = $typoScriptFrontendController->id;
                $typoScriptFrontendController->fe_user->setKey('ses', $this->sessionKey, $this->sessionData[$this->sessionKey]);
                $isSessionUsed = true;
            }
        } else {
            $this->destroySessionData();
        }

        return $isSessionUsed;
    }

    protected function destroySessionData()
    {
        $this->typoScriptFrontendController->fe_user->setKey('ses', $this->sessionKey, null);
        unset($this->sessionData[$this->sessionKey]);
        $this->typoScriptFrontendController->fe_user->setKey('ses', $this->sessionKey . '_vcc_pid_storage', null);
        unset($this->sessionData[$this->sessionKey . '_vcc_pid_storage']);
    }
}
