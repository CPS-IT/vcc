<?php
namespace CPSIT\Vcc\Service;

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

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Service to handle extension settings
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 */
class ExtensionSettingService implements SingletonInterface
{
    const extensionKey = 'vcc';

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * Initialize the object
     */
    public function __construct()
    {
        $this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][self::extensionKey]);
    }

    /**
     * Returns the configuration
     *
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
}
