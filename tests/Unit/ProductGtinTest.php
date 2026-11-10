<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductGtinTest extends TestCase
{
    private function product(?string $gtin, ?string $barcode): Product
    {
        return (new Product())->forceFill(['gtin' => $gtin, 'barcode' => $barcode]);
    }

    public function test_a_real_barcode_is_used_when_no_gtin_was_entered(): void
    {
        $this->assertSame('4006381333931', $this->product(null, '4006381333931')->globalGtin()); // EAN-13
        $this->assertSame('036000291452', $this->product('', '0360 0029 1452')->globalGtin());   // UPC-A
    }

    public function test_a_made_up_code_is_never_sent_as_a_gtin(): void
    {
        $this->assertNull($this->product(null, '4006381333932')->globalGtin()); // wrong check digit
        $this->assertNull($this->product(null, 'BABY227G')->globalGtin());
        $this->assertNull($this->product(null, null)->globalGtin());
    }

    public function test_the_gtin_field_wins(): void
    {
        $this->assertSame('036000291452', $this->product('036000291452', '4006381333931')->globalGtin());
    }
}
