/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Nicole Cordes <cordes@cps-it.de>
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
 * Module: TYPO3/CMS/Vcc/AjaxRequestQueue
 * Class to handle the vcc toolbar menu item to clear Varnish cache on demand
 * @exports TYPO3/CMS/Vcc/VccAjaxRequestQueue
 */
define(['jquery', 'TYPO3/CMS/Backend/Icons'], function($, Icons) {
    'use strict';

    var VccAjaxRequestQueue = {
        _active: false,
        _container: '#cpsit-vcc-backend-toolbaritems-ajaxrequestqueuetoolbaritem',
        _icon: undefined,
        _intervalId: 0,
        _queue: [],
        _queueCount: 0,
        _queueTotal: 0
    };

    VccAjaxRequestQueue.push = function(pageId) {
        if (this._queue.indexOf(pageId) === -1) {
            this._queue.push(pageId);
            this._queueCount++;
            this._queueTotal++;
            this.start();
        }
    };

    VccAjaxRequestQueue.start = function() {
        if (!this._active) {
            var $toolbarItemIcon = $('.t3js-icon', this._container);
            this._icon = $toolbarItemIcon.clone();

            Icons.getIcon('spinner-circle-light', Icons.sizes.small).done(function(spinner) {
                var $spinner = $(spinner);
                $spinner.prop('title', TYPO3.VccAjaxRequestQueue._queueCount + ' / ' + TYPO3.VccAjaxRequestQueue._queueTotal);
                $toolbarItemIcon.replaceWith($spinner[0].outerHTML);
            });

            this._intervalId = setInterval(this._processQueue, 200);
            this._active = true;
        }
    };

    VccAjaxRequestQueue._processQueue = function() {
        var maxConnections = Math.ceil(VccAjaxRequestQueue._queueTotal * 0.1);
        if ($.active < maxConnections && VccAjaxRequestQueue._queue.length > 0) {
            var pageId = VccAjaxRequestQueue._queue.shift();
            $.ajax({
                url: TYPO3.settings.ajaxUrls['vcc_process_request_queue_item'],
                data: {
                    'pageId': pageId
                },
                method: 'POST',
                async: false,
                complete: function(jqXHR) {
                    VccAjaxRequestQueue._update()
                }
            });
        }
    };

    VccAjaxRequestQueue._update = function() {
        this._queueCount--;
        var $toolbarItemIcon = $('.t3js-icon', this._container);
        if (this._queueCount > 0) {
            $toolbarItemIcon.prop('title', TYPO3.VccAjaxRequestQueue._queueCount + ' / ' + TYPO3.VccAjaxRequestQueue._queueTotal);
        } else {
            $toolbarItemIcon.replaceWith(this._icon);
            this._active = false;
            this._queueTotal = 0;
            clearInterval(this._intervalId);
        }
    };

    TYPO3.VccAjaxRequestQueue = VccAjaxRequestQueue;

    return VccAjaxRequestQueue;
});
