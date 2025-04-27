# php-packer-analysis

[![版本](https://img.shields.io/badge/version-0.0.x-blue.svg)](https://packagist.org/packages/tourze/php-packer-analysis)
[![协议](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

一个用于 PHP 代码依赖分析、资源查找、类关系分析的工具。适用于 monorepo 或模块化项目，支持依赖排序、循环依赖检测和资源使用分析。

## 功能特性

- 分析 PHP 代码中的类、函数、资源依赖关系
- 检测并报告循环依赖
- 基于依赖关系的拓扑排序，优化文件顺序
- 提取代码中使用的资源（如文件、静态资源）
- 可扩展的访问者架构
- 集成 AST 与反射服务

## 安装说明

### 环境要求

- PHP >= 8.1
- Composer

### Composer 安装

```bash
composer require tourze/php-packer-analysis
```

## 快速开始

```php
use PhpPacker\Analysis\Dependency\DependencyAnalyzer;
use PhpPacker\Analysis\ReflectionService;
use PhpPacker\Ast\AstManager;

$astManager = new AstManager();
$reflectionService = new ReflectionService();
$analyzer = new DependencyAnalyzer($astManager, $reflectionService);

$order = $analyzer->getOptimizedFileOrder('path/to/entry.php');
foreach ($order as $file) {
    echo $file . PHP_EOL;
}
```

## 详细文档

- 提供依赖与资源分析 API
- 可自定义访问者类，适配高级场景
- 进阶用法请参考源码

## 贡献指南

- 通过 GitHub 提交 Issue 和 PR
- 遵循 PSR 代码规范并补充测试用例
- 提交 PR 前请运行 `composer test`

## 版权和许可

MIT 协议，版权所有 (c) tourze。

## 更新日志

详见 [Releases](https://github.com/tourze/php-packer-analysis/releases)。
