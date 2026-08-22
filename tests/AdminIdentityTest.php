<?php

namespace Modules\Sviat\BlackBox;

use Okay\Core\Security\SessionNames;
use Okay\Modules\Sviat\BlackBox\Security\AdminIdentity;
use PHPUnit\Framework\TestCase;

/**
 * Обидві гілки вибору джерела логіна. Гілка спільної сесії перевіряється на
 * будь-якому рушії; гілка ядра — лише там, де клас ядра є.
 */
class AdminIdentityTest extends TestCase
{
    private $sessionBackup;

    protected function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->sessionBackup === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $this->sessionBackup;
        }
    }

    /** Рушій зі спільною сесією — незалежно від того, на якому йде прогін. */
    private function sharedSession(): AdminIdentity
    {
        return new class extends AdminIdentity {
            protected function hasSeparateBackendSession(): bool
            {
                return false;
            }
        };
    }

    public function testLoginIsReadFromTheSession(): void
    {
        $_SESSION = ['admin' => 'manager'];

        self::assertSame('manager', $this->sharedSession()->login());
    }

    /**
     * null, а не порожній рядок: ManagersEntity::get('') знайшов би першого-
     * ліпшого менеджера, і перевірка прав пропустила б анонімний запит.
     *
     * @dataProvider notLoggedIn
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('notLoggedIn')]
    public function testAbsentOrUnusableLoginIsNull($session): void
    {
        if ($session === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $session;
        }

        self::assertNull($this->sharedSession()->login());
    }

    public static function notLoggedIn(): array
    {
        return [
            'сесії немає'    => [null],
            'ключа немає'    => [[]],
            'порожній рядок' => [['admin' => '']],
            'не рядок'       => [['admin' => 0]],
            'масив'          => [['admin' => ['x']]],
        ];
    }

    /**
     * Рушій з окремою бекендовою сесією: логін приходить від ядра, а
     * $_SESSION['admin'] вітрини ігнорується — під час to_front-запиту він
     * порожній або, гірше, чужий.
     *
     * Шимів сумісності тут немає навмисно: на стоці тест не виконується, а
     * розбирається файл однаково на обох рушіях.
     */
    public function testBackendSessionWinsOverTheStorefrontOne(): void
    {
        if (!class_exists(SessionNames::class)) {
            self::markTestSkipped('рушій тримає одну сесію на вітрину й адмінку');
        }

        $_SESSION = ['admin' => 'storefront'];

        self::assertSame('backend', $this->withCoreLogin('backend'));
        self::assertNull($this->withCoreLogin(null));
    }

    /**
     * Ядро запам'ятовує логін у статичних полях, і index.php наповнює їх до
     * старту сесії вітрини. Тест стає на місце цього кроку й повертає стан
     * назад, бо статика переживає тест.
     */
    private function withCoreLogin(?string $login): ?string
    {
        $core    = new \ReflectionClass(SessionNames::class);
        $checked = $core->getProperty('adminChecked');
        $stored  = $core->getProperty('adminLogin');

        $previousChecked = $checked->getValue();
        $previousLogin   = $stored->getValue();

        $checked->setValue(null, true);
        $stored->setValue(null, $login);

        try {
            return (new AdminIdentity())->login();
        } finally {
            $checked->setValue(null, $previousChecked);
            $stored->setValue(null, $previousLogin);
        }
    }
}
