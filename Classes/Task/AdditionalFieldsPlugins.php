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

use TYPO3\CMS\Backend\Form\Container\SingleFieldContainer;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Additional fields for clear Varnish cache by plugin type scheduler task
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 */
class AdditionalFieldsPlugins extends AbstractAdditionalFieldsClass
{
    /**
     * @var string
     */
    protected $uniqueIdentifier = 'NEW456';

    /**
     * @param array $taskInfo
     * @param AbstractTask $task
     * @param SchedulerModuleController $schedulerModule
     * @return array
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $additionalFields = [];

        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        if (!empty($GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'])) {
            $GLOBALS['TCA']['tx_scheduler_task']['columns']['tx_vcc_plugins']['config']['items'] = array_merge(
                $GLOBALS['TCA']['tx_scheduler_task']['columns']['tx_vcc_plugins']['config']['items'],
                array_filter(
                    $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'],
                    function ($item) {
                        return !empty($item[1]);
                    }
                )
            );
        }
        $processedTca = $this->getProcessedTca('tx_scheduler_task');

        // Define configuration for plugins
        $pluginsValue = [];
        if (isset($taskInfo['tx_vcc_plugins'])) {
            // Get records with label (table_uid|label)
            $pluginsValue = $taskInfo['tx_vcc_plugins'];
        } elseif ($task instanceof ClearCachePlugins) {
            $pluginsValue = $task->tx_vcc_plugins;
        }

        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'tx_vcc_plugins',
            'databaseRow' => [
                'uid' => $this->uniqueIdentifier,
                'tx_vcc_plugins' => $pluginsValue,
            ],
            'inlineStructure' => [],
            'processedTca' => $processedTca,
        ];

        // Use FormEngine for rendering
        $singleFieldContainer = GeneralUtility::makeInstance(SingleFieldContainer::class, $nodeFactory, $data);
        $resultArray = $singleFieldContainer->render();

        $formResultCompiler->mergeResult($resultArray);

        // Render plugins field
        $additionalFields['tx_vcc_plugins'] = [
            'code' => $resultArray['html'],
            'label' => 'LLL:EXT:vcc/Resources/Private/Language/locallang_be.xlf:task.plugins',
        ];

        // Define configuration for pages
        $pagesValue = '';
        if (isset($taskInfo['tx_vcc_pages'])) {
            // Get records with label (table_uid|label)
            $pagesValue = $taskInfo['tx_vcc_pages'];
        } elseif ($task instanceof ClearCachePlugins) {
            $pagesValue = $task->tx_vcc_pages;
        }

        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'tx_vcc_pages',
            'databaseRow' => [
                'uid' => $this->uniqueIdentifier,
                'tx_vcc_pages' => $this->prepareGroupData($pagesValue),
            ],
            'inlineStructure' => [],
            'processedTca' => $processedTca,
        ];

        // Use FormEngine for rendering
        $singleFieldContainer = GeneralUtility::makeInstance(SingleFieldContainer::class, $nodeFactory, $data);
        $resultArray = $singleFieldContainer->render();

        $formResultCompiler->mergeResult($resultArray);

        // Render pages field
        $additionalFields['tx_vcc_pages'] = [
            'code' => $resultArray['html'],
            'label' => 'LLL:EXT:vcc/Resources/Private/Language/locallang_be.xlf:task.pages',
        ];

        // Define configuration for recursive
        $depthValue = '';
        if (isset($taskInfo['tx_vcc_depth'])) {
            $depthValue = $taskInfo['tx_vcc_depth'];
        } elseif ($task instanceof ClearCachePlugins) {
            $depthValue = $task->tx_vcc_depth;
        }

        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'tx_vcc_depth',
            'databaseRow' => [
                'uid' => $this->uniqueIdentifier,
                'tx_vcc_depth' => (string)$depthValue,
            ],
            'inlineStructure' => [],
            'processedTca' => $processedTca,
        ];

        // Use FormEngine for rendering
        $singleFieldContainer = GeneralUtility::makeInstance(SingleFieldContainer::class, $nodeFactory, $data);
        $resultArray = $singleFieldContainer->render();

        $formResultCompiler->mergeResult($resultArray);

        // Render recursive field
        $additionalFields['tx_vcc_depth'] = [
            'code' => $resultArray['html'],
            'label' => 'LLL:EXT:vcc/Resources/Private/Language/locallang_be.xlf:task.depth',
        ];

        // Define configuration for access check
        $accessValue = '';
        if (isset($taskInfo['tx_vcc_access'])) {
            $accessValue = $taskInfo['tx_vcc_access'];
        } elseif ($task instanceof ClearCachePlugins) {
            $accessValue = $task->tx_vcc_access;
        }

        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'tx_vcc_access',
            'databaseRow' => [
                'uid' => $this->uniqueIdentifier,
                'tx_vcc_access' => (string)$accessValue,
            ],
            'inlineStructure' => [],
            'processedTca' => $processedTca,
        ];

        // Use FormEngine for rendering
        $singleFieldContainer = GeneralUtility::makeInstance(SingleFieldContainer::class, $nodeFactory, $data);
        $resultArray = $singleFieldContainer->render();

        $formResultCompiler->mergeResult($resultArray);

        // Render access field
        $additionalFields['tx_vcc_access'] = [
            'code' => str_replace('document.editform', 'document.tx_scheduler_form', $resultArray['html']),
            'label' => 'LLL:EXT:vcc/Resources/Private/Language/locallang_be.xlf:task.access',
        ];

        // Define configuration for deleted check
        $deletedValue = '';
        if (isset($taskInfo['tx_vcc_deleted'])) {
            $deletedValue = $taskInfo['tx_vcc_deleted'];
        } elseif ($task instanceof ClearCachePlugins) {
            $deletedValue = $task->tx_vcc_deleted;
        }

        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'tx_vcc_deleted',
            'databaseRow' => [
                'uid' => $this->uniqueIdentifier,
                'tx_vcc_deleted' => (string)$deletedValue,
            ],
            'inlineStructure' => [],
            'processedTca' => $processedTca,
        ];

        // Use FormEngine for rendering
        $singleFieldContainer = GeneralUtility::makeInstance(SingleFieldContainer::class, $nodeFactory, $data);
        $resultArray = $singleFieldContainer->render();

        $formResultCompiler->mergeResult($resultArray);

        // Render deleted field
        $additionalFields['tx_vcc_deleted'] = [
            'code' => str_replace('document.editform', 'document.tx_scheduler_form', $resultArray['html']),
            'label' => 'LLL:EXT:vcc/Resources/Private/Language/locallang_be.xlf:task.deleted',
        ];

        // Define configuration for hosts
        $hostsValue = '';
        if (isset($taskInfo['tx_vcc_hosts'])) {
            $hostsValue = $taskInfo['tx_vcc_hosts'];
        } elseif ($task instanceof ClearCachePlugins) {
            $hostsValue = $task->tx_vcc_hosts;
        }

        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'tx_vcc_hosts',
            'databaseRow' => [
                'uid' => $this->uniqueIdentifier,
                'tx_vcc_hosts' => $hostsValue,
            ],
            'inlineStructure' => [],
            'processedTca' => $processedTca,
        ];

        // Use FormEngine for rendering
        $singleFieldContainer = GeneralUtility::makeInstance(SingleFieldContainer::class, $nodeFactory, $data);
        $resultArray = $singleFieldContainer->render();

        $formResultCompiler->mergeResult($resultArray);

        // Render hosts field
        $additionalFields['tx_vcc_hosts'] = [
            'code' => $resultArray['html'],
            'label' => 'LLL:EXT:vcc/Resources/Private/Language/locallang_be.xlf:task.hosts',
        ];

        // Define configuration for admin email
        $adminMailValue = '';
        if (isset($taskInfo['tx_vcc_email'])) {
            $adminMailValue = $taskInfo['tx_vcc_email'];
        } elseif ($task instanceof ClearCachePlugins) {
            $adminMailValue = $task->tx_vcc_email;
        }

        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'tx_vcc_email',
            'databaseRow' => [
                'uid' => $this->uniqueIdentifier,
                'tx_vcc_email' => $adminMailValue,
            ],
            'inlineStructure' => [],
            'processedTca' => $processedTca,
        ];

        // Use FormEngine for rendering
        $singleFieldContainer = GeneralUtility::makeInstance(SingleFieldContainer::class, $nodeFactory, $data);
        $resultArray = $singleFieldContainer->render();

        $formResultCompiler->mergeResult($resultArray);

        $code = $formResultCompiler->JStop();
        $code .= $resultArray['html'];
        $code .= $formResultCompiler->printNeededJSFunctions();
        $pageRenderer->addInlineSetting('FormEngine', 'formName', 'tx_scheduler_form');

        // Render admin email field
        $additionalFields['tx_vcc_email'] = [
            'code' => $code,
            'label' => 'LLL:EXT:vcc/Resources/Private/Language/locallang_be.xlf:task.email',
        ];

        return $additionalFields;
    }

    /**
     * @param array $submittedData
     * @param ClearCachePlugins|AbstractTask $task
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->tx_vcc_plugins = $submittedData['tx_vcc_plugins'];
        $task->tx_vcc_pages = $this->stripTableFromData($submittedData['tx_vcc_pages']);
        $task->tx_vcc_depth = MathUtility::forceIntegerInRange($submittedData['tx_vcc_depth'], 0, 250);
        $task->tx_vcc_access = MathUtility::forceIntegerInRange($submittedData['tx_vcc_access'], 0, 1, 0);
        $task->tx_vcc_deleted = MathUtility::forceIntegerInRange($submittedData['tx_vcc_deleted'], 0, 1, 0);
        $task->tx_vcc_hosts = $submittedData['tx_vcc_hosts'];
        $task->tx_vcc_email = $submittedData['tx_vcc_email'];
    }

    /**
     * @param array $submittedData
     * @param SchedulerModuleController $schedulerModule
     * @return bool
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        // Validate pages
        $dataArray = GeneralUtility::_GP('data');
        if (empty($dataArray['tx_scheduler_task'][$this->uniqueIdentifier])) {
            return false;
        }

        $submittedData = array_merge($submittedData, $dataArray['tx_scheduler_task'][$this->uniqueIdentifier]);

        if (empty($submittedData['tx_vcc_plugins'])) {
            return false;
        }
        $listTypeArray = array_map(function ($item) {
            return !empty($item[1]) ? $item[1] : null;
        }, $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items']);
        foreach ($submittedData['tx_vcc_plugins'] as $plugin) {
            if (!in_array($plugin, $listTypeArray)) {
                return false;
            }
        }
        unset($plugin);

        // Validate pages
        $pagesArray = GeneralUtility::trimExplode(',', $submittedData['tx_vcc_pages'], true);
        foreach ($pagesArray as $page) {
            list($table, $uid) = explode('_', $page, 2);
            if ($table !== 'pages') {
                return false;
            }
            if (!is_array(BackendUtility::getRecordRaw('pages', 'uid=' . $uid))) {
                return false;
            }
        }
        unset($page);

        // Validate recursive
        if ($submittedData['tx_vcc_depth'] != MathUtility::forceIntegerInRange($submittedData['tx_vcc_depth'], 0, 250)) {
            return false;
        }

        if (!MathUtility::canBeInterpretedAsInteger($submittedData['tx_vcc_access'])) {
            return false;
        }

        if (!MathUtility::canBeInterpretedAsInteger($submittedData['tx_vcc_deleted'])) {
            return false;
        }

        if ($submittedData['tx_vcc_email'] !== '' && !GeneralUtility::validEmail($submittedData['tx_vcc_email'])) {
            return false;
        }

        return true;
    }
}
