<?php

namespace PhpPacker\Analysis\Tests\Dependency;

use PhpPacker\Analysis\Dependency\ResourceAnalyzer;
use PhpPacker\Analysis\Tests\Visitor\MockResourceVisitor;
use PhpPacker\Ast\AstManagerInterface;
use PhpPacker\Visitor\UseResourceCollectorVisitor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ResourceAnalyzerTest extends TestCase
{
    private ResourceAnalyzer $resourceAnalyzer;
    /** @var AstManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $astManager;

    protected function setUp(): void
    {
        $this->astManager = $this->createMock(AstManagerInterface::class);
        $this->resourceAnalyzer = new ResourceAnalyzer(
            $this->astManager,
            new NullLogger()
        );
    }

    public function testFindUsedResources(): void
    {
        $fileName = '/path/to/test.php';
        $ast = ['node1', 'node2']; // 简化的AST结构
        $resourcePath1 = '/path/to/image.jpg';
        $resourcePath2 = '/path/to/style.css';

        // 创建文件系统模拟
        $fileSystem = $this->createMock(\PhpPacker\Analysis\Dependency\FileSystem\FileSystemInterface::class);
        // 配置文件系统模拟返回所有资源都存在
        $fileSystem->expects($this->exactly(2))
            ->method('exists')
            ->willReturn(true);

        // 创建一个能够替换createVisitor方法的子类
        $resourceAnalyzer = $this->getMockBuilder(ResourceAnalyzer::class)
            ->setConstructorArgs([
                $this->astManager,
                new NullLogger(),
                UseResourceCollectorVisitor::class,
                $fileSystem
            ])
            ->onlyMethods(['createVisitor'])
            ->getMock();

        // 模拟visitor
        $mockVisitor = new MockResourceVisitor($fileName);
        $mockVisitor->setResources([$resourcePath1, $resourcePath2]);

        $resourceAnalyzer->expects($this->once())
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
        $resources = iterator_to_array($resourceAnalyzer->findUsedResources($fileName, $ast));

        // 验证结果
        $this->assertCount(2, $resources);
        $this->assertContains($resourcePath1, $resources);
        $this->assertContains($resourcePath2, $resources);
    }

    public function testCollectUsedResourcesWithNoFiles(): void
    {
        // 测试空文件列表
        $resources = $this->invokeMethod($this->resourceAnalyzer, 'collectUsedResources', [[]]);
        $this->assertEmpty($resources);
    }

    public function testCollectUsedResourcesWithFiles(): void
    {
        // 创建一个能够替换findUsedResources方法的子类
        $resourceAnalyzer = $this->getMockBuilder(ResourceAnalyzer::class)
            ->setConstructorArgs([
                $this->astManager,
                new NullLogger()
            ])
            ->onlyMethods(['findUsedResources'])
            ->getMock();

        $file1 = '/path/to/file1.php';
        $file2 = '/path/to/file2.php';
        $resource1 = '/path/to/resource1.jpg';
        $resource2 = '/path/to/resource2.css';
        $resource3 = '/path/to/resource3.js';

        // 模拟findUsedResources方法返回资源
        $resourceAnalyzer->expects($this->exactly(2))
            ->method('findUsedResources')
            ->willReturnMap([
                [$file1, [], $this->yieldValues([$resource1, $resource2])],
                [$file2, [], $this->yieldValues([$resource2, $resource3])]
            ]);

        // 模拟AST
        $this->astManager->expects($this->exactly(2))
            ->method('getAst')
            ->willReturnMap([
                [$file1, []],
                [$file2, []]
            ]);

        // 执行测试
        $result = $this->invokeMethod($resourceAnalyzer, 'collectUsedResources', [[$file1, $file2]]);

        // 验证结果
        $this->assertCount(3, $result);
        $this->assertContains($resource1, $result);
        $this->assertContains($resource2, $result);
        $this->assertContains($resource3, $result);
    }

    public function testLogWarnOnNonexistentFile(): void
    {
        // 创建包含logger的mock
        /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
        $logger = $this->createMock(LoggerInterface::class);
        $resourceAnalyzer = new ResourceAnalyzer(
            $this->astManager,
            $logger
        );

        // 设置期望调用warning方法
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('AST not found for file'));

        // 模拟getAst返回null
        $this->astManager->expects($this->once())
            ->method('getAst')
            ->willReturn(null);

        // 执行测试
        $result = $this->invokeMethod($resourceAnalyzer, 'collectUsedResources', [['/path/to/nonexistent.php']]);

        // 验证结果
        $this->assertEmpty($result);
    }

    /**
     * 辅助方法：调用对象的私有或受保护方法
     *
     * @param object $object 要调用方法的对象
     * @param string $methodName 方法名
     * @param array $parameters 参数列表
     * @return mixed 方法返回值
     */
    private function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
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
