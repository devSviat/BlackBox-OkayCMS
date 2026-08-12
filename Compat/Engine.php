<?php

namespace Okay\Modules\Sviat\BlackBox\Compat;

/**
 * Питання до рушія — тільки за можливостями, ніколи за номером версії.
 *
 * Номер для цього непридатний: і наш форк, і сток звуть себе "4.5.2"
 * (Okay\Core\Config::$version), тож перевірка на версію мовчки розповідала б
 * неправду. Надійно розрізняє рушії лише наявність конкретного класу, методу
 * чи сусіднього модуля.
 *
 * Шар навмисно порожній щодо самих обхідних шляхів: на момент написання
 * жоден з API, яких торкається модуль, між рушіями не розходиться. Тут лежить
 * спосіб питати — щоб перша ж реальна різниця мала куди лягти, а не
 * розповзлася по коду перевірками на версію.
 */
final class Engine
{
    /**
     * Мажорна версія Smarty: 5 у форку, 3 у стоці, 0 якщо шаблонізатор ще не
     * завантажений. Smarty 5 живе в неймспейсі, Smarty 3 — у корені; це
     * найдешевша однозначна ознака рушія.
     */
    public static function smartyMajor(): int
    {
        if (class_exists('Smarty\Smarty')) {
            return 5;
        }

        return class_exists('Smarty') ? 3 : 0;
    }

    public static function hasClass(string $class): bool
    {
        return class_exists($class) || interface_exists($class);
    }

    public static function hasMethod(string $class, string $method): bool
    {
        return self::hasClass($class) && method_exists($class, $method);
    }

    /**
     * Чи лежить у дереві сусідній модуль. Саме «лежить», а не «встановлений і
     * ввімкнений»: цей стан живе в БД, а Init читає його не завжди й не всюди.
     * Для захисту від фатала при відсутньому модулі теки достатньо.
     */
    public static function hasModule(string $vendor, string $module): bool
    {
        return is_dir(__DIR__ . '/../../../' . $vendor . '/' . $module);
    }

    /**
     * Які з вимог не виконані. Запис вимоги:
     *   'Okay\Core\Design'            — клас або інтерфейс
     *   'Okay\Core\Design::minifyOutput' — метод класу
     *   'OkayCMS/NovaposhtaCost'      — сусідній модуль
     *
     * @param string[] $requirements
     * @return string[] невиконані вимоги, тим самим записом
     */
    public static function missing(array $requirements): array
    {
        $unmet = [];

        foreach ($requirements as $requirement) {
            if (strpos($requirement, '::') !== false) {
                list($class, $method) = explode('::', $requirement, 2);
                $met = self::hasMethod($class, $method);
            } elseif (strpos($requirement, '/') !== false) {
                list($vendor, $module) = explode('/', $requirement, 2);
                $met = self::hasModule($vendor, $module);
            } else {
                $met = self::hasClass($requirement);
            }

            if (!$met) {
                $unmet[] = $requirement;
            }
        }

        return $unmet;
    }
}
