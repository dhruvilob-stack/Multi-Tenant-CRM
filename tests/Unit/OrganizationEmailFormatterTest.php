<?php

namespace Tests\Unit;

use App\Support\OrganizationEmailFormatter;
use PHPUnit\Framework\TestCase;

class OrganizationEmailFormatterTest extends TestCase
{
    public function testSuggestEmailUsesDomainAndRole(): void
    {
        $email = OrganizationEmailFormatter::suggestEmail('John Doe', 'vendor', 'org@bluedart.com');

        $this->assertSame('john.vendor@bluedart.com', $email);
    }

    public function testNormalizeDomainHandlesPlainDomain(): void
    {
        $this->assertSame('example.com', OrganizationEmailFormatter::normalizeDomain('example.com'));
    }

    public function testNormalizeDomainHandlesEmail(): void
    {
        $this->assertSame('acme.com', OrganizationEmailFormatter::normalizeDomain('admin@acme.com'));
    }
}
