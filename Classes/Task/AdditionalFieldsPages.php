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
 * Class for scheduler task
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 */
class AdditionalFieldsPages extends AbstractAdditionalFieldsClass
{
    /**
     * @var string
     */
    protected $uniqueIdentifier = 'NEW123';

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

        $processedTca = $this->getProcessedTca('tx_scheduler_task');

        // Define configuration for pages
        $pagesValue = '';
        if (isset($taskInfo['tx_vcc_pages'])) {
            // Get records with label (table_uid|label)
            $pagesValue = $taskInfo['tx_vcc_pages'];
        } elseif ($task instanceof ClearCachePages) {
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
        $depthValue = 0;
        if (isset($taskInfo['tx_vcc_depth'])) {
            $depthValue = $taskInfo['tx_vcc_depth'];
        } elseif ($task instanceof ClearCachePages) {
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
        } elseif ($task instanceof ClearCachePages) {
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
        } elseif ($task instanceof ClearCachePages) {
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

        // Define configuration for translated pages
        $languageValue = '';
        if (isset($taskInfo['tx_vcc_language'])) {
            $languageValue = $taskInfo['tx_vcc_language'];
        } elseif ($task instanceof ClearCachePages) {
            $languageValue = $task->tx_vcc_language;
        }

        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'tx_vcc_language',
            'databaseRow' => [
                'uid' => $this->uniqueIdentifier,
                'tx_vcc_language' => (string)$languageValue,
            ],
            'inlineStructure' => [],
            'processedTca' => $processedTca,
        ];

        // Use FormEngine for rendering
        $singleFieldContainer = GeneralUtility::makeInstance(SingleFieldContainer::class, $nodeFactory, $data);
        $resultArray = $singleFieldContainer->render();

        $formResultCompiler->mergeResult($resultArray);

        // Render translation field
        $additionalFields['tx_vcc_language'] = [
            'code' => str_replace('document.editform', 'document.tx_scheduler_form', $resultArray['html']),
            'label' => 'LLL:EXT:vcc/Resources/Private/Language/locallang_be.xlf:task.language',
        ];

        // Define configuration for hosts
        $hostsValue = '';
        if (isset($taskInfo['tx_vcc_hosts'])) {
            $hostsValue = $taskInfo['tx_vcc_hosts'];
        } elseif ($task instanceof ClearCachePages) {
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
        $emailValue = '';
        if (isset($taskInfo['tx_vcc_email'])) {
            $emailValue = $taskInfo['tx_vcc_email'];
        } elseif ($task instanceof ClearCachePages) {
            $emailValue = $task->tx_vcc_email;
        }

        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'tx_vcc_email',
            'databaseRow' => [
                'uid' => $this->uniqueIdentifier,
                'tx_vcc_email' => $emailValue,
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
     * @param ClearCachePages|AbstractTask $task
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->tx_vcc_pages = $this->stripTableFromData($submittedData['tx_vcc_pages']);
        $task->tx_vcc_depth = MathUtility::forceIntegerInRange($submittedData['tx_vcc_depth'], 0, 250);
        $task->tx_vcc_access = MathUtility::forceIntegerInRange($submittedData['tx_vcc_access'], 0, 1, 0);
        $task->tx_vcc_deleted = MathUtility::forceIntegerInRange($submittedData['tx_vcc_deleted'], 0, 1, 0);
        $task->tx_vcc_language = MathUtility::forceIntegerInRange($submittedData['tx_vcc_language'], 0, 1, 0);
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

        $pagesArray = GeneralUtility::trimExplode(',', $submittedData['tx_vcc_pages'], true);
        foreach ($pagesArray as $page) {
            list($table, $uid) = BackendUtility::splitTable_Uid($page);
            if ($table !== 'pages') {
                return false;
            }
            if (!is_array(BackendUtility::getRecord('pages', $uid, 'uid'))) {
                return false;
            }
        }

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

        if (!MathUtility::canBeInterpretedAsInteger($submittedData['tx_vcc_language'])) {
            return false;
        }

        if ($submittedData['tx_vcc_email'] !== '' && !GeneralUtility::validEmail($submittedData['tx_vcc_email'])) {
            return false;
        }

        return true;
    }
}
