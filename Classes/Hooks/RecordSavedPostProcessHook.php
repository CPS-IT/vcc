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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Clears caches after a record was saved
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 * @package TYPO3
 * @subpackage vcc
 */
class RecordSavedPostProcessHook extends AbstractVarnishHook
{

    /**
     * @param DataHandler $parentObject
     * @return void
     */
    public function processDatamap_afterAllOperations(&$parentObject)
    {
        foreach ($parentObject->datamap as $table => $record) {
            $uid = key($record);
            if (isset($parentObject->substNEWwithIDs[$uid]) && ($table === 'pages' || $table === 'pages_language_overlay')) {
                continue;
            }
            $uid = isset($parentObject->substNEWwithIDs[$uid]) ? $parentObject->substNEWwithIDs[$uid] : $uid;
            if ($table === 'pages') {
                $pageId = $uid;
            } else {
                $pageId = $parentObject->getPID($table, $uid);
            }
            if ($this->isHookAccessible($pageId, $table)) {
                $resultArray = $this->communicationService->sendClearCacheCommandForTables($table, $uid, '', false);

                if ($this->communicationService->displayBackendMessage()) {
                    if (!isset($_POST['_saveandclosedok_x'])
                        && !isset($_POST['_translation_savedok_x'])
                        && GeneralUtility::_GP('closeDoc') == 0
                    ) {
                        $this->attachResultArrayToPageRenderer(
                            'RecordSavedPostProcessHook_processDatamap_afterAllOperations_' . $table . '_' . $uid,
                            $resultArray
                        );
                    } else {
                        $this->communicationService->storeBackendMessage($resultArray);
                    }
                }
            }
        }
        unset($table, $record);
    }
}

?>