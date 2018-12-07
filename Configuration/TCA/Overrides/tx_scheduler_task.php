<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(function () {
    if (!isset($GLOBALS['TCA']['tx_scheduler_task'])) {
        $GLOBALS['TCA']['tx_scheduler_task'] = [];
    }

    $GLOBALS['TCA']['tx_scheduler_task'] = array_merge(
        $GLOBALS['TCA']['tx_scheduler_task'],
        [
            'ctrl' => [
                'hideTable' => true,
            ],
            'columns' => [
                'tx_vcc_pages' => [
                    'config' => [
                        'type' => 'group',
                        'internal_type' => 'db',
                        'allowed' => 'pages',
                        'size' => 3,
                        'maxitems' => 50,
                        'minitems' => 0,
                        'show_thumbs' => 0,
                    ],
                ],
                'tx_vcc_plugins' => [
                    'config' => [
                        'type' => 'select',
                        'renderType' => 'selectCheckBox',
                        'items' => [],
                        'size' => 5,
                        'maxitems' => 100,
                        'autoSizeMax' => 50,
                    ],
                    'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.startingpoint',
                ],
                'tx_vcc_depth' => [
                    'config' => [
                        'type' => 'select',
                        'renderType' => 'selectSingle',
                        'items' => [
                            [
                                'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.0',
                                0,
                            ],
                            [
                                'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.1',
                                1,
                            ],
                            [
                                'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.2',
                                2,
                            ],
                            [
                                'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.3',
                                3,
                            ],
                            [
                                'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.4',
                                4,
                            ],
                            [
                                'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.5',
                                250,
                            ],
                        ],
                        'default' => 0,
                    ],
                ],
                'tx_vcc_access' => [
                    'config' => [
                        'type' => 'check',
                        'items' => [
                            [
                                'LLL:EXT:lang/locallang_core.xlf:labels.enabled',
                                '',
                            ],
                        ],
                    ],
                ],
                'tx_vcc_deleted' => [
                    'config' => [
                        'type' => 'check',
                        'items' => [
                            [
                                'LLL:EXT:lang/locallang_core.xlf:labels.enabled',
                                '',
                            ],
                        ],
                    ],
                ],
                'tx_vcc_language' => [
                    'config' => [
                        'type' => 'check',
                        'items' => [
                            [
                                'LLL:EXT:lang/locallang_core.xlf:labels.enabled',
                                '',
                            ],
                        ],
                    ],
                ],
                'tx_vcc_hosts' => [
                    'config' => [
                        'type' => 'input',
                        'size' => 50,
                        'max' => 255,
                    ],
                ],
                'tx_vcc_email' => [
                    'config' => [
                        'type' => 'input',
                        'size' => 50,
                        'max' => 255,
                    ],
                ],
            ],
        ]
    );
});
