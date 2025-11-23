<?php

declare(strict_types=1);

/*
 * This file is part of the QIF Library package.
 *
 * (c) Mário Čechovič <mimographix@gmail.com>
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace MimoGraphix\QIF\Tests\Unit\Enums;

use MimoGraphix\QIF\Enums\Status;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Status::class)]
final class StatusTest extends TestCase
{
    public function testNotClearedValue(): void
    {
        $this->assertSame('', Status::NOT_CLEARED->value);
    }

    public function testClearedValue(): void
    {
        $this->assertSame('c', Status::CLEARED->value);
    }

    public function testReconciledValue(): void
    {
        $this->assertSame('X', Status::RECONCILED->value);
    }

    public function testAllCasesExist(): void
    {
        $cases = Status::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(Status::NOT_CLEARED, $cases);
        $this->assertContains(Status::CLEARED, $cases);
        $this->assertContains(Status::RECONCILED, $cases);
    }

    public function testTryFromValidValues(): void
    {
        $this->assertSame(Status::NOT_CLEARED, Status::tryFrom(''));
        $this->assertSame(Status::CLEARED, Status::tryFrom('c'));
        $this->assertSame(Status::RECONCILED, Status::tryFrom('X'));
    }

    public function testTryFromInvalidValue(): void
    {
        $this->assertNull(Status::tryFrom('invalid'));
    }
}
