<?php

namespace Modules\Sviat\BlackBox;

use Okay\Modules\Sviat\BlackBox\Helpers\BlackBoxApiHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Приведення номера до вигляду, у якому він іде в чорний список. Один і той
 * самий клієнт може лишити номер у п'яти нотаціях — якщо вони не зводяться до
 * одного рядка, перевірка по базі відмов просто не спрацює.
 *
 * Метод не торкається єдиної залежності конструктора (Settings), тож клас
 * будується в обхід конструктора.
 */
class PhoneNormalizationTest extends TestCase
{
    private BlackBoxApiHelper $helper;

    protected function setUp(): void
    {
        $this->helper = (new \ReflectionClass(BlackBoxApiHelper::class))->newInstanceWithoutConstructor();
    }

    /** Усі побутові нотації одного номера мусять зійтись до 0XXXXXXXXX. */
    /** @dataProvider sameNumberProvider */
    #[DataProvider('sameNumberProvider')]
    public function testEveryNotationOfTheSameNumberCollapsesToLocalFormat(string $input): void
    {
        self::assertSame('0671234567', $this->helper->normalizePhone($input));
    }

    public static function sameNumberProvider(): array
    {
        return [
            'локальний'          => ['0671234567'],
            'E.164'              => ['+380671234567'],
            'без плюса'          => ['380671234567'],
            'без коду й нуля'    => ['671234567'],
            'з розділювачами'    => ['+38 (067) 123-45-67'],
            'з крапками'         => ['067.123.45.67'],
            'із пробілами'       => [' 067 123 45 67 '],
        ];
    }

    /** @dataProvider emptyProvider */

    #[DataProvider('emptyProvider')]
    public function testEmptyInputGivesNull(?string $input): void
    {
        self::assertNull($this->helper->normalizePhone($input));
    }

    public static function emptyProvider(): array
    {
        return [
            'null'           => [null],
            'порожній рядок' => [''],
        ];
    }

    /**
     * Код країни зрізається тільки коли номер справді 12-значний і починається
     * з 38: інакше довільний номер, що випадково почався з 38, втратив би дві
     * цифри.
     */
    public function testCountryCodeIsStrippedOnlyFromTwelveDigitNumbers(): void
    {
        self::assertSame('0381234567', $this->helper->normalizePhone('0381234567'));
        self::assertSame('3812345678901', $this->helper->normalizePhone('3812345678901'));
    }

    /**
     * Провідний нуль дописується лише до дев'ятизначного номера. Чужі формати
     * лишаються як є — краще не знайти збіг, ніж знайти хибний.
     */
    /** @dataProvider foreignFormatProvider */
    #[DataProvider('foreignFormatProvider')]
    public function testOtherLengthsArePassedThroughAsDigitsOnly(string $input, string $expected): void
    {
        self::assertSame($expected, $this->helper->normalizePhone($input));
    }

    public static function foreignFormatProvider(): array
    {
        return [
            'польський'      => ['+48 501 234 567', '48501234567'],
            'закороткий'     => ['12345', '12345'],
            'вісім цифр'     => ['12345678', '12345678'],
        ];
    }

    /**
     * Рядок без жодної цифри дає порожній результат, а не null: викликаючий код
     * має відрізняти «номера не було» від «номер був, але сміттєвий».
     */
    public function testInputWithoutDigitsGivesEmptyString(): void
    {
        self::assertSame('', $this->helper->normalizePhone('немає цифр'));
    }
}
