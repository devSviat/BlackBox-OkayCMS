<?php

namespace Modules\Sviat\BlackBox;

use Okay\Core\Request;
use Okay\Modules\Sviat\BlackBox\Security\RequestOrigin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Звірка походження мутуючого запиту.
 *
 * Маршрути модуля оголошені з to_front, тож запит іде повз авторизацію
 * бекенду. Кука адмінки має SameSite=Lax і міжсайтовий POST уже не несе, але
 * несе GET-навігацію за посиланням — саме її й перекриває ця перевірка разом
 * із вимогою POST.
 */
class RequestOriginTest extends TestCase
{
    private const SITE = 'https://shop.example';

    /** @var array<string, mixed> */
    private $serverBackup;

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;

        Request::setProtocol('https');
        Request::setDomain('shop.example');
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;

        // Статики Request переживають тест — без скидання наступний клас
        // отримав би чужий домен.
        Request::setProtocol('');
        Request::setDomain('');
    }

    /** @dataProvider acceptedProvider */
    #[DataProvider('acceptedProvider')]
    public function testRequestFromThisSiteIsAccepted(?string $origin, ?string $referer): void
    {
        $this->headers($origin, $referer);

        self::assertTrue(RequestOrigin::isFromThisSite());
    }

    /** @return array<string, array{0: string|null, 1: string|null}> */
    public static function acceptedProvider(): array
    {
        return [
            'звичайний Origin'        => [self::SITE, null],
            'Origin і Referer разом'  => [self::SITE, self::SITE . '/backend/'],
            'лише Referer'            => [null, self::SITE . '/backend/orders'],
            'регістр хоста'           => ['https://SHOP.EXAMPLE', null],
            'типовий порт написаний'  => ['https://shop.example:443', null],
        ];
    }

    /** @dataProvider rejectedProvider */
    #[DataProvider('rejectedProvider')]
    public function testForeignRequestIsRejected(?string $origin, ?string $referer): void
    {
        $this->headers($origin, $referer);

        self::assertFalse(RequestOrigin::isFromThisSite());
    }

    /** @return array<string, array{0: string|null, 1: string|null}> */
    public static function rejectedProvider(): array
    {
        return [
            'чужий хост'          => ['https://evil.example', null],
            'наш хост як префікс' => ['https://shop.example.evil.test', null],
            'інша схема'          => ['http://shop.example', null],
            'інший порт'          => ['https://shop.example:8443', null],
            // Пісочниця в iframe шле рядок "null" — це не наш origin.
            'рядок null'          => ['null', null],
            'сміття замість URL'  => ['not a url', null],
            // Облікових даних у чесному заголовку Origin не буває.
            'логін у хості'       => ['https://shop.example@evil.example', null],
            'нічого не надіслано' => [null, null],
            'порожні заголовки'   => ['', ''],
        ];
    }

    /**
     * Referer — запасний варіант, а не другий шанс. Інакше сторонній сайт
     * підсунув би свій Origin і наш Referer і пройшов би.
     */
    public function testForeignOriginIsNotSavedByOurReferer(): void
    {
        $this->headers('https://evil.example', self::SITE . '/backend/');

        self::assertFalse(RequestOrigin::isFromThisSite());
    }

    /**
     * Робочий шлях: домен ніхто не задає явно, тож Request бере його з
     * HTTP_HOST — тобто з адреси, яку відкрив сам відвідувач. Саме тому
     * очікуваний origin не може розійтися з тим, що шле браузер.
     */
    public function testExpectedOriginFallsBackToTheRequestHost(): void
    {
        Request::setProtocol('');
        Request::setDomain('');
        // Обчислення протоколу в ядрі читає обидва ключі напряму; у CLI їх
        // немає, і без них тест ловив би попередження ядра замість свого.
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SERVER_PORT'] = '443';
        $_SERVER['HTTP_HOST'] = 'shop.example';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        $this->headers('https://shop.example', null);
        self::assertTrue(RequestOrigin::isFromThisSite());

        $this->headers('https://evil.example', null);
        self::assertFalse(RequestOrigin::isFromThisSite());
    }

    private function headers(?string $origin, ?string $referer): void
    {
        unset($_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_REFERER']);

        if ($origin !== null) {
            $_SERVER['HTTP_ORIGIN'] = $origin;
        }
        if ($referer !== null) {
            $_SERVER['HTTP_REFERER'] = $referer;
        }
    }
}
