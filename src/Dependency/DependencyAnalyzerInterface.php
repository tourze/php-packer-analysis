<?php

namespace PhpPacker\Analysis\Dependency;

use PhpPacker\Analysis\Exception\CircularDependencyException;

/**
 * 依赖分析器接口
 */
interface DependencyAnalyzerInterface
{
    /**
     * 获取优化后的文件顺序（基于依赖关系的拓扑排序）
     *
     * @param string $entryFile 入口文件路径
     * @return array<string> 优化排序后的文件路径列表
     * @throws CircularDependencyException 如果强制模式下存在循环依赖
     */
    public function getOptimizedFileOrder(string $entryFile): array;

    /**
     * 查找文件的依赖项（包括类和函数依赖）
     *
     * @param string $fileName 文件路径
     * @param array $ast 文件的AST节点数组
     * @return \Traversable<string> 依赖的文件路径生成器
     */
    public function findDependencies(string $fileName, array $ast): \Traversable;

    /**
     * 查找文件使用的资源
     *
     * @param string $fileName 文件路径
     * @param array $ast 文件的AST节点数组
     * @return \Traversable<string> 使用的资源文件路径生成器
     */
    public function findUsedResources(string $fileName, array $ast): \Traversable;
}
