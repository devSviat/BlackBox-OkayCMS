<?php


namespace Okay\Modules\Sviat\BlackBox\Entities;

use Okay\Core\Entity\Entity;

class BlackBoxCacheEntity extends Entity
{
    protected static $fields = [
        'id',
        'phone',
        'last_name',
        'status',
        'payload',
        'updated_at',
    ];

    protected static $defaultOrderFields = ['id'];
    protected static $table = 'sviat__blackbox_cache';
    protected static $tableAlias = 'bbc';
}


