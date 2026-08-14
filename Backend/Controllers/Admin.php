<?php

namespace Okay\Modules\Sviat\BlackBox\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Modules\Sviat\BlackBox\Extenders\BackendExtender;

class Admin extends IndexAdmin
{
    public function fetch()
    {
        if ($this->request->method('POST')) {
            // Обробка збереження API-ключа
            if ($this->request->post('blackbox_api_key')) {
                $this->settings->set('blackbox_api_key', $this->request->post('blackbox_api_key'));
                $this->design->assign('message_success', 'saved');
            }
        }

        $this->response->setContent($this->design->fetch('admin.tpl'));
    }
}
