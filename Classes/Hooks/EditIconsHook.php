<?php
namespace CPSIT\Vcc\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Nicole Cordes <cordes@cps-it.de>
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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist;

/**
 * Adds the cache clear button to the edit form
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 */
class EditIconsHook implements Filelist\FileListEditIconHookInterface
{
    /**
     * @var array
     */
    public $cells = [];

    /**
     * @var CommunicationService
     */
    public $communicationService;

    /**
     * @var Filelist\FileList
     */
    public $pObj;

    /**
     * Initialize the object
     *
     * @param CommunicationService $communicationService
     */
    public function __construct(CommunicationService $communicationService = null)
    {
        $this->communicationService = $communicationService ?: GeneralUtility::makeInstance(CommunicationService::class);
    }

    /**
     * Modifies the edit icon array
     *
     * @param array $cells
     * @param Filelist\FileList $parentObject
     *
     * @return void
     */
    public function manipulateEditIcons(&$cells, &$parentObject)
    {
        $this->cells = $cells;
        $this->pObj = $parentObject;
        $fileObject = $cells['__fileOrFolderObject'];

        // Only files can be cleared
        if (!$fileObject instanceof File) {
            return;
        }

        $allowFileExtensions = GeneralUtility::trimExplode(',', strtolower($GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace']['allow']), 1);
        $denyFileExtensions = GeneralUtility::trimExplode(',', strtolower($GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace']['deny']), 1);

        // Check allowed files
        // TODO: Extract list to module settings
        if ((empty($allowFileExtensions) || in_array($fileObject->getExtension(), $allowFileExtensions, true))
            && (empty($denyFileExtensions) || !in_array($fileObject->getExtension(), $denyFileExtensions, true))
        ) {
            // Build button
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $button = '';
            if (isset($_GET['_clear_varnish_cache']) && $_GET['_clear_varnish_cache'] === $fileObject->getIdentifier()) {
                $button .= $this->process($fileObject);
            }
            $href = $this->pObj->listURL() . '&_clear_varnish_cache=' . rawurlencode($fileObject->getIdentifier());
            $button .= $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '<a href="' . htmlspecialchars($href) . '" class="btn btn-default" title="Clear Varnish cache">' .
                $iconFactory->getIcon('vcc-clearVarnishCache', Icon::SIZE_SMALL)->render() . '</a>' . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render();
            $cells['vcc_filelist'] = $button;
        }
    }

    /**
     * Evaluate request and send clear cache commands
     *
     * @param File $fileObject
     * @return string
     */
    protected function process($fileObject)
    {
        $string = '';

        if ($fileObject->checkActionPermission('read')) {
            $resultArray = $this->communicationService->sendClearCacheCommandForFiles($fileObject->getPublicUrl());
            $string = $this->communicationService->generateBackendMessage($resultArray);
        }

        return $string;
    }
}
