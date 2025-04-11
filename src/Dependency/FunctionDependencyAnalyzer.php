<?php

namespace PhpPacker\Analysis\Dependency;

use PhpPacker\Analysis\ReflectionServiceInterface;
use PhpPacker\Analysis\Visitor\UseFunctionCollectorVisitor;
use PhpPacker\Ast\AstManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * 函数依赖分析器
 */
class FunctionDependencyAnalyzer
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
     * 日志记录器
     */
    private LoggerInterface $logger;

    /**
     * 使用的函数收集器的类名
     */
    private string $functionCollectorClass;

    /**
     * @param AstManagerInterface $astManager AST管理器
     * @param ReflectionServiceInterface $reflectionService 反射服务
     * @param LoggerInterface|null $logger 日志记录器
     * @param string $functionCollectorClass 使用的函数收集器的类名
     */
    public function __construct(
        AstManagerInterface $astManager,
        ReflectionServiceInterface $reflectionService,
        ?LoggerInterface $logger = null,
        string $functionCollectorClass = UseFunctionCollectorVisitor::class,
    )
    {
        $this->astManager = $astManager;
        $this->reflectionService = $reflectionService;
        $this->logger = $logger ?? new NullLogger();
        $this->functionCollectorClass = $functionCollectorClass;
    }

    /**
     * 分析函数依赖
     *
     * @return array<string> 函数依赖文件列表
     */
    public function analyzeFunctionDependencies(): array
    {
        $this->logger->debug('Analyzing function dependencies');

        $funcGraph = [];
        foreach ($this->astManager->getAllAsts() as $file => $ast) {
            $traverser = $this->astManager->createNodeTraverser();

            $visitorClass = $this->functionCollectorClass;
            $visitor = $this->createVisitor($visitorClass);
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            if (empty($visitor->getUsedFunctions())) {
                continue;
            }

            $funcGraph[] = $file;

            foreach ($visitor->getUsedFunctions() as $function) {
                $_f = $this->reflectionService->getFunctionFileName($function);
                if ($_f && !in_array($_f, $funcGraph, true)) {
                    $funcGraph[] = $_f;
                }
            }
        }

        file_put_contents('latest-funcGraph.json', \Yiisoft\Json\Json::encode($funcGraph));

        $result = array_unique($funcGraph);
        $this->logger->debug('Function dependencies analysis completed', [
            'file_count' => count($result)
        ]);

        return $result;
    }

    /**
     * 获取文件中使用的函数
     *
     * @param string $fileName 文件路径
     * @param array $ast 文件的AST
     * @return array<string> 使用的函数列表
     */
    public function getFileFunctions(string $fileName, array $ast): array
    {
        $traverser = $this->astManager->createNodeTraverser();

        $visitorClass = $this->functionCollectorClass;
        $visitor = $this->createVisitor($visitorClass);
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getUsedFunctions();
    }

    /**
     * 查找函数所在的文件
     *
     * @param string $functionName 函数名
     * @return string|null 文件路径，如果函数不存在或匹配排除规则则返回null
     */
    public function findFunctionFile(string $functionName): ?string
    {
        return $this->reflectionService->getFunctionFileName($functionName);
    }

    /**
     * 创建访问者实例
     *
     * @param string $visitorClass 访问者类名
     * @return object 访问者实例
     */
    protected function createVisitor(string $visitorClass): object
    {
        return new $visitorClass();
    }
}
