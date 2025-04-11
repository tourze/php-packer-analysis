<?php

namespace PhpPacker\Analysis\Tests\Dependency;

use PhpPacker\Analysis\Dependency\ClassDependencyAnalyzer;
use PhpPacker\Analysis\Graph\DependencyGraph;
use PhpPacker\Analysis\Graph\GraphAnalyzerInterface;
use PhpPacker\Analysis\ReflectionServiceInterface;
use PhpPacker\Analysis\Tests\Visitor\MockClassVisitor;
use PhpPacker\Ast\AstManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ClassDependencyAnalyzerTest extends TestCase
{
    /** @var AstManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $astManager;

    /** @var ReflectionServiceInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $reflectionService;

    /** @var GraphAnalyzerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $graphAnalyzer;

    private ClassDependencyAnalyzer $classAnalyzer;

    protected function setUp(): void
    {
        $this->astManager = $this->createMock(AstManagerInterface::class);
        $this->reflectionService = $this->createMock(ReflectionServiceInterface::class);
        $this->graphAnalyzer = $this->createMock(GraphAnalyzerInterface::class);

        $this->classAnalyzer = new ClassDependencyAnalyzer(
            $this->astManager,
            $this->reflectionService,
            $this->graphAnalyzer,
            new NullLogger()
        );
    }

    public function testFindClassFile(): void
    {
        $className = 'TestClass';
        $expectedFile = '/path/to/TestClass.php';

        $this->reflectionService->expects($this->once())
            ->method('getClassFileName')
            ->with($className)
            ->willReturn($expectedFile);

        $result = $this->classAnalyzer->findClassFile($className);
        $this->assertEquals($expectedFile, $result);
    }

    public function testAnalyzeMustDependenciesWithEmptyGraph(): void
    {
        // 模拟空的AST集合
        $this->astManager->expects($this->once())
            ->method('getAllAsts')
            ->willReturn([]);

        // 创建一个能够替换createVisitor方法的子类
        $classAnalyzer = $this->getMockBuilder(ClassDependencyAnalyzer::class)
            ->setConstructorArgs([
                $this->astManager,
                $this->reflectionService,
                $this->graphAnalyzer,
                new NullLogger()
            ])
            ->onlyMethods(['createVisitor'])
            ->getMock();

        // 不应该调用createVisitor方法
        $classAnalyzer->expects($this->never())
            ->method('createVisitor');

        // 模拟拓扑排序结果
        $emptyGraph = new DependencyGraph(new NullLogger());
        $this->graphAnalyzer->expects($this->once())
            ->method('topologicalSort')
            ->willReturn([]);

        // 执行测试
        $result = $classAnalyzer->analyzeMustDependencies();
        $this->assertEmpty($result);
    }

    public function testAnalyzeMustDependenciesWithDependencies(): void
    {
        $file1 = '/path/to/File1.php';
        $file2 = '/path/to/File2.php';
        $ast1 = ['nodeA']; // 简化的AST
        $ast2 = ['nodeB']; // 简化的AST

        // 模拟AST管理器返回AST
        $this->astManager->expects($this->once())
            ->method('getAllAsts')
            ->willReturn([
                $file1 => $ast1,
                $file2 => $ast2
            ]);

        // 创建一个能够替换createVisitor方法的子类
        $classAnalyzer = $this->getMockBuilder(ClassDependencyAnalyzer::class)
            ->setConstructorArgs([
                $this->astManager,
                $this->reflectionService,
                $this->graphAnalyzer,
                new NullLogger()
            ])
            ->onlyMethods(['createVisitor'])
            ->getMock();

        // 模拟createVisitor方法的行为
        $visitor1 = new MockClassVisitor($file1);
        $visitor1->setMustDependClasses(['B']);

        $visitor2 = new MockClassVisitor($file2);
        // file2没有依赖

        $classAnalyzer->expects($this->exactly(2))
            ->method('createVisitor')
            ->willReturnOnConsecutiveCalls($visitor1, $visitor2);

        // 模拟NodeTraverser
        $mockTraverser = $this->createMock(\PhpParser\NodeTraverser::class);
        $this->astManager->expects($this->exactly(2))
            ->method('createNodeTraverser')
            ->willReturn($mockTraverser);

        // 模拟traverse方法调用
        $mockTraverser->expects($this->exactly(2))
            ->method('traverse')
            ->willReturnOnConsecutiveCalls($ast1, $ast2);

        // 设置反射服务返回类文件
        $this->reflectionService->expects($this->once())
            ->method('getClassFileName')
            ->with('B')
            ->willReturn($file2);

        // 模拟拓扑排序结果
        $this->graphAnalyzer->expects($this->once())
            ->method('topologicalSort')
            ->willReturn([$file2, $file1]);

        // 执行测试
        $result = $classAnalyzer->analyzeMustDependencies();

        // 验证结果
        $this->assertCount(2, $result);
        $this->assertEquals($file2, $result[0]);
        $this->assertEquals($file1, $result[1]);
    }

    public function testAnalyzeUsedDependencies(): void
    {
        $file1 = '/path/to/File1.php';
        $file2 = '/path/to/File2.php';
        $file3 = '/path/to/File3.php';
        $processedFiles = [$file1]; // 已处理的文件

        // 模拟AST管理器返回AST
        $this->astManager->expects($this->once())
            ->method('getAllAsts')
            ->willReturn([
                $file2 => ['ast2'],
                $file3 => ['ast3']
            ]);

        // 创建一个能够替换createVisitor方法的子类
        $classAnalyzer = $this->getMockBuilder(ClassDependencyAnalyzer::class)
            ->setConstructorArgs([
                $this->astManager,
                $this->reflectionService,
                $this->graphAnalyzer,
                new NullLogger()
            ])
            ->onlyMethods(['createVisitor'])
            ->getMock();

        // 模拟createVisitor方法的行为
        $visitor1 = new MockClassVisitor($file2);
        $visitor1->setUsedDependClasses(['C']);

        $visitor2 = new MockClassVisitor($file3);
        // file3没有依赖

        $classAnalyzer->expects($this->exactly(2))
            ->method('createVisitor')
            ->willReturnOnConsecutiveCalls($visitor1, $visitor2);

        // 模拟NodeTraverser
        $mockTraverser = $this->createMock(\PhpParser\NodeTraverser::class);
        $this->astManager->expects($this->exactly(2))
            ->method('createNodeTraverser')
            ->willReturn($mockTraverser);

        // 模拟traverse方法调用
        $mockTraverser->expects($this->exactly(2))
            ->method('traverse')
            ->willReturnOnConsecutiveCalls(['ast2'], ['ast3']);

        // 设置反射服务返回类文件
        $this->reflectionService->expects($this->once())
            ->method('getClassFileName')
            ->with('C')
            ->willReturn($file3);

        // 模拟拓扑排序结果 - 包括所有节点
        $this->graphAnalyzer->expects($this->once())
            ->method('topologicalSort')
            ->willReturn([$file3, $file2]);

        // 执行测试
        $result = $classAnalyzer->analyzeUsedDependencies($processedFiles);

        // 验证结果 - 只返回未处理的文件
        $this->assertCount(2, $result);
        $this->assertEquals($file3, $result[0]);
        $this->assertEquals($file2, $result[1]);
    }

    public function testDebugGraphToFile(): void
    {
        // 创建一个正常的实例
        $classAnalyzer = new ClassDependencyAnalyzer(
            $this->astManager,
            $this->reflectionService,
            $this->graphAnalyzer,
            new NullLogger()
        );

        // 创建临时文件名
        $tempFile = sys_get_temp_dir() . '/test-graph-' . uniqid() . '.json';

        // 创建测试图
        $graph = new DependencyGraph(new NullLogger());
        $graph->addEdge('node1', 'node2');

        // 通过反射调用私有方法
        $method = new \ReflectionMethod(ClassDependencyAnalyzer::class, 'debugGraphToFile');
        $method->setAccessible(true);
        $method->invoke($classAnalyzer, $graph, $tempFile);

        // 验证文件已被创建且包含正确的内容
        $this->assertFileExists($tempFile);
        $content = file_get_contents($tempFile);
        $this->assertNotEmpty($content);

        // 验证内容是有效的JSON
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('node1', $data);
        $this->assertEquals(['node2'], $data['node1']);

        // 清理
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}
