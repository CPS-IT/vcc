<?php
namespace CPSIT\Vcc\Hooks;

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
use CPSIT\Vcc\Renderer\IntScriptRenderer;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class ContentPostProcessHook
{
    /**
     * @var ApplicationContext
     */
    protected $applicationContext;

    /**
     * @var FrontendInterface
     */
    protected $esiCache;

    /**
     * @var HashService
     */
    protected $hashService;

    /**
     * @var IntScriptRenderer
     */
    protected $intScriptRenderer;

    /**
     * @var TypoScriptFrontendController
     */
    protected $typoScriptFrontendController;

    public function __construct(
        ApplicationContext $applicationContext = null,
        FrontendInterface $esiCache = null,
        HashService $hashService = null,
        IntScriptRenderer $intScriptRenderer = null
    ) {
        $this->applicationContext = $applicationContext ?: Bootstrap::getInstance()->getApplicationContext();
        $this->esiCache = $esiCache ?: GeneralUtility::makeInstance(CacheManager::class)->getCache('tx_vcc_esi');
        $this->hashService = $hashService ?: GeneralUtility::makeInstance(HashService::class);
        $this->intScriptRenderer = $intScriptRenderer ?: GeneralUtility::makeInstance(IntScriptRenderer::class);
    }

    public function replaceIntScripts(array $parameter)
    {
        $this->typoScriptFrontendController = $parameter['pObj'];
        if (empty($this->typoScriptFrontendController->config['INTincScript'])) {
            return;
        }

        if (empty($this->typoScriptFrontendController->tmpl->setup['plugin.']['tx_vcc.']['settings.']['typeNum'])) {
            throw new Exception('Page TypeNum for ESI rendering must be set', 1538651331);
        }

        $cacheTag = 'newHash_' . $this->typoScriptFrontendController->newHash;
        $this->esiCache->flushByTag($cacheTag);

        $content = $this->typoScriptFrontendController->content;
        $contentObjectRenderer = $this->typoScriptFrontendController->cObj;
        foreach ($this->typoScriptFrontendController->config['INTincScript'] as $identifier => $configuration) {
            $matches = [];
            if (preg_match('/<!--\s*' . preg_quote($identifier) . '\s*-->/i', $content, $matches)) {
                $cacheIdentifier = md5(json_encode($configuration) . $cacheTag);
                $this->esiCache->set($cacheIdentifier, $configuration, [$cacheTag]);

                $addQueryStringMethod = $this->typoScriptFrontendController->cHash ? 'GET' : '';
                $link = $contentObjectRenderer->typoLink_URL(
                    [
                        'parameter' => implode(',', [
                            $this->typoScriptFrontendController->id,
                            $this->typoScriptFrontendController->tmpl->setup['plugin.']['tx_vcc.']['settings.']['typeNum'],
                        ]),
                        'forceAbsoluteUrl' => 1,
                        'addQueryString.' => [
                            'method' => $addQueryStringMethod,
                        ],
                        'additionalParams' => '&tx_vcc[identifier]=' . $this->hashService->appendHmac($cacheIdentifier),
                        'useCacheHash' => 1,
                    ]
                );
                $esiContent = '<!--esi <esi:include src="' . $link . '" />-->';
                if (!GeneralUtility::getIndpEnv('TYPO3_REV_PROXY') && $this->applicationContext->isDevelopment()) {
                    $esiContent .= '<esi:remove>' . $this->intScriptRenderer->render($configuration) . '</esi:remove>';
                }
                $content = str_replace($matches[0], $esiContent, $content);
            }
            unset($this->typoScriptFrontendController->config['INTincScript'][$identifier]);
        }

        $this->typoScriptFrontendController->content = $content;

        if (empty($this->typoScriptFrontendController->config['INTincScript'])) {
            // Add additional header parts
            $this->typoScriptFrontendController->INTincScript();
            unset($this->typoScriptFrontendController->config['INTincScript']);
        }
    }
}
