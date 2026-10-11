<?php

namespace Tests\Unit;

use App\Models\Customer;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_contact_numbers_are_normalized_to_digits(): void
    {
        $this->assertSame('09171234567', Customer::normalizeContact('0917-123-4567'));
        $this->assertSame('09171234567', Customer::normalizeContact('0917 123 4567'));
        $this->assertSame('09171234567', Customer::normalizeContact('+63 917 123 4567'));
        $this->assertSame('09171234567', Customer::normalizeContact('(+63) 917.123.4567'));
        $this->assertSame('0822345678', Customer::normalizeContact('(082) 234-5678'));
        $this->assertNull(Customer::normalizeContact(null));
    }

    public function test_contact_numbers_are_shown_in_groups(): void
    {
        $customer = new Customer(['contact_number' => '09171234567']);
        $this->assertSame('0917 123 4567', $customer->contact_display);

        $landline = new Customer(['contact_number' => '0822345678']);
        $this->assertSame('0822345678', $landline->contact_display);
    }
}
