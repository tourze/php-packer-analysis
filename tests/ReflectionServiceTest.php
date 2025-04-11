<?php

namespace PhpPacker\Analysis\Tests;

use PhpPacker\Analysis\ReflectionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ReflectionServiceTest extends TestCase
{
    private ReflectionService $reflectionService;

    protected function setUp(): void
    {
        $this->reflectionService = new ReflectionService([], new NullLogger());
    }

    public function testGetClassFileName(): void
    {
        // 测试当前项目中的类
        $fileName = $this->reflectionService->getClassFileName(self::class);
        $this->assertNotNull($fileName);
        $this->assertStringEndsWith('ReflectionServiceTest.php', $fileName);

        // 测试内置类
        $fileName = $this->reflectionService->getClassFileName(\stdClass::class);
        $this->assertNull($fileName);

        // 测试不存在的类
        $fileName = $this->reflectionService->getClassFileName('NonExistentClass');
        $this->assertNull($fileName);
    }

    public function testGetFunctionFileName(): void
    {
        // 由于匿名函数无法直接通过名称引用，先定义一个全局函数用于测试
        if (!function_exists('PhpPacker\Analysis\Tests\test_reflection_function')) {
            function test_reflection_function()
            {
                return true;
            }
        }

        // 测试项目中的函数
        $fileName = $this->reflectionService->getFunctionFileName('PhpPacker\Analysis\Tests\test_reflection_function');
        $this->assertNotNull($fileName);
        $this->assertStringEndsWith('ReflectionServiceTest.php', $fileName);

        // 测试内置函数
        $fileName = $this->reflectionService->getFunctionFileName('array_map');
        $this->assertNull($fileName);

        // 测试不存在的函数
        $fileName = $this->reflectionService->getFunctionFileName('non_existent_function');
        $this->assertNull($fileName);
    }

    public function testExcludePatterns(): void
    {
        // 设置排除模式
        $patterns = [
            '**/ReflectionServiceTest.php',
            '**/vendor/**'
        ];
        $this->reflectionService->setExcludePatterns($patterns);

        // 验证排除模式已设置
        $this->assertSame($patterns, $this->reflectionService->getExcludePatterns());

        // 测试排除模式是否生效
        $fileName = $this->reflectionService->getClassFileName(self::class);
        $this->assertNull($fileName);

        // 测试添加单个排除模式
        $this->reflectionService->setExcludePatterns([]);
        $this->reflectionService->addExcludePattern('**/Extra.php');
        $this->assertCount(1, $this->reflectionService->getExcludePatterns());
        $this->assertSame(['**/Extra.php'], $this->reflectionService->getExcludePatterns());
    }
}
