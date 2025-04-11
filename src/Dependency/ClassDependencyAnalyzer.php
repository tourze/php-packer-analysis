<?php

namespace PhpPacker\Analysis\Dependency;

use PhpPacker\Analysis\Graph\DependencyGraph;
use PhpPacker\Analysis\Graph\GraphAnalyzerInterface;
use PhpPacker\Analysis\Graph\GraphInterface;
use PhpPacker\Analysis\ReflectionServiceInterface;
use PhpPacker\Analysis\Visitor\UseClassCollectorVisitor;
use PhpPacker\Ast\AstManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Json\Json;

/**
 * 类依赖分析器
 */
class ClassDependencyAnalyzer
{
    /**
     * AST管理器
     */
    private AstManagerInterface $astManager;

    /**
     * 反射服务
     */
    private ReflectionServiceInterface $reflectionService;

    /**
     * 图分析器
     */
    private GraphAnalyzerInterface $graphAnalyzer;

    /**
     * 日志记录器
     */
    private LoggerInterface $logger;

    /**
     * 使用的类收集器的类名
     */
    private string $classCollectorClass;

    /**
     * @param AstManagerInterface $astManager AST管理器
     * @param ReflectionServiceInterface $reflectionService 反射服务
     * @param GraphAnalyzerInterface $graphAnalyzer 图分析器
     * @param LoggerInterface|null $logger 日志记录器
     * @param string $classCollectorClass 使用的类收集器的类名
     */
    public function __construct(
        AstManagerInterface $astManager,
        ReflectionServiceInterface $reflectionService,
        GraphAnalyzerInterface $graphAnalyzer,
        ?LoggerInterface $logger = null,
        string $classCollectorClass = UseClassCollectorVisitor::class,
    )
    {
        $this->astManager = $astManager;
        $this->reflectionService = $reflectionService;
        $this->graphAnalyzer = $graphAnalyzer;
        $this->logger = $logger ?? new NullLogger();
        $this->classCollectorClass = $classCollectorClass;
    }

    /**
     * 分析必须类依赖（extends/implements等关系）
     *
     * @return array<string> 拓扑排序后的文件列表
     * @throws \PhpPacker\Analysis\Exception\CircularDependencyException 如果存在循环依赖
     */
    public function analyzeMustDependencies(): array
    {
        $this->logger->debug('Analyzing must class dependencies');

        $graph = $this->buildMustDependencyGraph();
        $this->debugGraphToFile($graph, 'latest-mustGraph.json');

        // 使用拓扑排序获取最终顺序
        $result = $this->graphAnalyzer->topologicalSort($graph, true, true);

        $this->logger->debug('Must class dependencies analysis completed', [
            'file_count' => count($result)
        ]);

        return $result;
    }

    /**
     * 分析可选类依赖（new/静态调用等）
     *
     * @param array<string> $processedFiles 已处理的文件
     * @return array<string> 处理后的文件列表
     */
    public function analyzeUsedDependencies(array $processedFiles): array
    {
        $this->logger->debug('Analyzing used class dependencies');

        $graph = $this->buildUsedDependencyGraph();
        $this->debugGraphToFile($graph, 'latest-usedGraph.json');

        $processedSet = array_flip($processedFiles); // 用于快速查找

        // 收集所有未处理的文件
        $remainingFiles = [];
        foreach ($graph->getNodes() as $file) {
            if (!isset($processedSet[$file])) {
                $remainingFiles[] = $file;
            }
        }

        // 对可选依赖进行拓扑排序（非严格模式，允许循环依赖）
        $sortedRemaining = $this->graphAnalyzer->topologicalSort($graph, true, false);

        // 只保留之前未处理的文件
        $result = [];
        foreach ($sortedRemaining as $file) {
            if (in_array($file, $remainingFiles, true)) {
                $result[] = $file;
            }
        }

        $this->logger->debug('Used class dependencies analysis completed', [
            'file_count' => count($result)
        ]);

        return $result;
    }

    /**
     * 构建必须依赖图
     *
     * @return GraphInterface 依赖图
     */
    private function buildMustDependencyGraph(): GraphInterface
    {
        $graph = new DependencyGraph($this->logger);

        foreach ($this->astManager->getAllAsts() as $file => $ast) {
            $traverser = $this->astManager->createNodeTraverser();

            $visitorClass = $this->classCollectorClass;
            $visitor = $this->createVisitor($visitorClass, $file);
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            if (empty($visitor->getMustDependClasses())) {
                continue;
            }

            $graph->addNode($file);

            foreach ($visitor->getMustDependClasses() as $class) {
                $_f = $this->reflectionService->getClassFileName($class);
                if ($_f && $_f !== $file) {
                    $graph->addEdge($file, $_f);
                }
            }
        }

        return $graph;
    }

    /**
     * 构建可选依赖图
     *
     * @return GraphInterface 依赖图
     */
    private function buildUsedDependencyGraph(): GraphInterface
    {
        $graph = new DependencyGraph($this->logger);

        foreach ($this->astManager->getAllAsts() as $file => $ast) {
            $traverser = $this->astManager->createNodeTraverser();

            $visitorClass = $this->classCollectorClass;
            $visitor = $this->createVisitor($visitorClass, $file);
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            if (empty($visitor->getUsedDependClasses())) {
                continue;
            }

            $graph->addNode($file);

            foreach ($visitor->getUsedDependClasses() as $class) {
                $_f = $this->reflectionService->getClassFileName($class);
                if ($_f && $_f !== $file) {
                    $graph->addEdge($file, $_f);
                }
            }
        }

        return $graph;
    }

    /**
     * 创建访问者实例
     *
     * @param string $visitorClass 访问者类名
     * @param string $file 文件名
     * @return object 访问者实例
     */
    protected function createVisitor(string $visitorClass, string $file): object
    {
        return new $visitorClass($file);
    }

    /**
     * 将图数据写入调试文件
     *
     * @param GraphInterface $graph 要调试的图
     * @param string $filename 输出文件名
     */
    private function debugGraphToFile(GraphInterface $graph, string $filename): void
    {
        file_put_contents($filename, Json::encode($graph->getGraphData()));
    }

    /**
     * 查找类所在的文件
     *
     * @param string $className 类名
     * @return string|null 文件路径，如果类不存在或匹配排除规则则返回null
     */
    public function findClassFile(string $className): ?string
    {
        return $this->reflectionService->getClassFileName($className);
    }
}
