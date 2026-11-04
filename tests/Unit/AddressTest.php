<?php

namespace Tests\Unit;

use App\Support\Address;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    private static function ok(string $a): bool
    {
        return preg_match(Address::REGEX, Address::clean($a)) === 1 && mb_strlen(Address::clean($a)) <= 300;
    }

    public static function good(): array
    {
        return [['123/A, Main Street, Colombo 07'], ['Dankotuwa'], ['දන්කොටුව, කොළඹ'], ['கொழும்பு 12/3'], ['No 5 Galle Road'], ['12 A, Café Street'], ['No. 12, Galle Rd., Kandy'], ['Kandy-Colombo Road'], ['45/B, St. Annes']];
    }

    #[DataProvider('good')]
    public function test_letters_numbers_commas_slashes_dots_and_hyphens_are_fine(string $a): void
    {
        $this->assertTrue(self::ok($a), $a);
    }

    public static function bad(): array
    {
        $out = ['only digits' => ['12345'], 'only punctuation' => [', / , . -'], 'empty' => ['']];
        foreach (str_split("@#\$%^&*()_+=[]{}|\\:;\"<>?~`!'") as $c) {
            $out["symbol $c"] = ["Main {$c} Street 5"];
        }
        $out['emoji'] = ['Colombo 😀'];
        $out['markup'] = ['<script>alert(1)</script>'];
        $out['arabic-indic digits'] = ['Colombo ٣'];

        return $out;
    }

    #[DataProvider('bad')]
    public function test_anything_else_is_refused(string $a): void
    {
        $this->assertFalse(self::ok($a), $a);
    }

    public function test_clean_squeezes_spaces_and_line_breaks(): void
    {
        $this->assertSame('No 5, Main Street', Address::clean("  No 5,\n\t Main   Street \r\n"));
        $this->assertSame('', Address::clean(null));
    }
}
