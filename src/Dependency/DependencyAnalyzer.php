<?php

namespace PhpPacker\Analysis\Dependency;

use PhpPacker\Analysis\ReflectionServiceInterface;
use PhpPacker\Analysis\Visitor\DefaultVisitorFactory;
use PhpPacker\Analysis\Visitor\VisitorFactoryInterface;
use PhpPacker\Ast\AstManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * 主依赖分析器
 */
class DependencyAnalyzer implements DependencyAnalyzerInterface
{
    /**
     * AST管理器
     */
    private AstManagerInterface $astManager;

    /**
     * 日志记录器
     */
    private LoggerInterface $logger;

    /**
     * 类依赖分析器
     */
    private ClassDependencyAnalyzer $classDependencyAnalyzer;

    /**
     * 函数依赖分析器
     */
    private FunctionDependencyAnalyzer $functionDependencyAnalyzer;

    /**
     * 资源分析器
     */
    private ResourceAnalyzer $resourceAnalyzer;

    /**
     * 访问者工厂
     */
    private VisitorFactoryInterface $visitorFactory;

    /**
     * @param AstManagerInterface $astManager AST管理器
     * @param ReflectionServiceInterface $reflectionService 反射服务
     * @param VisitorFactoryInterface|null $visitorFactory 访问者工厂
     * @param ClassDependencyAnalyzer|null $classDependencyAnalyzer 类依赖分析器
     * @param FunctionDependencyAnalyzer|null $functionDependencyAnalyzer 函数依赖分析器  
     * @param ResourceAnalyzer|null $resourceAnalyzer 资源分析器
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(
        AstManagerInterface $astManager,
        ReflectionServiceInterface $reflectionService,
        ?VisitorFactoryInterface $visitorFactory = null,
        ?ClassDependencyAnalyzer $classDependencyAnalyzer = null,
        ?FunctionDependencyAnalyzer $functionDependencyAnalyzer = null,
        ?ResourceAnalyzer $resourceAnalyzer = null,
        ?LoggerInterface $logger = null
    ) {
        $this->astManager = $astManager;
        $this->logger = $logger ?? new NullLogger();
        $this->visitorFactory = $visitorFactory ?? new DefaultVisitorFactory();

        // 如果未提供依赖分析器，则创建默认实例
        $graphAnalyzer = new \PhpPacker\Analysis\Graph\GraphAnalyzer($this->logger);

        $this->classDependencyAnalyzer = $classDependencyAnalyzer ?? new ClassDependencyAnalyzer(
            $this->astManager,
            $reflectionService,
            $graphAnalyzer,
            $this->logger,
            get_class($this->visitorFactory->createClassCollector(''))
        );

        $this->functionDependencyAnalyzer = $functionDependencyAnalyzer ?? new FunctionDependencyAnalyzer(
            $this->astManager,
            $reflectionService,
            $this->logger,
            get_class($this->visitorFactory->createFunctionCollector())
        );

        $this->resourceAnalyzer = $resourceAnalyzer ?? new ResourceAnalyzer(
            $this->astManager,
            $this->logger,
            get_class($this->visitorFactory->createResourceCollector(''))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOptimizedFileOrder(string $entryFile): array
    {
        $this->logger->info('Starting dependency analysis for entry file', [
            'entry_file' => $entryFile
        ]);

        // 第一步：处理必需类依赖
        $mustResult = $this->classDependencyAnalyzer->analyzeMustDependencies();
        $mustResult = array_diff($mustResult, [$entryFile]);
        $this->logger->debug('Step 1: Must class dependencies analyzed', [
            'file_count' => count($mustResult)
        ]);

        // 第二步：处理可选类依赖
        $usedResult = $this->classDependencyAnalyzer->analyzeUsedDependencies($mustResult);
        $usedResult = array_diff($usedResult, [$entryFile]);
        $this->logger->debug('Step 2: Used class dependencies analyzed', [
            'file_count' => count($usedResult)
        ]);

        // 第三步：处理函数依赖，函数一般可以放到比较后面
        $funcResult = $this->functionDependencyAnalyzer->analyzeFunctionDependencies();
        $funcResult = array_diff($funcResult, [$entryFile]);
        $this->logger->debug('Step 3: Function dependencies analyzed', [
            'file_count' => count($funcResult)
        ]);

        // 合并结果
        $result = array_unique(array_merge($mustResult, $usedResult, $funcResult, [$entryFile]));

        $this->logger->info('Dependency analysis completed', [
            'total_file_count' => count($result)
        ]);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function findDependencies(string $fileName, array $ast): \Traversable
    {
        $traverser = $this->astManager->createNodeTraverser();

        // 添加类依赖访问者
        $classVisitor = $this->visitorFactory->createClassCollector($fileName);
        $traverser->addVisitor($classVisitor);

        // 添加函数依赖访问者
        $funcVisitor = $this->visitorFactory->createFunctionCollector();
        $traverser->addVisitor($funcVisitor);

        $traverser->traverse($ast);

        // 收集所有类依赖
        foreach ($classVisitor->getUseClasses() as $class) {
            $_f = $this->classDependencyAnalyzer->findClassFile($class);
            if ($_f) {
                yield $_f;
            }
        }

        // 收集所有函数依赖
        foreach ($funcVisitor->getUsedFunctions() as $function) {
            $_f = $this->functionDependencyAnalyzer->findFunctionFile($function);
            if ($_f) {
                yield $_f;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findUsedResources(string $fileName, array $ast): \Traversable
    {
        return $this->resourceAnalyzer->findUsedResources($fileName, $ast);
    }

    /**
     * 获取类依赖分析器
     */
    public function getClassDependencyAnalyzer(): ClassDependencyAnalyzer
    {
        return $this->classDependencyAnalyzer;
    }

    /**
     * 获取函数依赖分析器
     */
    public function getFunctionDependencyAnalyzer(): FunctionDependencyAnalyzer
    {
        return $this->functionDependencyAnalyzer;
    }

    /**
     * 获取资源分析器
     */
    public function getResourceAnalyzer(): ResourceAnalyzer
    {
        return $this->resourceAnalyzer;
    }

    /**
     * 获取访问者工厂
     */
    public function getVisitorFactory(): VisitorFactoryInterface
    {
        return $this->visitorFactory;
    }
}
