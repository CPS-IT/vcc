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

use TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;

/**
 * Class for scheduler task
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 */
abstract class AbstractAdditionalFieldsClass extends AbstractItemProvider implements AdditionalFieldProviderInterface
{
    protected function getProcessedTca($table)
    {
        if (empty($GLOBALS['TCA'][$table]['columns'])) {
            return [];
        }

        $processedTca = $GLOBALS['TCA'][$table];
        foreach ($processedTca['columns'] as $field => &$fieldConfiguration) {
            if (empty($fieldConfiguration['config']['items'])) {
                continue;
            }

            $fieldConfiguration['config']['items'] = $this->translateLabels([], $fieldConfiguration['config']['items'], $table, $field);
        }

        return $processedTca;
    }

    /**
     * Adds the record title to each element
     *
     * @param array|string $itemsArray
     * @return array
     */
    protected function prepareGroupData($itemsArray)
    {
        if (!is_array($itemsArray)) {
            $itemsArray = GeneralUtility::trimExplode(',', $itemsArray, true);
        }

        $newItemsArray = [];
        foreach ($itemsArray as $item) {
            list($table, $uid) = BackendUtility::splitTable_Uid($item);
            if (MathUtility::canBeInterpretedAsInteger($uid) && empty($table)) {
                $table = 'pages';
            }
            $row = BackendUtility::getRecord($table, $uid);
            $newItemsArray[] = [
                'table' => $table,
                'uid' => $uid,
                'title' => BackendUtility::getRecordTitle($table, $row, true),
            ];
        }

        if (version_compare(TYPO3_version, '8.7.0', '<')) {
            $newItemsArray = implode(',', array_map(function ($item) {
                return $item['table'] . '_' . $item['uid'] . '|' . str_replace(',', '', $item['title']);
            }, $newItemsArray));
        }

        return $newItemsArray;
    }

    /**
     * Strips the table from each element
     *
     * @param string $data
     * @return string
     */
    protected function stripTableFromData($data)
    {
        $pagesArray = GeneralUtility::trimExplode(',', $data, true);

        $newPagesArray = [];
        foreach ($pagesArray as $page) {
            list($table, $uid) = BackendUtility::splitTable_Uid($page);
            if ($table === 'pages') {
                if (is_array(BackendUtility::getRecord('pages', $uid, 'uid'))) {
                    $newPagesArray[] = $uid;
                }
            }
        }

        return implode(',', $newPagesArray);
    }
}
