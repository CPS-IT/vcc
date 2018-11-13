<?php
namespace CPSIT\Vcc\Hooks;

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

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Checks if any session data was set
 */
class EndOfFrontendHook
{
    /**
     * @var TypoScriptFrontendController
     */
    public $typoScriptFrontendController = null;

    public function checkSessionIsInUse(array $_params, TypoScriptFrontendController $pObj)
    {
        $this->typoScriptFrontendController = $pObj;

        // Get extension configuration
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vcc']);

        // First check all hook objects as they might manipulate any entry
        $isSessionUsed = false;

        $sessionData = $this->getSessionData();

        // Enable hook to give possibility to change session handling
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['checkSession'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['checkSession'])
        ) {
            // Add extension configuration to parameter array
            $_params = [
                'extConf' => $extConf,
                'sessionData' => &$sessionData,
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['vcc']['hooks']['checkSession'] as $classReference) {
                $hookObject = GeneralUtility::getUserObj($classReference);

                // Hook objects have to implement interface
                if ($hookObject instanceof SessionInUseInterface) {
                    $isSessionUsed = $hookObject->checkSessionIsInUse($isSessionUsed, $_params, $this->typoScriptFrontendController, $this);
                }
            }
        }

        if ($isSessionUsed) {
            return;
        }

        // Check if solr is installed
        // Any solr request needs frontend cookie to work
        if (isset($_SERVER['HTTP_X_TX_SOLR_IQ'])
            && ExtensionManagementUtility::isLoaded('solr')
        ) {
            return;
        }

        // Check if any user is logged in
        if (!empty($this->typoScriptFrontendController->fe_user->user['uid'])) {
            return;
        }

        // Check session data
        if (!empty($sessionData)
            && is_array($sessionData)
        ) {
            // Get extension settings
            $preservedKeyArray = GeneralUtility::trimExplode(',', $extConf['keys'], true);
            foreach ($sessionData as $key => $value) {
                if (in_array($key, $preservedKeyArray, true)) {
                    return;
                }
                if (is_array($value)) {
                    if (!$this->isEmptyArray($value)) {
                        return;
                    }
                } elseif (!empty($value)) {
                    return;
                }
            }
        }

        if (!$isSessionUsed
            && (
                empty($this->typoScriptFrontendController->fe_user->dontSetCookie)
                || $_COOKIE[$this->typoScriptFrontendController->fe_user->name]
            )
        ) {
            // Remove session
            $this->typoScriptFrontendController->fe_user->removeSessionData();

            $settings = $GLOBALS['TYPO3_CONF_VARS']['SYS'];

            // Get the domain to be used for the cookie (if any):
            $cookieDomain = $this->getCookieDomain();
            // If no cookie domain is set, use the base path:
            $cookiePath = ($cookieDomain ? '/' : GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
            // If the cookie lifetime is set, use it:
            $cookieExpire = ($this->typoScriptFrontendController->fe_user->isRefreshTimeBasedCookie()) ?
                $GLOBALS['EXEC_TIME'] - $this->typoScriptFrontendController->fe_user->lifetime : $GLOBALS['EXEC_TIME'] - 3600;
            // Use the secure option when the current request is served by a secure connection:
            $cookieSecure = (bool)$settings['cookieSecure'] && GeneralUtility::getIndpEnv('TYPO3_SSL');
            // Deliver cookies only via HTTP and prevent possible XSS by JavaScript:
            $cookieHttpOnly = (bool)$settings['cookieHttpOnly'];

            // Unset cookie
            setcookie(
                $this->typoScriptFrontendController->fe_user->name,
                '',
                $cookieExpire,
                $cookiePath,
                $cookieDomain,
                $cookieSecure,
                $cookieHttpOnly
            );

            // Send X-Remove-Cookie header to Varnish
            header('X-Remove-Cookie: 1');
        }
    }

    protected function getSessionData()
    {
        if (property_exists($this->typoScriptFrontendController->fe_user, 'sesData')) {
            return $this->typoScriptFrontendController->fe_user->sesData;
        }

        $frontendUser = $this->typoScriptFrontendController->fe_user;
        $closure = \Closure::bind(function () use ($frontendUser) {
            return $frontendUser->sessionData;
        }, null, AbstractUserAuthentication::class);

        return $closure();
    }

    protected function getCookieDomain()
    {
        $result = '';
        $cookieDomain = $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'];
        // If a specific cookie domain is defined for a given TYPO3_MODE,
        // use that domain
        if (!empty($GLOBALS['TYPO3_CONF_VARS'][$this->typoScriptFrontendController->fe_user->loginType]['cookieDomain'])) {
            $cookieDomain = $GLOBALS['TYPO3_CONF_VARS'][$this->typoScriptFrontendController->fe_user->loginType]['cookieDomain'];
        }

        if (!empty($cookieDomain)) {
            if ($cookieDomain[0] === '/') {
                $match = [];
                if (@preg_match($cookieDomain, GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'), $match) === false) {
                    GeneralUtility::sysLog('The regular expression for the cookie domain (' . $cookieDomain . ') contains errors. The session is not shared across sub-domains.', 'Core', 3);
                } elseif (!empty($match)) {
                    $result = $match[0];
                }
            } else {
                $result = $cookieDomain;
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    protected function isEmptyArray(array $array)
    {
        $isEmpty = true;

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $isEmpty = $this->isEmptyArray($value);
            } else {
                $isEmpty = empty($value);
            }
            if (!$isEmpty) {
                break;
            }
        }

        return $isEmpty;
    }
}
