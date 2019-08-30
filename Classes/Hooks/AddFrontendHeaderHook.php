<?php
namespace CPSIT\Vcc\Hooks;

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

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Adds additional header information for Varnish cache processing to the current page request
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 */
class AddFrontendHeaderHook
{
    /**
     * @var array
     */
    protected $extensionConfiguration;

    public function __construct(array $extensionConfiguration = null)
    {
        $this->extensionConfiguration = $extensionConfiguration ?: unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['vcc']);
    }

    public function addHeader(array $params, TypoScriptFrontendController $parentObject)
    {
        if (!empty($parentObject->id)) {
            header($this->extensionConfiguration['pidHeader'] . ': ' . $parentObject->id);
        }
    }
}