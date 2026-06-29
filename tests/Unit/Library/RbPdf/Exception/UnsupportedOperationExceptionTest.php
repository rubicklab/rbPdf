<?php

declare(strict_types=1);

namespace Rubick\RbPdf\Tests\Unit\Library\RbPdf\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Rubick\RbPdf\Exception\RbPdfException;
use Rubick\RbPdf\Exception\RbPdfUnsupportedOperationException;
use RuntimeException;

#[CoversClass(RbPdfUnsupportedOperationException::class)]
final class UnsupportedOperationExceptionTest extends TestCase
{
    public function test_is_instance_of_rb_pdf_exception(): void
    {
        $exception = new RbPdfUnsupportedOperationException('test');

        $this->assertInstanceOf(RbPdfException::class, $exception);
    }

    public function test_is_instance_of_runtime_exception(): void
    {
        $exception = new RbPdfUnsupportedOperationException('test');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function test_can_be_constructed_with_message(): void
    {
        $message = 'Operation roundedRect() is not supported by this adapter.';
        $exception = new RbPdfUnsupportedOperationException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function test_can_be_caught_as_rb_pdf_exception(): void
    {
        $caught = false;

        try {
            throw new RbPdfUnsupportedOperationException('unsupported');
        } catch (RbPdfException) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }

    public function test_can_be_caught_as_runtime_exception(): void
    {
        $caught = false;

        try {
            throw new RbPdfUnsupportedOperationException('unsupported');
        } catch (RuntimeException) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }
}
