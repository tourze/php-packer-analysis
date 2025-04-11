<?php

namespace PhpPacker\Analysis\Tests\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitor;

/**
 * 用于测试的模拟资源访问者
 */
class MockResourceVisitor implements NodeVisitor
{
    /**
     * 文件名
     */
    private string $fileName;

    /**
     * 资源文件列表
     */
    private array $resources = [];

    /**
     * @param string $fileName 文件名
     */
    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * 设置资源文件列表
     *
     * @param array $resources 资源文件路径数组
     * @return self 支持链式调用
     */
    public function setResources(array $resources): self
    {
        $this->resources = $resources;
        return $this;
    }

    /**
     * 获取资源文件列表
     *
     * @return array 资源文件路径数组
     */
    public function getResources(): array
    {
        return $this->resources;
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
