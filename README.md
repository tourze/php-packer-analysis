# php-packer-analysis

[![Version](https://img.shields.io/badge/version-0.0.x-blue.svg)](https://packagist.org/packages/tourze/php-packer-analysis)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A PHP code analysis tool for dependency analysis, resource detection, and class relationship analysis. Designed for use in PHP monorepo or modular projects, it provides reliable dependency sorting, circular dependency detection, and resource usage analysis.

## Features

- Analyze class, function, and resource dependencies in PHP code
- Detect and report circular dependencies
- Topological sorting for optimized file order
- Resource usage extraction (e.g., files, assets)
- Extensible visitor-based architecture
- Integrates with AST and reflection services

## Installation

### Requirements

- PHP >= 8.1
- Composer

### Install via Composer

```bash
composer require tourze/php-packer-analysis
```

## Quick Start

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

## Documentation

- Provides API for dependency and resource analysis
- Customizable visitor classes for advanced scenarios
- See source code for advanced configuration and extension

## Contributing

- Submit issues and pull requests via GitHub
- Follow PSR code style and provide tests
- Run `composer test` before submitting PRs

## License

MIT License. Copyright (c) tourze.

## Changelog

See [Releases](https://github.com/tourze/php-packer-analysis/releases) for version history.
