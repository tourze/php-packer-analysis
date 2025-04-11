<?php

namespace PhpPacker\Analysis\Tests\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitor;

/**
 * 用于测试的模拟类访问者
 */
class MockClassVisitor implements NodeVisitor
{
    /**
     * 文件名
     */
    private string $fileName;

    /**
     * 必须依赖的类列表
     */
    private array $mustDependClasses = [];

    /**
     * 使用的依赖类列表
     */
    private array $usedDependClasses = [];

    /**
     * 使用的类列表
     */
    private array $useClasses = [];

    /**
     * @param string $fileName 文件名
     */
    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * 设置必须依赖的类列表
     *
     * @param array $classes 类名数组
     * @return self 支持链式调用
     */
    public function setMustDependClasses(array $classes): self
    {
        $this->mustDependClasses = $classes;
        return $this;
    }

    /**
     * 设置使用的依赖类列表
     *
     * @param array $classes 类名数组
     * @return self 支持链式调用
     */
    public function setUsedDependClasses(array $classes): self
    {
        $this->usedDependClasses = $classes;
        return $this;
    }

    /**
     * 设置使用的类列表
     *
     * @param array $classes 类名数组
     * @return self 支持链式调用
     */
    public function setUseClasses(array $classes): self
    {
        $this->useClasses = $classes;
        return $this;
    }

    /**
     * 获取必须依赖的类列表
     *
     * @return array 类名数组
     */
    public function getMustDependClasses(): array
    {
        return $this->mustDependClasses;
    }

    /**
     * 获取使用的依赖类列表
     *
     * @return array 类名数组
     */
    public function getUsedDependClasses(): array
    {
        return $this->usedDependClasses;
    }

    /**
     * 获取使用的类列表
     *
     * @return array 类名数组
     */
    public function getUseClasses(): array
    {
        return $this->useClasses;
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
