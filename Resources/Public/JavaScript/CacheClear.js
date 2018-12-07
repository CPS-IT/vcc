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
 * Module: TYPO3/CMS/Vcc/CacheClear
 * Class to handle the vcc_plus toolbar menu item to clear Varnish cache on demand
 */
define(['jquery', 'TYPO3/CMS/Backend/Icons'], function($, Icons) {
    'use strict';

    var containerSelector = '.tx-vcc-menu';

    $(function() {
        $(containerSelector).keyup(function(event) {
            if (event.keyCode === 13 || event.which === 13) {
                var $toolbarInput = $('input', containerSelector);
                var value = $toolbarInput.val();
                if (value.trim() !== '') {

                    var $container = $(containerSelector);
                    var $toolbarItemIcon = $('.icon', $container),
                        $existingIcon = $toolbarItemIcon.clone();

                    $container.removeClass('open');

                    Icons.getIcon('spinner-circle-light', Icons.sizes.small).done(function(spinner) {
                        $toolbarItemIcon.replaceWith(spinner);
                    });

                    // Call Ajax action
                    $.ajax({
                        url: TYPO3.settings.ajaxUrls['vcc_varnish_cache_clear'],
                        data: {
                            'path': value
                        },
                        method: 'GET',
                        async: false,
                        complete: function(jqXHR) {
                            var response = JSON.parse(jqXHR.responseText);
                            if (response.result) {
                                $(containerSelector).append(response.result);
                            }
                            $toolbarInput.val('');
                            $('.icon', $container).replaceWith($existingIcon);
                        }
                    });
                }
            }
        });
    });

});
