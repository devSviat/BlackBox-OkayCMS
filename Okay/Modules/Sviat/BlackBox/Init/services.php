<?php


use Okay\Core\Design;
use Okay\Core\Request;
use Okay\Core\Settings;
use Okay\Core\EntityFactory;
use Okay\Modules\Sviat\BlackBox\Extenders\BackendExtender;
use Okay\Modules\Sviat\BlackBox\Helpers\BlackBoxApiHelper;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;

return [
    BlackBoxApiHelper::class => [
        'class' => BlackBoxApiHelper::class,
        'arguments' => [
            new SR(Settings::class),
        ],
    ],
    BackendExtender::class => [
        'class' => BackendExtender::class,
        'arguments' => [
            new SR(Request::class),
            new SR(EntityFactory::class),
            new SR(Design::class),
            new SR(BlackBoxApiHelper::class),
        ],
    ],
];


