<?php

namespace PhpPacker\Analysis\Graph;

use PhpPacker\Analysis\Exception\CircularDependencyException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * 图分析器实现
 */
class GraphAnalyzer implements GraphAnalyzerInterface
{
    /**
     * 日志记录器
     */
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function hasCircularDependencies(GraphInterface $graph): bool
    {
        return !empty($this->findCircularDependencies($graph));
    }

    /**
     * {@inheritdoc}
     */
    public function findCircularDependencies(GraphInterface $graph): array
    {
        $visited = [];
        $path = [];
        $cycles = [];

        foreach ($graph->getNodes() as $node) {
            if (!isset($visited[$node])) {
                $this->dfs($node, $graph, $visited, $path, $cycles);
            }
        }

        return $cycles;
    }

    /**
     * 深度优先搜索辅助方法，用于查找循环依赖
     *
     * @param string $node 当前节点
     * @param GraphInterface $graph 依赖图
     * @param array<string, bool> $visited 已访问节点集合
     * @param array<string, int> $path 当前遍历路径
     * @param array<int, array<int, string>> $cycles 发现的循环集合
     */
    private function dfs(
        string         $node,
        GraphInterface $graph,
        array          &$visited,
        array          &$path,
        array          &$cycles
    ): void
    {
        // 标记当前节点为已访问
        $visited[$node] = true;
        $path[$node] = count($path);

        foreach ($graph->getNeighbors($node) as $neighbor) {
            if (!isset($visited[$neighbor])) {
                $this->dfs($neighbor, $graph, $visited, $path, $cycles);
            } elseif (isset($path[$neighbor])) {
                // 找到一个循环
                $cycle = [];
                for ($i = $path[$neighbor]; $i < count($path); $i++) {
                    $cycle[] = array_search($i, $path);
                }
                $cycles[] = $cycle;
            }
        }

        // 回溯时移除当前节点
        unset($path[$node]);
    }

    /**
     * {@inheritdoc}
     */
    public function topologicalSort(GraphInterface $graph, bool $reverse = false, bool $strict = true): array
    {
        $this->logger->debug('Starting topological sort');

        // 计算每个节点的入度
        $inDegree = [];
        foreach ($graph->getNodes() as $node) {
            $inDegree[$node] = 0;
        }

        foreach ($graph->getNodes() as $node) {
            foreach ($graph->getNeighbors($node) as $neighbor) {
                $inDegree[$neighbor]++;
            }
        }

        // 将入度为0的节点加入队列
        $queue = new \SplQueue();
        foreach ($inDegree as $node => $degree) {
            if ($degree === 0) {
                $queue->enqueue($node);
            }
        }

        $result = [];

        // 拓扑排序
        while (!$queue->isEmpty()) {
            $node = $queue->dequeue();
            $result[] = $node;

            foreach ($graph->getNeighbors($node) as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] === 0) {
                    $queue->enqueue($neighbor);
                }
            }
        }

        // 检查是否存在循环依赖
        if (count($result) !== count($graph->getNodes())) {
            $this->logger->warning('Circular dependencies detected during topological sort');

            if ($strict) {
                $cycles = $this->findCircularDependencies($graph);
                throw new CircularDependencyException(
                    'Circular dependencies detected during topological sort',
                    $cycles
                );
            }

            // 非严格模式下，将剩余节点加入结果
            foreach (array_keys($inDegree) as $node) {
                if (!in_array($node, $result, true)) {
                    $result[] = $node;
                }
            }
        }

        $this->logger->debug('Topological sort completed', ['result_count' => count($result)]);

        // 是否需要反转结果
        return $reverse ? array_reverse($result) : $result;
    }
}
