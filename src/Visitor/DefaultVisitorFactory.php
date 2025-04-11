<?php

namespace PhpPacker\Analysis\Visitor;

use PhpPacker\Visitor\UseClassCollectorVisitor;
use PhpPacker\Visitor\UseFunctionCollectorVisitor;
use PhpPacker\Visitor\UseResourceCollectorVisitor;

/**
 * 默认访问者工厂实现
 */
class DefaultVisitorFactory implements VisitorFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createClassCollector(string $fileName): object
    {
        return new UseClassCollectorVisitor($fileName);
    }

    /**
     * {@inheritdoc}
     */
    public function createFunctionCollector(): object
    {
        return new UseFunctionCollectorVisitor();
    }

    /**
     * {@inheritdoc}
     */
    public function createResourceCollector(string $fileName): object
    {
        return new UseResourceCollectorVisitor($fileName);
    }
}
