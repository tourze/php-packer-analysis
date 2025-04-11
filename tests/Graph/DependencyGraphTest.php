<?php

namespace PhpPacker\Analysis\Tests\Graph;

use PhpPacker\Analysis\Graph\DependencyGraph;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class DependencyGraphTest extends TestCase
{
    private DependencyGraph $graph;

    protected function setUp(): void
    {
        $this->graph = new DependencyGraph(new NullLogger());
    }

    public function testAddNode(): void
    {
        $this->graph->addNode('node1');
        $this->assertTrue($this->graph->hasNode('node1'));
        $this->assertFalse($this->graph->hasNode('node2'));
    }

    public function testAddEdge(): void
    {
        $this->graph->addEdge('node1', 'node2');
        $this->assertTrue($this->graph->hasNode('node1'));
        $this->assertTrue($this->graph->hasNode('node2'));
        $this->assertTrue($this->graph->hasEdge('node1', 'node2'));
        $this->assertFalse($this->graph->hasEdge('node2', 'node1'));
    }

    public function testGetNeighbors(): void
    {
        $this->graph->addEdge('node1', 'node2');
        $this->graph->addEdge('node1', 'node3');

        $neighbors = $this->graph->getNeighbors('node1');
        $this->assertCount(2, $neighbors);
        $this->assertContains('node2', $neighbors);
        $this->assertContains('node3', $neighbors);

        // 没有出边的节点
        $this->assertEmpty($this->graph->getNeighbors('node2'));

        // 不存在的节点
        $this->assertEmpty($this->graph->getNeighbors('nonexistent'));
    }

    public function testGetNodes(): void
    {
        $this->graph->addNode('node1');
        $this->graph->addNode('node2');
        $this->graph->addEdge('node3', 'node4');

        $nodes = $this->graph->getNodes();
        $this->assertCount(4, $nodes);
        $this->assertContains('node1', $nodes);
        $this->assertContains('node2', $nodes);
        $this->assertContains('node3', $nodes);
        $this->assertContains('node4', $nodes);
    }

    public function testGetGraphData(): void
    {
        $this->graph->addEdge('node1', 'node2');
        $this->graph->addEdge('node1', 'node3');
        $this->graph->addNode('node4');

        $graphData = $this->graph->getGraphData();
        $this->assertCount(4, $graphData);
        $this->assertArrayHasKey('node1', $graphData);
        $this->assertArrayHasKey('node2', $graphData);
        $this->assertArrayHasKey('node3', $graphData);
        $this->assertArrayHasKey('node4', $graphData);

        $this->assertCount(2, $graphData['node1']);
        $this->assertContains('node2', $graphData['node1']);
        $this->assertContains('node3', $graphData['node1']);

        $this->assertEmpty($graphData['node2']);
        $this->assertEmpty($graphData['node3']);
        $this->assertEmpty($graphData['node4']);
    }

    public function testClear(): void
    {
        $this->graph->addEdge('node1', 'node2');
        $this->assertCount(2, $this->graph->getNodes());

        $this->graph->clear();
        $this->assertCount(0, $this->graph->getNodes());
        $this->assertFalse($this->graph->hasNode('node1'));
        $this->assertFalse($this->graph->hasNode('node2'));
    }
}
