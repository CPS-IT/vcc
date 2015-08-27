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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adds the cache clear button to the edit form
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 * @package TYPO3
 * @subpackage vcc
 */
class ClearCacheIconHook extends AbstractVarnishHook {

	/**
	 * @var DocumentTemplate|NULL
	 */
	protected $pObj = NULL;

	/**
	 * @var array
	 */
	protected $params = array();

	/**
	 * Checks access to the record and adds the clear cache button
	 *
	 * @param array $params
	 * @param DocumentTemplate $pObj
	 * @return void
	 */
	public function addButton($params, $pObj) {
		$this->params = $params;
		$this->pObj = $pObj;

		$record = array();
		$table = '';

		// For web -> page view or web -> list view
		$module = GeneralUtility::_GP('M');
		if ($module === 'web_layout' || $module === 'web_list') {
			$id = GeneralUtility::_GP('id');
			if (is_object($GLOBALS['SOBE']) && $GLOBALS['SOBE']->current_sys_language) {
				$table = 'pages_language_overlay';
				$record = BackendUtility::getRecordsByField($table, 'pid', $id, ' AND ' . $table . '.sys_language_uid=' . intval($GLOBALS['SOBE']->current_sys_language), '', '', '1');
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
		} elseif (GeneralUtility::_GP('route') === '/record/edit') { // For record edit
			$editConf = GeneralUtility::_GP('edit');
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
					$record = BackendUtility::getRecord($table, $recordUid, 'uid, pid');
				}
			}
		}

		if (isset($record['pid']) && $record['pid'] > 0) {
			if ($this->isHookAccessible($record['pid'], $table)) {
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
				$buttonWrap = HtmlParser::getSubpart($pObj->moduleTemplate, '###BUTTON_GROUP_WRAP###');
				$this->params['markers']['BUTTONLIST_LEFT'] .= HtmlParser::substituteMarker($buttonWrap, '###BUTTONS###', trim($button));
			}
		}
	}

	/**
	 * Returns the icon button on condition wrapped with a form
	 *
	 * @param bool $wrapWithForm
	 * @return string
	 */
	protected function generateButton($wrapWithForm = FALSE) {
		$html = '<input type="image" class="c-inputButton" name="_clearvarnishcache" src="clear.gif" title="Clear Varnish cache" />';

		if ($wrapWithForm) {
			$html = '<form action="' . GeneralUtility::getindpenv('REQUEST_URI') . '" method="post">' . $html . '</form>';
		}

		return IconUtility::getSpriteIcon(
			'extensions-vcc-clearVarnishCache',
			array(
				'html' => $html
			)
		);
	}

	/**
	 * Evaluate request and send clear cache commands
	 *
	 * @param string $table
	 * @param int $uid
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

?>