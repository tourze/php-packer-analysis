<?php

namespace PhpPacker\Analysis\Visitor;

/**
 * 访问者工厂接口
 */
interface VisitorFactoryInterface
{
    /**
     * 创建类收集器访问者
     *
     * @param string $fileName 文件名
     * @return object 类收集器访问者
     */
    public function createClassCollector(string $fileName): object;

    /**
     * 创建函数收集器访问者
     *
     * @return object 函数收集器访问者
     */
    public function createFunctionCollector(): object;

    /**
     * 创建资源收集器访问者
     *
     * @param string $fileName 文件名
     * @return object 资源收集器访问者
     */
    public function createResourceCollector(string $fileName): object;
}
