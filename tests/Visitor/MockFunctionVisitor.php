<?php

namespace PhpPacker\Analysis\Tests\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitor;

/**
 * 用于测试的模拟函数访问者
 */
class MockFunctionVisitor implements NodeVisitor
{
    /**
     * 使用的函数列表
     */
    private array $usedFunctions = [];

    /**
     * 设置使用的函数列表
     *
     * @param array $functions 函数名数组
     * @return self 支持链式调用
     */
    public function setUsedFunctions(array $functions): self
    {
        $this->usedFunctions = $functions;
        return $this;
    }

    /**
     * 获取使用的函数列表
     *
     * @return array 函数名数组
     */
    public function getUsedFunctions(): array
    {
        return $this->usedFunctions;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeTraverse(array $nodes)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function leaveNode(Node $node)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function afterTraverse(array $nodes)
    {
        return null;
    }
}
