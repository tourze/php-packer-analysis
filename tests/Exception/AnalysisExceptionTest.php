<?php

namespace PhpPacker\Analysis\Tests\Exception;

use PhpPacker\Analysis\Exception\AnalysisException;
use PHPUnit\Framework\TestCase;

class AnalysisExceptionTest extends TestCase
{
    public function testIsRuntimeException(): void
    {
        $exception = new AnalysisException('Test message');
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testConstructorWithMessage(): void
    {
        $message = 'Test analysis exception message';
        $exception = new AnalysisException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testConstructorWithCode(): void
    {
        $code = 123;
        $exception = new AnalysisException('Test message', $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function testConstructorWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new AnalysisException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
