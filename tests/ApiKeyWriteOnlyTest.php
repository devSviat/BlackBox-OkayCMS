<?php

namespace Modules\Sviat\BlackBox;

use PHPUnit\Framework\TestCase;

/**
 * Сторінку модуля відкриває кожен менеджер з правом settings, тож API-ключ у
 * HTML не повертається. Порожнє поле при збереженні ключ не чіпає.
 */
class ApiKeyWriteOnlyTest extends TestCase
{
    public function testTheTemplateNeverPrintsTheKey(): void
    {
        $template = (string) file_get_contents(dirname(__DIR__, 4) . '/Okay/Modules/Sviat/BlackBox/Backend/design/html/admin.tpl');

        $this->assertStringNotContainsString('blackbox_api_key|', $template);
        $this->assertMatchesRegularExpression('~<input type="password" name="blackbox_api_key"[^>]*value=""~', $template);
    }
}
