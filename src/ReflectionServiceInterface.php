<?php

namespace PhpPacker\Analysis;

/**
 * 反射服务接口
 */
interface ReflectionServiceInterface
{
    /**
     * 获取类所在的文件路径
     *
     * @param string $className 完全限定的类名
     * @return string|null 文件路径，如果类不存在或匹配排除规则则返回null
     */
    public function getClassFileName(string $className): ?string;

    /**
     * 获取函数所在的文件路径
     *
     * @param string $functionName 函数名
     * @return string|null 文件路径，如果函数不存在或匹配排除规则则返回null
     */
    public function getFunctionFileName(string $functionName): ?string;

    /**
     * 获取文件排除规则列表
     *
     * @return array<string> 文件路径模式的列表，用于排除匹配的文件
     */
    public function getExcludePatterns(): array;
}
