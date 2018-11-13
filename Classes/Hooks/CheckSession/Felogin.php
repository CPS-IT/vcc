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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Checks if any rsa authentication session was started
 */
class Felogin implements SessionInUseInterface
{
    /**
     * @param bool $isSessionUsed
     * @param array $_params
     * @param TypoScriptFrontendController $typoScriptFrontendController
     * @param EndOfFrontendHook $pObj
     * @return bool
     */
    public function checkSessionIsInUse($isSessionUsed, $_params, $typoScriptFrontendController, $pObj)
    {
        if ($isSessionUsed) {
            return true;
        }

        // Check for enabled rsa authentication cookie
        if (!empty($_SESSION['tx_rsaauth_key'])) {
            $isSessionUsed = true;
        } else {
            $sessionName = session_name();
            if (isset($_COOKIE[$sessionName])) {
                // Get current session information
                $sessionParams = session_get_cookie_params();
                // If the cookie lifetime is set, use it:
                $cookieExpire = ($typoScriptFrontendController->fe_user->isRefreshTimeBasedCookie()) ?
                    $GLOBALS['EXEC_TIME'] - $typoScriptFrontendController->fe_user->lifetime : $GLOBALS['EXEC_TIME'] - 3600;
                setcookie(
                    session_name(),
                    '',
                    $cookieExpire,
                    $sessionParams['path'],
                    $sessionParams['domain'],
                    $sessionParams['secure'],
                    $sessionParams['httponly']
                );
            }
        }

        return $isSessionUsed;
    }
}
