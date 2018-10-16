<?php
namespace CPSIT\Vcc\Renderer;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Nicole Cordes <cordes@cps-it.de>
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

use CPSIT\Vcc\Exception\Exception;
use CPSIT\Vcc\Service\ExtensionSettingService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class EsiRenderer
{
    /**
     * @var FrontendInterface
     */
    protected $esiCache;

    /**
     * @var ExtensionSettingService
     */
    private $extensionSettingService;

    /**
     * @var IntScriptRenderer
     */
    protected $intScriptRenderer;

    /**
     * @var HashService
     */
    protected $hashService;

    /**
     * @var TypoScriptFrontendController
     */
    protected $typoScriptFrontendController;

    public function __construct(
        FrontendInterface $esiCache = null,
        ExtensionSettingService $extensionSettingService = null,
        HashService $hashService = null,
        IntScriptRenderer $intScriptRenderer = null,
        TypoScriptFrontendController $typoScriptFrontendController = null
    ) {
        $this->esiCache = $esiCache ?: GeneralUtility::makeInstance(CacheManager::class)->getCache('tx_vcc_esi');
        $this->extensionSettingService = $extensionSettingService ?: GeneralUtility::makeInstance(ExtensionSettingService::class);
        $this->intScriptRenderer = $intScriptRenderer ?: GeneralUtility::makeInstance(IntScriptRenderer::class);
        $this->hashService = $hashService ?: GeneralUtility::makeInstance(HashService::class);
        $this->typoScriptFrontendController = $typoScriptFrontendController ?: $GLOBALS['TSFE'];
    }

    public function render()
    {
        $arguments = GeneralUtility::_GET('tx_vcc');
        if (empty($arguments['identifier']) || !is_string($arguments['identifier'])) {
            throw new  Exception('Missing identifier', 1538659647);
        }

        $cacheIdentifier = $this->hashService->validateAndStripHmac($arguments['identifier']);

        $configuration = $this->esiCache->get($cacheIdentifier);

        if (empty($configuration)) {
            $this->setPageNotFound();

            return '';
        }

        if (!empty($configuration['conf']['cache_timeout'])) {
            $this->typoScriptFrontendController->page['cache_timeout'] = $configuration['conf']['cache_timeout'];
        } else {
            $this->typoScriptFrontendController->set_no_cache('ESI response is non-cacheable by default');
        }

        return $this->intScriptRenderer->render($configuration);
    }

    protected function setPageNotFound()
    {
        $configuration = $this->extensionSettingService->getConfiguration();
        $this->typoScriptFrontendController->set_no_cache('No ESI configuration found');
        if (empty($this->typoScriptFrontendController->config['config']['additionalHeaders.'])) {
            $this->typoScriptFrontendController->config['config']['additionalHeaders.'] = [];
        }
        $this->typoScriptFrontendController->config['config']['additionalHeaders.'] += [
            [
                'header' => $configuration['pageNotFoundHeader'],
                'httpResponseCode' => (int)$configuration['pageNotFoundCode'],
                'replace' => '1',
            ],
        ];
    }
}
