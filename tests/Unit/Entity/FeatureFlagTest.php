<?php

namespace Devexploris\ShizukuFeatureFlags\Tests\Unit\Entity;

use Devexploris\ShizukuFeatureFlags\Entity\FeatureFlag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FeatureFlagTest extends TestCase
{
    public function testValidNameIsAccepted(): void
    {
        $flag = new FeatureFlag('checkout_new_flow', 'A valid flag');

        $this->assertSame('checkout_new_flow', $flag->getName());
    }

    #[DataProvider('invalidNameProvider')]
    public function testInvalidNameThrowsException(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FeatureFlag($name, 'description');
    }

    public static function invalidNameProvider(): array
    {
        return [
            'uppercase letters'  => ['MyFlag'],
            'spaces'             => ['my flag'],
            'starts with digit'  => ['1flag'],
            'special characters' => ['flag!name'],
            'camelCase'          => ['myFeatureFlag'],
            'empty string'       => [''],
        ];
    }
}
