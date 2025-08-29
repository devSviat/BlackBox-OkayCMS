<?php

namespace Okay\Modules\Sviat\BlackBox\Init;

use Okay\Modules\Sviat\BlackBox\Controllers\BlackBoxController;

return [
    'Sviat_BlackBox_update' => [
        'slug' => 'ajax/bb/update_client',
        'to_front' => true,
        'params' => [
            'controller' => BlackBoxController::class,
            'method' => 'updateClientInfo',
        ],
    ],

    'Sviat_BlackBox_add' => [
        'slug' => 'ajax/bb/add_client',
        'to_front' => true,
        'params' => [
            'controller' => BlackBoxController::class,
            'method' => 'addClientInfo',
        ],
    ]
];
