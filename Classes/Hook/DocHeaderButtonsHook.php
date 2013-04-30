<?php
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

/**
 * Adds the cache clear button to the edit form
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 * @package TYPO3
 * @subpackage vcc
 */
class tx_vcc_hook_docHeaderButtonsHook {

	/**
	 * @var tx_vcc_service_communicationService|NULL
	 */
	var $communicationService = NULL;

	/**
	 * @var template|NULL
	 */
	var $pObj = NULL;

	/**
	 * @var array
	 */
	var $params = array();

	/**
	 * @var string
	 */
	var $permsClause = '';

	/**
	 * @var tx_vcc_service_tsConfigService|NULL
	 */
	var $tsConfigService = NULL;

	/**
	 * Initialize the object
	 *
	 * @return void
	 */
	public function __construct() {
		$communicationService = t3lib_div::makeInstance('tx_vcc_service_communicationService');
		$this->injectCommunicationService($communicationService);

		$tsConfigService = t3lib_div::makeInstance('tx_vcc_service_tsConfigService');
		$this->injectTsConfigService($tsConfigService);

		$this->permsClause = $GLOBALS['BE_USER']->getPagePermsClause(2);
	}

	/**
	 * Injects the communication service
	 *
	 * @param tx_vcc_service_communicationService $communicationService
	 *
	 * @return void
	 */
	public function injectCommunicationService(tx_vcc_service_communicationService $communicationService) {
		$this->communicationService = $communicationService;
	}

	/**
	 * Injects the TSConfig service
	 *
	 * @param tx_vcc_service_tsConfigService $tsConfigService
	 *
	 * @return void
	 */
	public function injectTsConfigService(tx_vcc_service_tsConfigService $tsConfigService) {
		$this->tsConfigService = $tsConfigService;
	}

	/**
	 * Checks access to the record and adds the clear cache button
	 *
	 * @param array $params
	 * @param template $pObj
	 *
	 * @return void
	 */
	public function addButton($params, $pObj) {
		$this->params = $params;
		$this->pObj = $pObj;

		$record = array();
		$table = '';

		// For web -> page view or web -> list view
		if ($this->pObj->scriptID === 'ext/cms/layout/db_layout.php' || $this->pObj->scriptID === 'ext/recordlist/mod1/index.php') {
			$id = t3lib_div::_GP('id');
			if (is_object($GLOBALS['SOBE']) && $GLOBALS['SOBE']->current_sys_language) {
				$table = 'pages_language_overlay';
				$record = t3lib_BEfunc::getRecordsByField($table, 'pid', $id, ' AND ' . $table . '.sys_language_uid=' . intval($GLOBALS['SOBE']->current_sys_language), '', '', '1');
				if (is_array($record) && !empty($record)) {
					$record = $record[0];
				}
			} else {
				$table = 'pages';
				$record = array(
					'uid' => $id,
					'pid' => $id
				);
			}
		} elseif ($this->pObj->scriptID === 'typo3/alt_doc.php') { // For record edit
			$editConf = t3lib_div::_GP('edit');
			if (is_array($editConf) && !empty($editConf)) {
				// Finding the current table
				reset($editConf);
				$table = key($editConf);

				// Finding the first id and get the records pid
				reset($editConf[$table]);
				$recordUid = key($editConf[$table]);
				// If table is pages we need uid (as pid) to get TSconfig
				if ($table === 'pages') {
					$record['uid'] = $recordUid;
					$record['pid'] = $recordUid;
				} else {
					$record = t3lib_BEfunc::getRecord($table, $recordUid, 'uid, pid');
				}
			}
		}

		if (isset($record['pid']) && $record['pid'] > 0) {
			if ($this->isModuleAccessible($record['pid'], $table)) {
				// Process last request
				$button = $this->process($table, $record['uid']);

				// Generate button with form for list view
				if ($this->pObj->scriptID === 'ext/recordlist/mod1/index.php') {
					$button .= $this->generateButton(TRUE);
				} else { // Generate plain input button
					$button .= $this->generateButton();
				}

				// Add button to button list and extend layout
				$this->params['buttons']['vcc'] = $button;
				$buttonWrap = t3lib_parsehtml::getSubpart($pObj->moduleTemplate, '###BUTTON_GROUP_WRAP###');
				$this->params['markers']['BUTTONLIST_LEFT'] .= t3lib_parsehtml::substituteMarker($buttonWrap, '###BUTTONS###', trim($button));
			}
		}
	}

	/**
	 * Returns the icon button on condition wrapped with a form
	 *
	 * @param boolean $wrapWithForm
	 *
	 * @return string
	 */
	protected function generateButton($wrapWithForm = FALSE) {
		$html = '<input type="image" class="c-inputButton" name="_clearvarnishcache" src="clear.gif" title="Clear Varnish cache" />';

		if ($wrapWithForm) {
			$html = '<form action="' . t3lib_div::getindpenv('REQUEST_URI') . '" method="post">' . $html . '</form>';
		}

		return t3lib_iconWorks::getSpriteIcon(
			'extensions-vcc-clearVarnishCache',
			array(
				'html' => $html
			)
		);
	}

	/**
	 * Checks if the button could be inserted
	 *
	 * @param integer $pageId
	 * @param string $table
	 *
	 * @return boolean
	 */
	protected function isModuleAccessible($pageId, $table) {
		$access = FALSE;

		// Check edit rights for page as cache can be flushed then only
		$pageinfo = t3lib_BEfunc::readPageAccess($pageId, $this->permsClause);
		if ($pageinfo !== FALSE) {
			// Get TSconfig for extension
			$tsConfig = $this->tsConfigService->getConfiguration($pageId);
			if (isset($tsConfig[$table]) && !empty($tsConfig[$table])) {
				$access = TRUE;
			}
		}

		return $access;
	}

	/**
	 * Evaluate request and send clear cache commands
	 *
	 * @param string $table
	 * @param integer $uid
	 *
	 * @return string
	 */
	protected function process($table, $uid) {
		$string = '';
		if (isset($_POST['_clearvarnishcache_x'])) {
			$resultArray = $this->communicationService->sendClearCacheCommandForTables($table, $uid);
			$string = $this->communicationService->generateBackendMessage($resultArray);
		}

		return $string;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/vcc/Classes/Hook/DocHeaderButtonsHook.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/vcc/Classes/Hooks/DocHeaderButtonsHook.php']);
}

?>