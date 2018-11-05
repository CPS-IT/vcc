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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adds the cache clear button to the edit form
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 */
class ClearCacheIconHook extends AbstractVarnishHook
{
    /**
     * @var DocumentTemplate
     */
    protected $pObj = null;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @param array $params
     * @param ButtonBar $parentObject
     * @return array
     */
    public function addButton(array $params, ButtonBar $parentObject)
    {
        $moduleName = GeneralUtility::_GP('M');
        $route = GeneralUtility::_GP('route');
        if (!in_array($moduleName, ['web_layout', 'web_list'], true) && $route !== '/record/edit') {
            return $params['buttons'];
        }

        $table = '';
        $record = [];
        if (in_array($moduleName, ['web_layout', 'web_list'], true)) {
            $table = 'pages';
            $id = GeneralUtility::_GP('id');
            if (is_object($GLOBALS['SOBE']) && (int)$GLOBALS['SOBE']->current_sys_language) {
                $table = 'pages_language_overlay';
                $record = BackendUtility::getRecordsByField($table, 'pid', $id, ' AND ' . $table . '.sys_language_uid=' . (int)$GLOBALS['SOBE']->current_sys_language, '', '', '1');
                if (!empty($record) && is_array($record)) {
                    $record = $record[0];
                }
            } else {
                $record = [
                    'uid' => $id,
                    'pid' => $id,
                ];
            }
        } else {
            $editConf = GeneralUtility::_GP('edit');
            if (!empty($editConf) && is_array($editConf)) {
                // Finding the current table
                reset($editConf);
                $table = key($editConf);

                // Finding the first id and get the records pid
                reset($editConf[$table]);
                $recordUid = key($editConf[$table]);
                // If table is pages we need uid (as pid) to get TSconfig
                if ($table === 'pages') {
                    $record = [
                        'uid' => $recordUid,
                        'pid' => $recordUid,
                    ];
                } else {
                    $record = BackendUtility::getRecord($table, $recordUid, 'uid, pid');
                }
            }
        }

        if (isset($record['pid']) && $record['pid'] > 0) {
            if ($this->isHookAccessible($record['pid'], $table)) {
                // Process last request
                /** @var PageRenderer $pageRenderer */
                $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
                $pageRenderer->addJsInlineCode('vccProcessedVarnishRequest', $this->process($table, $record['uid']));

                $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
                $varnishButton = $parentObject->makeLinkButton()
                    ->setIcon($iconFactory->getIcon('vcc-clearVarnishCache', Icon::SIZE_SMALL))
                    ->setTitle('Clear Varnish cache');
                if (!empty($moduleName)) {
                    $varnishButton->setHref(BackendUtility::getModuleUrl($moduleName, [
                        'id' => $record['pid'],
                        'processVarnishRequest' => 1,
                    ]));
                } else {
                    $varnishButton->setHref(BackendUtility::getModuleUrl('record_edit', [
                        'edit' => GeneralUtility::_GP('edit'),
                        'returnUrl' => GeneralUtility::_GP('returnUrl'),
                        'processVarnishRequest' => 1,
                    ]));
                }
                $params['buttons']['left'][99][] = $varnishButton;
            }
        }

        return $params['buttons'];
    }

    /**
     * Evaluate request and send clear cache commands
     *
     * @param string $table
     * @param int $uid
     * @return string
     */
    protected function process($table, $uid)
    {
        $string = '';
        if (GeneralUtility::_GP('processVarnishRequest')) {
            $resultArray = $this->communicationService->sendClearCacheCommandForTables($table, $uid);
            $string = $this->communicationService->generateBackendMessage($resultArray, false);
        }

        return $string;
    }
}
