<?php
declare(strict_types=1);

namespace zhengqi\cron\core;

use DateTime;
use DateTimeInterface;
use RuntimeException;

/**
 * 下次时间计算
 */
class NextTimeCalculator
{
    private array $schedule;

    public function __construct(array $schedule)
    {
        $this->schedule = $schedule;
    }

    public function calculate(?DateTimeInterface $from): DateTime
    {
        $from = $from ?? new DateTime();
        $current = DateTime::createFromInterface($from);
        $current->modify('+1 second');
        $maxDate = (clone $current)->modify('+5 years');

        while ($current <= $maxDate) {
            // 月份不匹配时跳到下个有效月份
            if (!$this->matchMonth($current)) {
                $this->incrementMonth($current);
                continue;
            }

            // 日期不匹配时跳到下个有效日
            if (!$this->matchDay($current)) {
                $this->incrementDay($current);
                continue;
            }

            // 时间部分不匹配时递增时间
            if (!$this->matchTime($current)) {
                $this->incrementTime($current);
                continue;
            }

            return $current;
        }

        throw new RuntimeException('No valid time found in 5 years');
    }

    private function matchMonth(DateTime $date): bool
    {
        $currentMonth = (int)$date->format('n');
        return in_array($currentMonth, $this->schedule['month'], true);
    }

    private function matchDay(DateTime $date): bool
    {
        $day = (int)$date->format('j');
        $weekday = (int)$date->format('w');
        return in_array($day, $this->schedule['day'], true)
            || in_array($weekday, $this->schedule['weekday'], true);
    }

    private function matchTime(DateTime $date): bool
    {
        return in_array((int)$date->format('H'), $this->schedule['hour'], true)
            && in_array((int)$date->format('i'), $this->schedule['minute'], true)
            && in_array((int)$date->format('s'), $this->schedule['second'], true);
    }

    private function incrementMonth(DateTime &$date): void
    {
        $currentMonth = (int)$date->format('n');
        $nextMonth = $this->findNextValue($currentMonth + 1, $this->schedule['month'], 12);

        if ($nextMonth === null) {
            $date->modify('first day of next year')->setDate(
                (int)$date->format('Y') + 1,
                1,
                1
            );
        } else {
            $date->setDate(
                (int)$date->format('Y'),
                $nextMonth,
                1
            );
        }
        $date->setTime(0, 0, 0);
    }

    private function incrementDay(DateTime &$date): void
    {
        $date->modify('+1 day')->setTime(0, 0, 0);
    }

    private function incrementTime(DateTime &$date): void
    {
        // 处理秒级递增
        $nextSecond = $this->findNextValue(
            (int)$date->format('s') + 1,
            $this->schedule['second'],
            59
        );

        if ($nextSecond !== null) {
            $date->setTime(
                (int)$date->format('H'),
                (int)$date->format('i'),
                $nextSecond
            );
            return;
        }

        // 处理分钟级递增
        $nextMinute = $this->findNextValue(
            (int)$date->format('i') + 1,
            $this->schedule['minute'],
            59
        );

        if ($nextMinute !== null) {
            $date->setTime(
                (int)$date->format('H'),
                $nextMinute,
                $this->schedule['second'][0]
            );
            return;
        }

        // 处理小时级递增
        $nextHour = $this->findNextValue(
            (int)$date->format('H') + 1,
            $this->schedule['hour'],
            23
        );

        if ($nextHour !== null) {
            $date->setTime(
                $nextHour,
                $this->schedule['minute'][0],
                $this->schedule['second'][0]
            );
            return;
        }

        // 进入下一天
        $date->modify('+1 day')->setTime(
            $this->schedule['hour'][0],
            $this->schedule['minute'][0],
            $this->schedule['second'][0]
        );
    }

    private function findNextValue(int $start, array $sortedValues, int $max): ?int
    {
        // 边界检查
        if ($start > $max) {
            return null;
        }

        foreach ($sortedValues as $value) {
            if ($value >= $start) {
                return $value;
            }
        }

        return $sortedValues[0] ?? null;
    }
}