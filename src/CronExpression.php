<?php
declare(strict_types=1);

namespace zhengqi\cron;

use DateTimeInterface;
use Throwable;
use zhengqi\cron\core\ExpressionParser;
use zhengqi\cron\core\NextTimeCalculator;

class CronExpression
{
    private array $schedule;
    private NextTimeCalculator $calculator;

    public function __construct(string $expression)
    {
        $parser = new ExpressionParser();
        $this->schedule = $parser->parse($expression);
        $this->calculator = new NextTimeCalculator($this->schedule);
    }

    public function getNextRunDate(?DateTimeInterface $from = null): DateTimeInterface
    {
        return $this->calculator->calculate($from);
    }

    public static function create(string $expression): self
    {
        return new self($expression);
    }

    /**
     * 验证表达式是否有效
     * @param string $expression 要验证的crontab表达式
     * @return bool 是否有效
     */
    public static function isValid(string $expression): bool
    {
        try {
            new self($expression);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}