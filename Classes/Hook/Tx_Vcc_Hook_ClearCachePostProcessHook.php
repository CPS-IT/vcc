<?php
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

/**
 * Clears caches after a record was saved
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 * @package TYPO3
 * @subpackage vcc
 */
class Tx_Vcc_Hook_ClearCachePostProcessHook extends Tx_Vcc_Hook_AbstractVarnishHook {

	/**
	 * @param array $params
	 * @param t3lib_TCEmain $parentObject
	 * @return void
	 */
	public function clearCacheByCommand($params, &$parentObject) {
		if (!empty($params['cacheCmd'])) {
			$resultArray = array();
			if (in_array(strtolower($params['cacheCmd']), array('all', 'pages'))) {
				$resultArray = $this->communicationService->sendClearCacheCommandForFiles('');
			} else {
				if (class_exists('t3lib_utility_Math')) {
					$isInt = t3lib_utility_Math::canBeInterpretedAsInteger($params['cacheCmd']);
				} else {
					$isInt = \TYPO3\CMS\Core\Utility\GeneralUtility::testInt($params['cacheCmd']);
				}
				if ($isInt) {
					$resultArray = $this->communicationService->sendClearCacheCommandForTables('pages', (int) $params['cacheCmd']);
				}
			}
			if ($this->communicationService->displayBackendMessage()) {
				$this->attachResultArrayToPageRenderer(
					'Tx_Vcc_Hook_ClearCachePostProcessHook_clearCacheByCommand_' . $params['cacheCmd'],
					$resultArray
				);
			}
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/vcc/Classes/Hook/Tx_Vcc_Hook_ClearCachePostProcessHook.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/vcc/Classes/Hooks/Tx_Vcc_Hook_ClearCachePostProcessHook.php']);
}

?>