<?php

namespace Okay\Modules\Sviat\BlackBox\Security;

use Okay\Core\Security\SessionNames;

/**
 * Логін менеджера, залогіненого в адмінці, або null.
 *
 * Потрібен там, де маршрут оголошений з to_front: запит іде через вітрину повз
 * авторизацію бекенду, тож модуль перевіряє доступ сам.
 *
 * Рушії зберігають бекендову сесію по-різному. Де вітрина й адмінка ділять одну
 * сесію, логін лежить у $_SESSION['admin']. Де в адмінки власна кука, сесія
 * вітрини її не бачить узагалі — там логін читає ядро.
 */
class AdminIdentity
{
    /**
     * Гілка ядра тримається на тому, що рушій читає бекендову сесію ДО старту
     * вітринної й запам'ятовує логін: при вже активній сесії SessionNames
     * навмисне віддає null. Зникне той крок із index.php — маршрути почнуть
     * віддавати 401 залогіненому менеджеру, і мовчки.
     */
    public function login(): ?string
    {
        if ($this->hasSeparateBackendSession()) {
            return SessionNames::adminLogin();
        }

        $login = $_SESSION['admin'] ?? null;

        return is_string($login) && $login !== '' ? $login : null;
    }

    /**
     * Окремим методом, щоб обидві гілки були перевірімі: наявність класу —
     * глобальний стан процесу, і в тесті його не підмінити.
     */
    protected function hasSeparateBackendSession(): bool
    {
        return class_exists(SessionNames::class);
    }
}
