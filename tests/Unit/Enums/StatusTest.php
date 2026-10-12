<?php

declare(strict_types=1);

/*
 * This file is part of the QIF Library package.
 *
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silarhi\Qif\Tests\Unit\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Silarhi\Qif\Enums\Status;

#[CoversClass(Status::class)]
final class StatusTest extends TestCase
{
    public function testNotClearedValue(): void
    {
        self::assertSame('', Status::NotCleared->value);
    }

    public function testClearedValue(): void
    {
        self::assertSame('c', Status::Cleared->value);
    }

    public function testReconciledValue(): void
    {
        self::assertSame('X', Status::Reconciled->value);
    }

    public function testAllCasesExist(): void
    {
        $cases = Status::cases();

        self::assertCount(3, $cases);
        self::assertContains(Status::NotCleared, $cases);
        self::assertContains(Status::Cleared, $cases);
        self::assertContains(Status::Reconciled, $cases);
    }

    public function testTryFromValidValues(): void
    {
        self::assertSame(Status::NotCleared, Status::tryFrom(''));
        self::assertSame(Status::Cleared, Status::tryFrom('c'));
        self::assertSame(Status::Reconciled, Status::tryFrom('X'));
    }

    #[DataProvider('provideInvalidValues')]
    public function testTryFromInvalidValue(string $value): void
    {
        self::assertNull(Status::tryFrom($value));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'unknown code' => ['invalid'];
    }
}
