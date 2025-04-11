<?php

namespace PhpPacker\Analysis\Tests\Graph;

use PhpPacker\Analysis\Exception\CircularDependencyException;
use PhpPacker\Analysis\Graph\DependencyGraph;
use PhpPacker\Analysis\Graph\GraphAnalyzer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class GraphAnalyzerTest extends TestCase
{
    private GraphAnalyzer $analyzer;
    private DependencyGraph $graph;

    protected function setUp(): void
    {
        $this->analyzer = new GraphAnalyzer(new NullLogger());
        $this->graph = new DependencyGraph(new NullLogger());
    }

    public function testTopologicalSortWithoutCycles(): void
    {
        // 创建一个简单的有向无环图
        // A -> B -> D
        // |    |
        // v    v
        // C -> E
        $this->graph->addEdge('A', 'B');
        $this->graph->addEdge('A', 'C');
        $this->graph->addEdge('B', 'D');
        $this->graph->addEdge('B', 'E');
        $this->graph->addEdge('C', 'E');

        $sorted = $this->analyzer->topologicalSort($this->graph);

        // 验证拓扑排序的结果
        $this->assertContains('A', $sorted);
        $this->assertContains('B', $sorted);
        $this->assertContains('C', $sorted);
        $this->assertContains('D', $sorted);
        $this->assertContains('E', $sorted);

        // 验证顺序关系
        $this->assertLessThan(array_search('B', $sorted), array_search('A', $sorted));
        $this->assertLessThan(array_search('C', $sorted), array_search('A', $sorted));
        $this->assertLessThan(array_search('D', $sorted), array_search('B', $sorted));
        $this->assertLessThan(array_search('E', $sorted), array_search('B', $sorted));
        $this->assertLessThan(array_search('E', $sorted), array_search('C', $sorted));
    }

    public function testTopologicalSortWithReverse(): void
    {
        $this->graph->addEdge('A', 'B');
        $this->graph->addEdge('B', 'C');

        $normalSort = $this->analyzer->topologicalSort($this->graph);
        $reverseSort = $this->analyzer->topologicalSort($this->graph, true);

        $this->assertSame(array_reverse($normalSort), $reverseSort);
    }

    public function testTopologicalSortWithCyclesStrict(): void
    {
        // 创建一个有向有环图
        $this->graph->addEdge('A', 'B');
        $this->graph->addEdge('B', 'C');
        $this->graph->addEdge('C', 'A'); // 形成循环

        $this->expectException(CircularDependencyException::class);
        $this->analyzer->topologicalSort($this->graph);
    }

    public function testTopologicalSortWithCyclesNonStrict(): void
    {
        // 创建一个有向有环图
        $this->graph->addEdge('A', 'B');
        $this->graph->addEdge('B', 'C');
        $this->graph->addEdge('C', 'A'); // 形成循环

        $sorted = $this->analyzer->topologicalSort($this->graph, false, false);

        // 验证结果包含所有节点
        $this->assertCount(3, $sorted);
        $this->assertContains('A', $sorted);
        $this->assertContains('B', $sorted);
        $this->assertContains('C', $sorted);
    }

    public function testHasCircularDependencies(): void
    {
        // 无环图
        $this->graph->addEdge('A', 'B');
        $this->graph->addEdge('B', 'C');
        $this->assertFalse($this->analyzer->hasCircularDependencies($this->graph));

        // 有环图
        $this->graph->addEdge('C', 'A');
        $this->assertTrue($this->analyzer->hasCircularDependencies($this->graph));
    }

    public function testFindCircularDependencies(): void
    {
        // 创建有两个循环的图
        // A -> B -> C -> A (循环1)
        // D -> E -> D (循环2)
        $this->graph->addEdge('A', 'B');
        $this->graph->addEdge('B', 'C');
        $this->graph->addEdge('C', 'A');
        $this->graph->addEdge('D', 'E');
        $this->graph->addEdge('E', 'D');

        $cycles = $this->analyzer->findCircularDependencies($this->graph);

        $this->assertCount(2, $cycles);

        // 检查第一个循环
        $this->assertCount(3, $cycles[0]);
        $this->assertContains('A', $cycles[0]);
        $this->assertContains('B', $cycles[0]);
        $this->assertContains('C', $cycles[0]);

        // 检查第二个循环
        $this->assertCount(2, $cycles[1]);
        $this->assertContains('D', $cycles[1]);
        $this->assertContains('E', $cycles[1]);
    }
}
