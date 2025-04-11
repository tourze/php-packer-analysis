<?php

namespace PhpPacker\Analysis\Dependency\FileSystem;

/**
 * 文件系统接口
 */
interface FileSystemInterface
{
    /**
     * 检查文件或目录是否存在
     *
     * @param string $path 文件或目录路径
     * @return bool 如果存在则返回true，否则返回false
     */
    public function exists(string $path): bool;
}
