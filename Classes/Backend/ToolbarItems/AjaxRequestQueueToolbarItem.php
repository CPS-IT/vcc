<?php
namespace CPSIT\Vcc\Backend\ToolbarItems;

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

use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Toolbar item to show ajax request for cache invalidation
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 */
class AjaxRequestQueueToolbarItem implements ToolbarItemInterface
{
    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    public function __construct(
        IconFactory $iconFactory = null,
        PageRenderer $pageRenderer = null
    ) {
        $this->iconFactory = $iconFactory ?: GeneralUtility::makeInstance(IconFactory::class);
        $this->pageRenderer = $pageRenderer ?: GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Checks whether the user has access to this toolbar item
     *
     * @return bool
     */
    public function checkAccess()
    {
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vcc']);

        return !empty($extensionConfiguration['ajaxRequestQueue']) && $extensionConfiguration['cacheControl'] === 'automatic';
    }

    /**
     * Render "item" part of this toolbar
     *
     * @return string
     */
    public function getItem()
    {
        $pathInfo = PathUtility::pathinfo(GeneralUtility::getFileAbsFileName('EXT:vcc/Resources/Public/Css/ToolbarItems.css'));
        $this->pageRenderer->addCssFile(PathUtility::getRelativePathTo($pathInfo['dirname']) . $pathInfo['basename']);
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Vcc/AjaxRequestQueue');

        return '<span title="Varnish cache cleared">'
            . $this->iconFactory->getIcon('vcc-ajax-request-queue-cleared', Icon::SIZE_SMALL)->render()
            . '</span>';
    }

    /**
     * TRUE if this toolbar item has a collapsible drop down
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return false;
    }

    /**
     * Render "drop down" part of this toolbar
     *
     * @return string Drop down HTML
     */
    public function getDropDown()
    {
        return '';
    }

    /**
     * Returns an array with additional attributes added to containing <li> tag of the item.
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        return [
            'class' => 'tx-vcc-ajaxRequestQueue',
        ];
    }

    /**
     * Returns an integer between 0 and 100 to determine the position of this item relative to others
     *
     * @return int
     */
    public function getIndex()
    {
        return 80;
    }
}
