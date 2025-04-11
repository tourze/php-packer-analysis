<?php

namespace PhpPacker\Analysis\Graph;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * 依赖图实现
 */
class DependencyGraph implements GraphInterface
{
    /**
     * 图的邻接表表示 [节点 => [邻居节点列表]]
     *
     * @var array<string, array<string>>
     */
    private array $graph = [];

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
    public function addNode(string $node): self
    {
        if (!isset($this->graph[$node])) {
            $this->graph[$node] = [];
            $this->logger->debug('Added node to dependency graph', ['node' => $node]);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addEdge(string $from, string $to): self
    {
        // 确保两个节点都存在
        $this->addNode($from);
        $this->addNode($to);

        // 只有当边不存在时才添加
        if (!$this->hasEdge($from, $to)) {
            $this->graph[$from][] = $to;
            $this->logger->debug('Added edge to dependency graph', [
                'from' => $from,
                'to' => $to
            ]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasNode(string $node): bool
    {
        return isset($this->graph[$node]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasEdge(string $from, string $to): bool
    {
        return $this->hasNode($from) && in_array($to, $this->graph[$from], true);
    }

    /**
     * {@inheritdoc}
     */
    public function getNeighbors(string $node): array
    {
        return $this->hasNode($node) ? $this->graph[$node] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getNodes(): array
    {
        return array_keys($this->graph);
    }

    /**
     * {@inheritdoc}
     */
    public function getGraphData(): array
    {
        return $this->graph;
    }

    /**
     * 清空图
     *
     * @return self 支持链式调用
     */
    public function clear(): self
    {
        $this->graph = [];
        $this->logger->debug('Dependency graph cleared');
        return $this;
    }
}
