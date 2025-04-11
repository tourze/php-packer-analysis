<?php

namespace PhpPacker\Analysis\Tests\Visitor;

use PhpPacker\Analysis\Visitor\VisitorFactoryInterface;

/**
 * 用于测试的访问者工厂
 */
class TestVisitorFactory implements VisitorFactoryInterface
{
    /**
     * 模拟的类收集器访问者
     *
     * @var object|null
     */
    private ?object $classCollector = null;

    /**
     * 模拟的函数收集器访问者
     *
     * @var object|null
     */
    private ?object $functionCollector = null;

    /**
     * 模拟的资源收集器访问者
     *
     * @var object|null
     */
    private ?object $resourceCollector = null;

    /**
     * 设置模拟的类收集器访问者
     *
     * @param object $visitor 访问者实例
     * @return self 支持链式调用
     */
    public function setClassCollector(object $visitor): self
    {
        $this->classCollector = $visitor;
        return $this;
    }

    /**
     * 设置模拟的函数收集器访问者
     *
     * @param object $visitor 访问者实例
     * @return self 支持链式调用
     */
    public function setFunctionCollector(object $visitor): self
    {
        $this->functionCollector = $visitor;
        return $this;
    }

    /**
     * 设置模拟的资源收集器访问者
     *
     * @param object $visitor 访问者实例
     * @return self 支持链式调用
     */
    public function setResourceCollector(object $visitor): self
    {
        $this->resourceCollector = $visitor;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createClassCollector(string $fileName): object
    {
        if ($this->classCollector !== null) {
            return $this->classCollector;
        }

        return new \stdClass(); // 返回空对象作为备用
    }

    /**
     * {@inheritdoc}
     */
    public function createFunctionCollector(): object
    {
        if ($this->functionCollector !== null) {
            return $this->functionCollector;
        }

        return new \stdClass(); // 返回空对象作为备用
    }

    /**
     * {@inheritdoc}
     */
    public function createResourceCollector(string $fileName): object
    {
        if ($this->resourceCollector !== null) {
            return $this->resourceCollector;
        }

        return new \stdClass(); // 返回空对象作为备用
    }
}
