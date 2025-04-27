# 数据库实体设计说明

本模块本身不直接包含数据库实体，仅依赖于 AST、依赖图、访问者等内存结构进行分析。

## 主要内存结构

- **DependencyGraph**：用于存储文件、类、函数等节点及其依赖关系的有向图结构。
- **ClassDependencyAnalyzer / FunctionDependencyAnalyzer / ResourceAnalyzer**：分别负责分析类、函数、资源的依赖关系。
- **UseClassCollectorVisitor / UseFunctionCollectorVisitor / UseResourceCollectorVisitor**：访问 AST 节点，收集依赖信息。

如需持久化分析结果，可根据实际业务需求扩展实体结构，建议设计如下：

| 实体名                | 字段                         | 说明               |
|----------------------|------------------------------|--------------------|
| dependency_analysis  | id, file, type, dependencies | 单次分析结果存储   |
| resource_usage       | id, file, resource_path      | 资源使用记录       |
| circular_dependency  | id, files                    | 循环依赖链路记录   |

> 本模块默认不包含数据库操作，所有依赖分析均在内存中完成。
