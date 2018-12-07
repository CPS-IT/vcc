<?php
namespace CPSIT\Vcc\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Nicole Cordes <cordes@cps-it.de>
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

use CPSIT\Vcc\Service\CommunicationService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Class for scheduler task
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 */
class ClearCachePages extends AbstractTask
{
    /**
     * @var int
     */
    public $tx_vcc_access = 0;

    /**
     * @var string
     */
    public $tx_vcc_email = '';

    /**
     * @var int
     */
    public $tx_vcc_deleted = 0;

    /**
     * @var int
     */
    public $tx_vcc_depth = 0;

    /**
     * @var string
     */
    public $tx_vcc_hosts = '';

    /**
     * @var int
     */
    public $tx_vcc_language = 0;

    /**
     * @var string
     */
    public $tx_vcc_pages = '';

    /**
     * @var CommunicationService
     */
    protected $communicationService = null;

    /**
     * Injects the communication service
     *
     * @param CommunicationService $communicationService
     * @return void
     */
    public function injectCommunicationService(CommunicationService $communicationService)
    {
        $this->communicationService = $communicationService;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        // Inject communication service
        $communicationService = GeneralUtility::makeInstance(CommunicationService::class);
        $this->injectCommunicationService($communicationService);

        // Send debug administration mail for executing task
        $this->sendAdministrationMail('The task with uid ' . $this->getTaskUid() . ' was started at ' . date('r', $this->getExecutionTime()));

        // Get all ids
        $pagesArray = GeneralUtility::trimExplode(',', $this->tx_vcc_pages, true);
        $pidListArray = [];
        foreach ($pagesArray as $page) {
            $pidListArray = $this->getTreeList($pidListArray, $page, $this->tx_vcc_depth, $this->tx_vcc_access, $this->tx_vcc_deleted);
        }
        unset($page);

        // Send debug administration mail with pages information
        $this->sendAdministrationMail('pidListArray: ' . implode(',', $pidListArray));

        // Clear cache for pages
        $resultArray = [];
        foreach ($pidListArray as $page) {
            $resultArray = array_merge_recursive(
                $resultArray,
                $this->communicationService->sendClearCacheCommandForTables('pages', $page, $this->tx_vcc_hosts)
            );

            // Clear cache for translations
            if ($this->tx_vcc_language) {
                $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                    'uid',
                    'pages_language_overlay',
                    'pid = ' . intval($page) .
                    (($this->tx_vcc_access) ? ' AND hidden = 0 AND starttime <= ' . $GLOBALS['SIM_ACCESS_TIME'] .
                        ' AND (endtime = 0 OR endtime > ' . $GLOBALS['SIM_ACCESS_TIME'] . ')' : '') .
                    ((!$this->tx_vcc_deleted) ? ' AND deleted = 0' : '')
                );

                if ($result !== false) {
                    while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
                        $resultArray = array_merge_recursive(
                            $resultArray,
                            $this->communicationService->sendClearCacheCommandForTables('pages_language_overlay', $row['uid'])
                        );
                    }
                    $GLOBALS['TYPO3_DB']->sql_free_result($result);
                }
            }
        }
        unset($page);

        // Send debug administration mail with result information
        $this->sendAdministrationMail(print_r($resultArray, true));

        return true;
    }

    protected function getTreeList($pidListArray, $id, $depth = 0, $checkAccess = 0, $includeDeleted = 0, $recursionLevel = 0)
    {
        if ($id) {
            // Check page
            $row = BackendUtility::getRecordRaw('pages', 'uid=' . intval($id));

            if ((!$checkAccess || (
                is_array($row) && $row['hidden'] == 0 && $row['nav_hide'] == 0 &&
                    $row['doktype'] < 200 && !in_array($row['doktype'], [PageRepository::DOKTYPE_BE_USER_SECTION, PageRepository::DOKTYPE_SPACER]) &&
                    $row['starttime'] <= $GLOBALS['SIM_ACCESS_TIME'] && ($row['endtime'] == 0 || $row['endtime'] > $GLOBALS['SIM_ACCESS_TIME']) &&
                    ($row['fe_group'] == '' || $row['fe_group'] == '0')
                )) && ($includeDeleted || $row['deleted'] == 0)
            ) {
                if ($depth > 0) {
                    $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                        'uid',
                        'pages',
                        'pid=' . intval($id) .
                            (($checkAccess) ? ' AND hidden = 0 AND nav_hide = 0' .
                                ' AND doktype < 200 AND doktype NOT IN (' .
                                implode(',', [PageRepository::DOKTYPE_BE_USER_SECTION, PageRepository::DOKTYPE_SPACER]) .
                                ') AND starttime <= ' . $GLOBALS['SIM_ACCESS_TIME'] .
                                ' AND (endtime = 0 OR endtime > ' . $GLOBALS['SIM_ACCESS_TIME'] . ')' .
                                ' AND (fe_group = "" OR fe_group = "0") ' : '') .
                            ((!$includeDeleted) ? ' AND deleted = 0' : ''),
                        '',
                        'sorting'
                    );

                    if ($result !== false) {
                        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
                            if (!in_array($row['uid'], $pidListArray)) {
                                $pidListArray = $this->getTreeList($pidListArray, $row['uid'], $depth - 1, $checkAccess, $includeDeleted, $recursionLevel + 1);
                            }
                            $pidListArray[] = $row['uid'];
                        }
                        $GLOBALS['TYPO3_DB']->sql_free_result($result);
                    }
                }

                if ($recursionLevel == 0) {
                    $pidListArray[] = $id;
                }
            }
        }

        return $pidListArray;
    }

    /**
     * @param string $message
     * @return int
     */
    protected function sendAdministrationMail($message)
    {
        if (!empty($this->tx_vcc_email)) {
            // Get call method
            if (basename(PATH_thisScript) == 'cli_dispatch.phpsh') {
                $calledBy = 'CLI module dispatcher';
                $site = 'n.a.';
            } else {
                $calledBy = 'TYPO3 backend (User: ' . $GLOBALS['BE_USER']->user['username'] . ')';
                $site = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
            }

            // Add task information
            $executionObject = $this->getExecution();
            $mailBody = $message . LF . LF .
                'TASK INFORMATION' . LF .
                '- - - - - - - - - - - - - - - -' . LF .
                'UID: ' . $this->getTaskUid() . LF .
                'Called by: ' . $calledBy . LF .
                'Sitename: ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . LF .
                'Site: ' . $site . LF .
                'Tstamp: ' . date('Y-m-d H:i:s') . ' [' . time() . ']' . LF .
                'MaxLifetime: ' . $this->scheduler->extConf['maxLifetime'] . LF .
                'Start: ' . date('Y-m-d H:i:s', $executionObject->getStart()) . ' [' . $executionObject->getStart() . ']' . LF .
                'End: ' . (($executionObject->getEnd()) ? date('Y-m-d H:i:s', $executionObject->getEnd()) . ' [' . $executionObject->getEnd() . ']' : 'n.a.') . LF .
                'Interval: ' . $executionObject->getInterval() . LF .
                'Multiple: ' . ($executionObject->getMultiple() ? 'yes' : 'no') . LF .
                'CronCmd: ' . ($executionObject->getCronCmd() ? $executionObject->getCronCmd() : 'n.a.');

            $mail = GeneralUtility::makeInstance(MailMessage::class);
            $mail->setFrom($this->tx_vcc_email);
            $mail->setTo($this->tx_vcc_email);
            $mail->setSubject('Administration information for \'Clear Varnish cache for pages\' task #' . $this->getTaskUid());
            $mail->setBody($mailBody);

            return $mail->send();
        }

        return 0;
    }
}
