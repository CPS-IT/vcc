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

/**
 * Adds the interface for hook objects of the communicationService
 *
 * @author Nicole Cordes <cordes@cps-it.de>
 * @package TYPO3
 * @subpackage vcc
 */
interface CommunicationServiceHookInterface {

	/**
	 * Function to change the url before processing it
	 *
	 * @param array $params
	 * @param CommunicationService $pObj
	 * @return void
	 */
	public function processClearCacheCommandGetUrl($params, &$pObj);

	/**
	 * Function which is called before the request is sent to the server
	 *
	 * @param array $params
	 * @param resource $ch
	 * @param string $request
	 * @param array $response
	 * @param CommunicationService $pObj
	 * @return void
	 */
	public function processClearCacheCommandPreProcess($params, &$ch, &$request, &$response, &$pObj);

	/**
	 * Function which is called after the request was sent to the server and
	 * some response options were set
	 *
	 * @param array $params
	 * @param resource $ch
	 * @param string $request
	 * @param array $response
	 * @param CommunicationService $pObj
	 * @return void
	 */
	public function processClearCacheCommandPostProcess($params, &$ch, &$request, &$response, &$pObj);
}

?>