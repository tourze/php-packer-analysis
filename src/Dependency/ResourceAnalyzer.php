<?php

namespace PhpPacker\Analysis\Dependency;

use PhpPacker\Analysis\Dependency\FileSystem\FileSystemInterface;
use PhpPacker\Analysis\Dependency\FileSystem\RealFileSystem;
use PhpPacker\Ast\AstManagerInterface;
use PhpPacker\Visitor\UseResourceCollectorVisitor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * 资源分析器
 */
class ResourceAnalyzer
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
     * 资源收集访问者类名
     */
    private string $resourceCollectorClass;

    /**
     * 文件系统接口
     */
    private FileSystemInterface $fileSystem;

    /**
     * @param AstManagerInterface $astManager AST管理器
     * @param LoggerInterface|null $logger 日志记录器
     * @param string $resourceCollectorClass 资源收集访问者类名
     * @param FileSystemInterface|null $fileSystem 文件系统接口
     */
    public function __construct(
        AstManagerInterface $astManager,
        ?LoggerInterface $logger = null,
        string $resourceCollectorClass = UseResourceCollectorVisitor::class,
        ?FileSystemInterface $fileSystem = null,
    )
    {
        $this->astManager = $astManager;
        $this->logger = $logger ?? new NullLogger();
        $this->resourceCollectorClass = $resourceCollectorClass;
        $this->fileSystem = $fileSystem ?? new RealFileSystem();
    }

    /**
     * 查找文件中使用的资源
     *
     * @param string $fileName 文件路径
     * @param array $ast 文件的AST
     * @return \Traversable<string> 使用的资源文件路径生成器
     */
    public function findUsedResources(string $fileName, array $ast): \Traversable
    {
        $traverser = $this->astManager->createNodeTraverser();

        $visitorClass = $this->resourceCollectorClass;
        $visitor = $this->createVisitor($visitorClass, $fileName);
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        foreach ($visitor->getResources() as $resource) {
            if (!$this->fileSystem->exists($resource)) {
                $this->logger->warning('Resource not found', [
                    'resource' => $resource,
                    'file' => $fileName
                ]);
                continue;
            }

            $this->logger->debug('Found resource', [
                'resource' => $resource,
                'file' => $fileName
            ]);

            yield $resource;
        }
    }

    /**
     * 获取多个文件中使用的所有资源
     *
     * @param array<string> $files 文件路径列表
     * @return array<string> 使用的资源文件路径列表
     */
    public function collectUsedResources(array $files): array
    {
        $this->logger->debug('Collecting resources from files', [
            'file_count' => count($files)
        ]);

        $resources = [];
        foreach ($files as $file) {
            $ast = $this->astManager->getAst($file);
            if ($ast === null) {
                $this->logger->warning('AST not found for file', ['file' => $file]);
                continue;
            }

            foreach ($this->findUsedResources($file, $ast) as $resource) {
                $resources[] = $resource;
            }
        }

        $result = array_unique($resources);
        $this->logger->debug('Resources collection completed', [
            'resource_count' => count($result)
        ]);

        return $result;
    }

    /**
     * 创建访问者实例
     *
     * @param string $visitorClass 访问者类名
     * @param string $fileName 文件名
     * @return object 访问者实例
     */
    protected function createVisitor(string $visitorClass, string $fileName): object
    {
        return new $visitorClass($fileName);
    }
}
