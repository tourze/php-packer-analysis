<?php

namespace PhpPacker\Analysis\Dependency\FileSystem;

/**
 * 真实文件系统实现
 */
class RealFileSystem implements FileSystemInterface
{
    public function exists(string $path): bool
    {
        return file_exists($path);
    }
}
