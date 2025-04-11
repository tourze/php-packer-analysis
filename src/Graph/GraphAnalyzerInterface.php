<?php

namespace PhpPacker\Analysis\Graph;

use PhpPacker\Analysis\Exception\CircularDependencyException;

/**
 * 图分析器接口
 */
interface GraphAnalyzerInterface
{
    /**
     * 检测图中是否存在循环依赖
     *
     * @param GraphInterface $graph 要分析的依赖图
     * @return bool 是否存在循环依赖
     */
    public function hasCircularDependencies(GraphInterface $graph): bool;

    /**
     * 查找图中的所有循环依赖
     *
     * @param GraphInterface $graph 要分析的依赖图
     * @return array<int, array<int, string>> 发现的循环依赖列表
     */
    public function findCircularDependencies(GraphInterface $graph): array;

    /**
     * 对图进行拓扑排序
     *
     * @param GraphInterface $graph 要排序的依赖图
     * @param bool $reverse 是否反转结果（默认为false）
     * @param bool $strict 是否在发现循环依赖时抛出异常（默认为true）
     * @return array<string> 排序后的节点列表
     * @throws CircularDependencyException 如果启用严格模式并且存在循环依赖
     */
    public function topologicalSort(GraphInterface $graph, bool $reverse = false, bool $strict = true): array;
}
