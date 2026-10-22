<?php

namespace Modules\Sviat\BlackBox;

use PHPUnit\Framework\TestCase;

/**
 * Шаблони модуля мусять рендеритись без повідомлень і на Smarty 5 (форк), і
 * на Smarty 4 (сток). Модифікатор implode у Smarty 5 завжди дає deprecation, а
 * join зі звичним порядком «масив, роздільник» у Smarty 4 — це PHP-функція
 * join(), яка на PHP 8 з таким порядком падає. Списки виводяться через foreach.
 */
class TemplateModifiersTest extends TestCase
{
    public function testNoImplodeOrJoinModifierInTemplates(): void
    {
        $templates = glob(dirname(__DIR__, 4) . '/Okay/Modules/Sviat/BlackBox/Backend/design/html/*.tpl');
        $this->assertNotEmpty($templates, 'шаблони не знайшлися — зламався сам пошук');

        $found = [];
        foreach ($templates as $template) {
            if (preg_match_all('/\|\s*(implode|join)\b/', file_get_contents($template), $m)) {
                $found[] = basename($template) . ': ' . implode(', ', $m[0]);
            }
        }

        $this->assertSame([], $found);
    }
}
