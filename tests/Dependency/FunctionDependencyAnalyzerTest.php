<?php

namespace PhpPacker\Analysis\Tests\Dependency;

use PhpPacker\Analysis\Dependency\FunctionDependencyAnalyzer;
use PhpPacker\Analysis\ReflectionServiceInterface;
use PhpPacker\Analysis\Tests\Visitor\MockFunctionVisitor;
use PhpPacker\Ast\AstManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class FunctionDependencyAnalyzerTest extends TestCase
{
    /** @var AstManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $astManager;

    /** @var ReflectionServiceInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $reflectionService;

    private FunctionDependencyAnalyzer $functionAnalyzer;

    protected function setUp(): void
    {
        $this->astManager = $this->createMock(AstManagerInterface::class);
        $this->reflectionService = $this->createMock(ReflectionServiceInterface::class);

        $this->functionAnalyzer = new FunctionDependencyAnalyzer(
            $this->astManager,
            $this->reflectionService,
            new NullLogger()
        );
    }

    public function testFindFunctionFile(): void
    {
        $functionName = 'test_function';
        $expectedFile = '/path/to/functions.php';

        $this->reflectionService->expects($this->once())
            ->method('getFunctionFileName')
            ->with($functionName)
            ->willReturn($expectedFile);

        $result = $this->functionAnalyzer->findFunctionFile($functionName);
        $this->assertEquals($expectedFile, $result);
    }

    public function testAnalyzeFunctionDependencies(): void
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
        $functionAnalyzer = $this->getMockBuilder(FunctionDependencyAnalyzer::class)
            ->setConstructorArgs([
                $this->astManager,
                $this->reflectionService,
                new NullLogger()
            ])
            ->onlyMethods(['createVisitor'])
            ->getMock();

        // 模拟createVisitor方法的行为
        $visitor1 = new MockFunctionVisitor();
        $visitor1->setUsedFunctions(['func1', 'func2']);

        $visitor2 = new MockFunctionVisitor();
        $visitor2->setUsedFunctions(['func3']);

        $functionAnalyzer->expects($this->exactly(2))
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

        // 设置反射服务返回函数文件
        $this->reflectionService->expects($this->exactly(3))
            ->method('getFunctionFileName')
            ->willReturnMap([
                ['func1', '/path/to/func1.php'],
                ['func2', '/path/to/func2.php'],
                ['func3', '/path/to/func3.php']
            ]);

        // 执行测试
        $result = $functionAnalyzer->analyzeFunctionDependencies();

        // 验证结果
        $this->assertContains($file1, $result);
        $this->assertContains($file2, $result);
        $this->assertContains('/path/to/func1.php', $result);
        $this->assertContains('/path/to/func2.php', $result);
        $this->assertContains('/path/to/func3.php', $result);
    }

    public function testGetFileFunctions(): void
    {
        $fileName = '/path/to/test.php';
        $ast = ['node1', 'node2']; // 简化的AST

        // 创建一个能够替换createVisitor方法的子类
        $functionAnalyzer = $this->getMockBuilder(FunctionDependencyAnalyzer::class)
            ->setConstructorArgs([
                $this->astManager,
                $this->reflectionService,
                new NullLogger()
            ])
            ->onlyMethods(['createVisitor'])
            ->getMock();

        // 模拟visitor
        $mockVisitor = new MockFunctionVisitor();
        $mockVisitor->setUsedFunctions(['function1', 'function2']);

        $functionAnalyzer->expects($this->once())
            ->method('createVisitor')
            ->willReturn($mockVisitor);

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

        // 执行测试
        $result = $functionAnalyzer->getFileFunctions($fileName, $ast);

        // 验证结果
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains('function1', $result);
        $this->assertContains('function2', $result);
    }
}
