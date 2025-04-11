<?php

namespace PhpPacker\Analysis\Tests\Dependency;

use PhpPacker\Analysis\Dependency\ClassDependencyAnalyzer;
use PhpPacker\Analysis\Dependency\DependencyAnalyzer;
use PhpPacker\Analysis\Dependency\FunctionDependencyAnalyzer;
use PhpPacker\Analysis\Dependency\ResourceAnalyzer;
use PhpPacker\Analysis\ReflectionServiceInterface;
use PhpPacker\Analysis\Tests\Visitor\MockClassVisitor;
use PhpPacker\Analysis\Tests\Visitor\MockFunctionVisitor;
use PhpPacker\Analysis\Tests\Visitor\TestVisitorFactory;
use PhpPacker\Ast\AstManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class DependencyAnalyzerTest extends TestCase
{
    /** @var AstManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $astManager;

    /** @var ReflectionServiceInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $reflectionService;

    /** @var ClassDependencyAnalyzer&\PHPUnit\Framework\MockObject\MockObject */
    private $classDependencyAnalyzer;

    /** @var FunctionDependencyAnalyzer&\PHPUnit\Framework\MockObject\MockObject */
    private $functionDependencyAnalyzer;

    /** @var ResourceAnalyzer&\PHPUnit\Framework\MockObject\MockObject */
    private $resourceAnalyzer;

    /** @var TestVisitorFactory */
    private $visitorFactory;

    private DependencyAnalyzer $dependencyAnalyzer;

    protected function setUp(): void
    {
        $this->astManager = $this->createMock(AstManagerInterface::class);
        $this->reflectionService = $this->createMock(ReflectionServiceInterface::class);
        $this->classDependencyAnalyzer = $this->createMock(ClassDependencyAnalyzer::class);
        $this->functionDependencyAnalyzer = $this->createMock(FunctionDependencyAnalyzer::class);
        $this->resourceAnalyzer = $this->createMock(ResourceAnalyzer::class);

        // 创建测试用的访问者工厂
        $this->visitorFactory = new TestVisitorFactory();

        // 创建DependencyAnalyzer实例，并注入所有依赖
        $this->dependencyAnalyzer = new DependencyAnalyzer(
            $this->astManager,
            $this->reflectionService,
            $this->visitorFactory,
            $this->classDependencyAnalyzer,
            $this->functionDependencyAnalyzer,
            $this->resourceAnalyzer,
            new NullLogger()
        );
    }

    public function testGetAnalyzers(): void
    {
        // 验证获取分析器
        $this->assertSame($this->classDependencyAnalyzer, $this->dependencyAnalyzer->getClassDependencyAnalyzer());
        $this->assertSame($this->functionDependencyAnalyzer, $this->dependencyAnalyzer->getFunctionDependencyAnalyzer());
        $this->assertSame($this->resourceAnalyzer, $this->dependencyAnalyzer->getResourceAnalyzer());
        $this->assertSame($this->visitorFactory, $this->dependencyAnalyzer->getVisitorFactory());
    }

    public function testGetOptimizedFileOrder(): void
    {
        $entryFile = '/path/to/entry.php';
        $mustDeps = ['/path/to/must1.php', '/path/to/must2.php'];
        $usedDeps = ['/path/to/used1.php', '/path/to/used2.php'];
        $funcDeps = ['/path/to/func1.php', '/path/to/func2.php'];

        // 设置类依赖分析器的返回值
        $this->classDependencyAnalyzer->expects($this->once())
            ->method('analyzeMustDependencies')
            ->willReturn(array_merge($mustDeps, [$entryFile]));

        $this->classDependencyAnalyzer->expects($this->once())
            ->method('analyzeUsedDependencies')
            ->with($this->equalTo($mustDeps))
            ->willReturn(array_merge($usedDeps, [$entryFile]));

        // 设置函数依赖分析器的返回值
        $this->functionDependencyAnalyzer->expects($this->once())
            ->method('analyzeFunctionDependencies')
            ->willReturn(array_merge($funcDeps, [$entryFile]));

        // 调用测试方法
        $result = $this->dependencyAnalyzer->getOptimizedFileOrder($entryFile);

        // 验证结果
        $expected = array_unique(array_merge($mustDeps, $usedDeps, $funcDeps, [$entryFile]));
        $this->assertEquals($expected, $result);
    }

    public function testFindDependencies(): void
    {
        $fileName = '/path/to/test.php';
        $ast = ['node1', 'node2']; // 简化的AST

        // 设置访问者
        $mockClassVisitor = new MockClassVisitor($fileName);
        $mockClassVisitor->setUseClasses(['Class1', 'Class2']);
        $this->visitorFactory->setClassCollector($mockClassVisitor);

        $mockFunctionVisitor = new MockFunctionVisitor();
        $mockFunctionVisitor->setUsedFunctions(['function1']);
        $this->visitorFactory->setFunctionCollector($mockFunctionVisitor);

        // 模拟NodeTraverser
        $mockTraverser = $this->createMock(\PhpParser\NodeTraverser::class);
        $this->astManager->expects($this->once())
            ->method('createNodeTraverser')
            ->willReturn($mockTraverser);

        // 模拟traverse方法调用
        $mockTraverser->expects($this->once())
            ->method('traverse')
            ->with($ast)
            ->willReturn($ast);

        // 设置类依赖文件
        $classFiles = ['/path/to/class1.php', '/path/to/class2.php'];
        $this->classDependencyAnalyzer->expects($this->exactly(2))
            ->method('findClassFile')
            ->willReturnMap([
                ['Class1', $classFiles[0]],
                ['Class2', $classFiles[1]]
            ]);

        // 设置函数依赖文件
        $functionFiles = ['/path/to/function1.php'];
        $this->functionDependencyAnalyzer->expects($this->once())
            ->method('findFunctionFile')
            ->with('function1')
            ->willReturn($functionFiles[0]);

        // 调用测试方法
        $dependencies = iterator_to_array($this->dependencyAnalyzer->findDependencies($fileName, $ast));

        // 验证结果
        $expected = array_merge($classFiles, $functionFiles);
        $this->assertEquals($expected, $dependencies);
    }

    public function testFindUsedResources(): void
    {
        $fileName = '/path/to/test.php';
        $ast = ['node1', 'node2']; // 简化的AST
        $resources = ['/path/to/resource1.jpg', '/path/to/resource2.png'];

        // 设置资源分析器返回值
        $this->resourceAnalyzer->expects($this->once())
            ->method('findUsedResources')
            ->with($fileName, $ast)
            ->willReturn($this->yieldValues($resources));

        // 调用测试方法
        $result = iterator_to_array($this->dependencyAnalyzer->findUsedResources($fileName, $ast));

        // 验证结果
        $this->assertEquals($resources, $result);
    }

    /**
     * 辅助方法：生成一个可以yield指定值的生成器
     */
    private function yieldValues(array $values): \Generator
    {
        foreach ($values as $value) {
            yield $value;
        }
    }
}
