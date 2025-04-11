<?php

namespace PhpPacker\Analysis\Tests\Visitor;

use PhpPacker\Analysis\Visitor\DefaultVisitorFactory;
use PhpPacker\Visitor\UseClassCollectorVisitor;
use PhpPacker\Visitor\UseFunctionCollectorVisitor;
use PhpPacker\Visitor\UseResourceCollectorVisitor;
use PHPUnit\Framework\TestCase;

class DefaultVisitorFactoryTest extends TestCase
{
    private DefaultVisitorFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new DefaultVisitorFactory();
    }

    public function testCreateClassCollector(): void
    {
        $fileName = '/path/to/test.php';
        $visitor = $this->factory->createClassCollector($fileName);

        $this->assertInstanceOf(UseClassCollectorVisitor::class, $visitor);
    }

    public function testCreateFunctionCollector(): void
    {
        $visitor = $this->factory->createFunctionCollector();

        $this->assertInstanceOf(UseFunctionCollectorVisitor::class, $visitor);
    }

    public function testCreateResourceCollector(): void
    {
        $fileName = '/path/to/test.php';
        $visitor = $this->factory->createResourceCollector($fileName);

        $this->assertInstanceOf(UseResourceCollectorVisitor::class, $visitor);
    }
}
