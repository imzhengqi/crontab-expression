<?php
declare(strict_types=1);

namespace zhengqi\cron\core;

use InvalidArgumentException;

/**
 * 表达式解析器
 */
class ExpressionParser
{
    private FieldParser $fieldParser;

    public function __construct()
    {
        $this->fieldParser = new FieldParser();
    }

    public function parse(string $expression): array
    {
        $parts = preg_split('/\s+/', trim($expression));

        // 自动补全秒字段
        if (count($parts) === 5) {
            array_unshift($parts, '*');
        }

        if (count($parts) !== 6) {
            throw new InvalidArgumentException("Invalid expression format");
        }

        return [
            'second' => $this->parseField('second', $parts[0]),
            'minute' => $this->parseField('minute', $parts[1]),
            'hour' => $this->parseField('hour', $parts[2]),
            'day' => $this->parseField('day', $parts[3]),
            'month' => $this->parseField('month', $parts[4]),
            'weekday' => $this->parseField('weekday', $parts[5])
        ];
    }

    private function parseField(string $type, string $value): array
    {
        return $this->fieldParser->parse($type, $value);
    }
}