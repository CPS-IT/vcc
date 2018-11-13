<?php
namespace CPSIT\Vcc\Hooks\CheckSession;

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

use CPSIT\Vcc\Hooks\EndOfFrontendHook;
use CPSIT\Vcc\Hooks\SessionInUseInterface;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Checks if the current site has a powermail form
 */
class Powermail implements SessionInUseInterface
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

        if (!empty($_params['sessionData'])) {
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $databaseConnection = $this->getDatabaseConnection();
            $count = $databaseConnection->exec_SELECTcountRows(
                '*',
                'tt_content',
                'pid=' . (int)$typoScriptFrontendController->id
                . ' AND CType=' . $databaseConnection->fullQuoteStr('list', 'tt_content')
                . 'AND list_type=' . $databaseConnection->fullQuoteStr('powermail_pi1', 'tt_content')
                . $contentObject->enableFields('tt_content')
            );
            if (empty($count)) {
                $sessionData = $_params['sessionData'];
                foreach ($sessionData as $key => $value) {
                    if (strpos($key, 'powermail') === 0) {
                        $typoScriptFrontendController->fe_user->setKey('ses', $key, null);
                        unset($_params['sessionData'][$key]);
                    }
                }
            }
        }

        return $isSessionUsed;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
