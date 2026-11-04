<?php

namespace Tests\Unit;

use App\Support\SriLankanPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SriLankanPhoneTest extends TestCase
{
    public static function good(): array
    {
        return [
            ['0771234567'], ['077 123 4567'], ['+94771234567'], ['+94 77 123 4567'], ['0094771234567'],
            ['94771234567'], ['771234567'], ['(0)77-123-4567'], ['+94 (0) 77 123 4567'], ["  077\t123 4567 "],
        ];
    }

    #[DataProvider('good')]
    public function test_every_common_way_of_writing_a_mobile_number_gives_the_same_result(string $in): void
    {
        $this->assertSame('+94771234567', SriLankanPhone::normalise($in));
    }

    public static function bad(): array
    {
        return [
            'empty' => [''], 'null-ish' => ['   '], 'letters' => ['07712abcde'], 'landline' => ['0112345678'],
            'does not start with 7' => ['0551234567'], 'too short' => ['07712345'], 'too long' => ['07712345678'],
            'markup' => ['<b>0771234567</b>'], 'markup+digit' => ['<b>0771234567</b>1'], 'letter inside' => ['0771a234567'], 'indian +91' => ['+919876543210'], 'only plus' => ['+'],
            'arabic digits' => ['٠٧٧١٢٣٤٥٦٧'],
        ];
    }

    #[DataProvider('bad')]
    public function test_anything_that_is_not_a_sri_lankan_mobile_is_refused(string $in): void
    {
        $this->assertNull(SriLankanPhone::normalise($in));
    }

    public function test_null_input_is_refused(): void
    {
        $this->assertNull(SriLankanPhone::normalise(null));
    }

    public function test_pretty_groups_the_digits_for_messages(): void
    {
        $this->assertSame('+94 77 123 4567', SriLankanPhone::pretty('+94771234567'));
        $this->assertSame('weird', SriLankanPhone::pretty('weird'));
    }
}
