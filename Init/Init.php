<?php


namespace Okay\Modules\Sviat\BlackBox\Init;

use Okay\Admin\Helpers\BackendOrdersHelper;
use Okay\Core\Modules\AbstractInit;
use Okay\Core\Modules\EntityField;
use Okay\Modules\Sviat\BlackBox\Extenders\BackendExtender;
use Okay\Modules\Sviat\BlackBox\Entities\BlackBoxCacheEntity;


class Init extends AbstractInit
{
    public function install()
    {
        $this->setBackendMainController('Admin');
        $this->migrateEntityTable(BlackBoxCacheEntity::class, [
            (new EntityField('id'))->setTypeInt(11)->setAutoIncrement(),
            (new EntityField('phone'))->setTypeVarchar(32)->setNullable(),
            (new EntityField('last_name'))->setTypeVarchar(255)->setNullable(),
            (new EntityField('status'))->setTypeVarchar(32)->setNullable(),
            (new EntityField('payload'))->setTypeText()->setNullable(),
            (new EntityField('updated_at'))->setTypeDatetime()->setNullable(),
        ]);
    }

    public function init()
    {
        $this->registerBackendController('Admin');
        $this->addBackendControllerPermission('Admin', 'settings');

        $this->registerQueueExtension(
            [BackendOrdersHelper::class, 'findOrder'],
            [BackendExtender::class,     'getBlackBoxOrderInfo']
        );

        $this->registerQueueExtension(
            [BackendOrdersHelper::class, 'executeCustomPost'],
            [BackendExtender::class, 'updateBlackBoxOrderInfo']
        );
    }
}
 

