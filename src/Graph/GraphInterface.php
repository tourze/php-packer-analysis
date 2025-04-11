<?php

namespace PhpPacker\Analysis\Graph;

/**
 * 依赖图接口
 */
interface GraphInterface
{
    /**
     * 添加节点
     *
     * @param string $node 节点名称
     * @return self 支持链式调用
     */
    public function addNode(string $node): self;

    /**
     * 添加边（依赖关系）
     *
     * @param string $from 起始节点
     * @param string $to 目标节点
     * @return self 支持链式调用
     */
    public function addEdge(string $from, string $to): self;

    /**
     * 检查图中是否存在节点
     *
     * @param string $node 节点名称
     * @return bool 是否存在
     */
    public function hasNode(string $node): bool;

    /**
     * 检查两个节点之间是否存在边（依赖关系）
     *
     * @param string $from 起始节点
     * @param string $to 目标节点
     * @return bool 是否存在边
     */
    public function hasEdge(string $from, string $to): bool;

    /**
     * 获取节点的所有出边目标节点
     *
     * @param string $node 起始节点
     * @return array<string> 目标节点列表
     */
    public function getNeighbors(string $node): array;

    /**
     * 获取图中的所有节点
     *
     * @return array<string> 节点列表
     */
    public function getNodes(): array;

    /**
     * 获取原始依赖图数据
     *
     * @return array<string, array<string>> 依赖图数据 [节点 => [依赖节点列表]]
     */
    public function getGraphData(): array;
}
