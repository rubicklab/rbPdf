<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Exception\RbPdfException;
use RuntimeException;

#[CoversClass(RbPdfException::class)]
final class RbPdfExceptionTest extends TestCase
{
    public function test_is_instance_of_runtime_exception(): void
    {
        $exception = new RbPdfException('test');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function test_can_be_constructed_with_message(): void
    {
        $message = 'RbPdfColor: valor de red (300) fora do range 0-255.';
        $exception = new RbPdfException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function test_can_be_constructed_with_message_and_code(): void
    {
        $exception = new RbPdfException('error', 42);

        $this->assertSame('error', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
    }

    public function test_can_be_caught_as_runtime_exception(): void
    {
        $caught = false;

        try {
            throw new RbPdfException('test');
        } catch (RuntimeException) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }
}
