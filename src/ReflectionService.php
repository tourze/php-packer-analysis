<?php

namespace PhpPacker\Analysis;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * 反射服务实现
 */
class ReflectionService implements ReflectionServiceInterface
{
    /**
     * 文件排除模式列表
     *
     * @var array<string>
     */
    private array $excludePatterns;

    /**
     * 日志记录器
     */
    private LoggerInterface $logger;

    /**
     * @param array<string> $excludePatterns 要排除的文件路径模式列表
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(array $excludePatterns = [], ?LoggerInterface $logger = null)
    {
        $this->excludePatterns = $excludePatterns;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function getClassFileName(string $className): ?string
    {
        try {
            $reflection = new \ReflectionClass($className);

            if (!$reflection->isUserDefined()) {
                $this->logger->debug('Skipped internal class', ['class' => $className]);
                return null; // 内置的类我们直接不处理
            }

            $fileName = $reflection->getFileName();

            if ($fileName === false) {
                return null;
            }

            foreach ($this->excludePatterns as $pattern) {
                if (fnmatch($pattern, $fileName)) {
                    $this->logger->debug('Skipped excluded class file', [
                        'class' => $className,
                        'file' => $fileName,
                        'pattern' => $pattern
                    ]);
                    return null; // 忽略匹配的文件
                }
            }

            $this->logger->debug('Found class file', [
                'class' => $className,
                'file' => $fileName
            ]);

            return $fileName;
        } catch (\Throwable $exception) {
            $this->logger->debug('Failed to reflect class', [
                'class' => $className,
                'exception' => $exception->getMessage()
            ]);
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctionFileName(string $functionName): ?string
    {
        try {
            $reflection = new \ReflectionFunction($functionName);

            if (!$reflection->isUserDefined()) {
                $this->logger->debug('Skipped internal function', ['function' => $functionName]);
                return null; // 内置的函数我们直接不处理
            }

            $fileName = $reflection->getFileName();

            if ($fileName === false) {
                return null;
            }

            foreach ($this->excludePatterns as $pattern) {
                if (fnmatch($pattern, $fileName)) {
                    $this->logger->debug('Skipped excluded function file', [
                        'function' => $functionName,
                        'file' => $fileName,
                        'pattern' => $pattern
                    ]);
                    return null; // 忽略匹配的文件
                }
            }

            $this->logger->debug('Found function file', [
                'function' => $functionName,
                'file' => $fileName
            ]);

            return $fileName;
        } catch (\Throwable $exception) {
            $this->logger->debug('Failed to reflect function', [
                'function' => $functionName,
                'exception' => $exception->getMessage()
            ]);
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExcludePatterns(): array
    {
        return $this->excludePatterns;
    }

    /**
     * 设置排除模式列表
     *
     * @param array<string> $patterns 文件路径模式列表
     * @return self 支持链式调用
     */
    public function setExcludePatterns(array $patterns): self
    {
        $this->excludePatterns = $patterns;
        return $this;
    }

    /**
     * 添加排除模式
     *
     * @param string $pattern 文件路径模式
     * @return self 支持链式调用
     */
    public function addExcludePattern(string $pattern): self
    {
        if (!in_array($pattern, $this->excludePatterns, true)) {
            $this->excludePatterns[] = $pattern;
        }
        return $this;
    }
}
