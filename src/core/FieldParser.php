<?php
declare(strict_types=1);

namespace zhengqi\cron\core;

use InvalidArgumentException;

/**
 * 字段解析器
 */
class FieldParser
{
    private array $ranges = [
        'second' => [0, 59],
        'minute' => [0, 59],
        'hour' => [0, 23],
        'day' => [1, 31],
        'month' => [1, 12],
        'weekday' => [0, 6]
    ];

    /**
     * 月份别名
     */
    private const MONTH_ALIASES = [
        'JAN' => 1, 'FEB' => 2, 'MAR' => 3,
        'APR' => 4, 'MAY' => 5, 'JUN' => 6,
        'JUL' => 7, 'AUG' => 8, 'SEP' => 9,
        'OCT' => 10, 'NOV' => 11, 'DEC' => 12
    ];

    /**
     * 周别名
     */
    private const WEEKDAY_ALIASES = [
        'SUN' => 0, 'MON' => 1, 'TUE' => 2,
        'WED' => 3, 'THU' => 4, 'FRI' => 5, 'SAT' => 6
    ];

    public function parse(string $field, string $value): array
    {
        $value = $this->normalizeAliases($field, $value);
        $parts = explode(',', $value);
        $values = [];

        foreach ($parts as $part) {
            $values = array_merge(
                $values,
                $this->parsePart($field, $part)
            );
        }

        $values = array_unique($values);
        sort($values);

        if (empty($values)) {
            throw new InvalidArgumentException("Invalid $field value: $value");
        }

        return $values;
    }

    private function parsePart(string $field, string $part): array
    {
        if ($part === '*') {
            return range(...$this->ranges[$field]);
        }

        if (str_contains($part, '/')) {
            return $this->parseStep($field, $part);
        }

        if (str_contains($part, '-')) {
            return $this->parseRange($field, $part);
        }

        return [$this->validateValue($field, (int)$part)];
    }

    private function parseStep(string $field, string $part): array
    {
        [$range, $step] = explode('/', $part);
        $step = (int)$step;

        if ($step < 1) {
            throw new InvalidArgumentException("Step must be ≥1 in $field");
        }

        $start = $range === '*' ? $this->ranges[$field][0] : (int)$range;
        $end = $this->ranges[$field][1];

        return range($start, $end, $step);
    }

    private function parseRange(string $field, string $part): array
    {
        [$start, $end] = explode('-', $part);
        $start = $this->validateValue($field, (int)$start);
        $end = $this->validateValue($field, (int)$end);

        if ($start > $end) {
            throw new InvalidArgumentException("Invalid range in $field");
        }

        return range($start, $end);
    }

    private function validateValue(string $field, int $value): int
    {
        $min = $this->ranges[$field][0];
        $max = $this->ranges[$field][1];

        if ($value < $min || $value > $max) {
            throw new InvalidArgumentException("$field value out of range");
        }

        return $value;
    }

    private function normalizeAliases(string $field, string $value): string
    {
        $maps = match ($field) {
            'month' => self::MONTH_ALIASES,
            'weekday' => self::WEEKDAY_ALIASES,
            default => []
        };

        return str_ireplace(array_keys($maps), array_values($maps), $value);
    }
}