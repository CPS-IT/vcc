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

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class IntScriptRenderer
{
    /**
     * @var ContentObjectRenderer
     */
    protected $contentObjectRenderer;

    /**
     * @param array $configuration
     * @return string
     */
    public function render(array $configuration)
    {
        if (empty($configuration['cObj']) || empty($configuration['type'])) {
            return '';
        }

        $this->contentObjectRenderer = unserialize($configuration['cObj']);
        if (!$this->contentObjectRenderer instanceof ContentObjectRenderer) {
            return '';
        }

        $content = '';
        switch ($configuration['type']) {
            case 'COA':
                $content = $this->contentObjectRenderer->cObjGetSingle('COA', $configuration['conf']);
                break;
            case 'FUNC':
                $content = $this->contentObjectRenderer->cObjGetSingle('USER', $configuration['conf']);
                break;
            case 'POSTUSERFUNC':
                $content = $this->contentObjectRenderer->callUserFunction($configuration['postUserFunc'], $configuration['conf'], $configuration['content']);
                break;
        }

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->setTemplateFile('EXT:vcc/Resources/Private/Templates/PageRenderer.html');
        $page = $pageRenderer->render(PageRenderer::PART_HEADER);
        $page .= $content;
        // The PageRenderer gets reset after render, so the template must be reassigned
        $pageRenderer->setTemplateFile('EXT:vcc/Resources/Private/Templates/PageRenderer.html');
        $page .= $pageRenderer->render(PageRenderer::PART_FOOTER);

        return $page;
    }
}
