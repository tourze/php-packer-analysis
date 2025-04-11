<?php

namespace PhpPacker\Analysis\Tests\Exception;

use PhpPacker\Analysis\Exception\AnalysisException;
use PhpPacker\Analysis\Exception\CircularDependencyException;
use PHPUnit\Framework\TestCase;

class CircularDependencyExceptionTest extends TestCase
{
    public function testIsAnalysisException(): void
    {
        $exception = new CircularDependencyException('Test message');
        $this->assertInstanceOf(AnalysisException::class, $exception);
    }

    public function testConstructorWithMessageAndCycles(): void
    {
        $message = 'Circular dependency found';
        $cycles = [
            ['A', 'B', 'C'],
            ['D', 'E']
        ];

        $exception = new CircularDependencyException($message, $cycles);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertSame($cycles, $exception->getCycles());
    }

    public function testGetCycles(): void
    {
        $cycles = [
            ['A', 'B', 'C'],
            ['D', 'E']
        ];

        $exception = new CircularDependencyException('Test message', $cycles);

        $this->assertSame($cycles, $exception->getCycles());
        $this->assertCount(2, $exception->getCycles());
    }

    public function testGetFormattedCycles(): void
    {
        $cycles = [
            ['A', 'B', 'C'],
            ['D', 'E']
        ];

        $exception = new CircularDependencyException('Test message', $cycles);

        $formattedCycles = $exception->getFormattedCycles();
        $this->assertStringContainsString('A -> B -> C -> A', $formattedCycles);
        $this->assertStringContainsString('D -> E -> D', $formattedCycles);
    }

    public function testEmptyCycles(): void
    {
        $exception = new CircularDependencyException('Test message');

        $this->assertEmpty($exception->getCycles());
        $this->assertEmpty($exception->getFormattedCycles());
    }
}
