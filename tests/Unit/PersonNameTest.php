<?php

namespace Tests\Unit;

use App\Support\PersonName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PersonNameTest extends TestCase
{
    public static function good(): array
    {
        return [
            ['Nuwan Akalanka'], ['A B'], ['Li'], ["Conan O'Brien"], ['Anne-Marie Smith'], ['Dr. J. R. Silva'],
            ['නුවන් අකලංක'],            // Sinhala
            ['කේෂ්‍ර පෙරේරා'],          // Sinhala with the joiner conjuncts need
            ['நுவன் அகலங்க'],           // Tamil (combining marks)
            ['José Núñez'], ['Zoë Ångström'], ['Charith Silva'], ["D’Souza Perera"],
        ];
    }

    #[DataProvider('good')]
    public function test_real_names_are_accepted(string $name): void
    {
        $this->assertTrue(PersonName::isValid($name), $name);
    }

    public static function bad(): array
    {
        return [
            'digits' => ['Nuwan123'], 'only digits' => ['12345'], 'digit in middle' => ['Nu1wan Perera'],
            'symbols' => ['Nuwan@Perera'], 'markup' => ['<b>Nuwan</b>'], 'script' => ['<script>alert(1)</script>'],
            'emoji' => ['Nuwan 😀'], 'underscore' => ['Nuwan_Perera'], 'starts with dash' => ['-Nuwan'],
            'starts with space char' => ['.Nuwan'], 'one letter' => ['A'], 'one letter with dots' => ['A..'],
            'only punctuation' => ["'-. "], 'empty' => [''], 'too long' => [str_repeat('a', 151)],
            'sql' => ["Robert'); DROP TABLE x;--"], 'slash' => ['Nuwan/Perera'], 'arabic-indic digits' => ['Nuwan ٣'],
            'newline' => ["Nuwan\nPerera"], 'tab' => ["Nuwan\tPerera"],
        ];
    }

    #[DataProvider('bad')]
    public function test_names_with_numbers_symbols_or_junk_are_refused(string $name): void
    {
        $this->assertFalse(PersonName::isValid($name), $name);
    }

    public function test_clean_trims_and_squeezes_spaces_and_line_breaks(): void
    {
        $this->assertSame('Nuwan Akalanka Payoe', PersonName::clean("  Nuwan   Akalanka\t Payoe \n"));
        $this->assertSame('', PersonName::clean(null));
    }
}
