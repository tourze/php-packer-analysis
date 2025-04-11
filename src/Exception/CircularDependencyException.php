<?php

namespace PhpPacker\Analysis\Exception;

/**
 * 循环依赖异常
 */
class CircularDependencyException extends AnalysisException
{
    /**
     * 保存发现的循环依赖
     *
     * @var array<int, array<int, string>>
     */
    private array $cycles;

    /**
     * @param string $message 错误消息
     * @param array<int, array<int, string>> $cycles 发现的循环依赖列表
     * @param int $code 错误代码
     * @param \Throwable|null $previous 前一个异常
     */
    public function __construct(
        string      $message = '',
        array       $cycles = [],
        int         $code = 0,
        ?\Throwable $previous = null
    )
    {
        $this->cycles = $cycles;
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取发现的循环依赖列表
     *
     * @return array<int, array<int, string>> 循环依赖列表
     */
    public function getCycles(): array
    {
        return $this->cycles;
    }

    /**
     * 获取格式化的循环依赖信息
     *
     * @return string 格式化的循环依赖描述
     */
    public function getFormattedCycles(): string
    {
        $result = '';
        foreach ($this->cycles as $cycle) {
            $result .= "\n" . implode(' -> ', $cycle) . ' -> ' . $cycle[0];
        }
        return $result;
    }
}
