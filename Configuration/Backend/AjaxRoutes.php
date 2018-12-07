<?php

return [
    'vcc_varnish_cache_clear' => [
        'path' => '/vcc/cache/clear',
        'target' => \CPSIT\Vcc\Backend\AjaxRequest::class . '::clearCacheAction',
    ],
    'vcc_process_request_queue_item' => [
        'path' => '/vcc/request/queue-item',
        'target' => \CPSIT\Vcc\Backend\AjaxRequest::class . '::processAjaxRequestQueueItemAction',
    ],
];
