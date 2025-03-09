<?php

declare(strict_types=1);

namespace zhengqi\cron;

use Cron\AbstractField;
use Cron\FieldInterface;
use DateTimeInterface;

/**
 * Minutes field.  Allows: * , / -.
 */
class SecondsField extends AbstractField
{
    /**
     * {@inheritdoc}
     */
    protected $rangeStart = 0;

    /**
     * {@inheritdoc}
     */
    protected $rangeEnd = 59;

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(DateTimeInterface $date, $value, bool $invert): bool
    {
        if ($value === '?') {
            return true;
        }

        return $this->isSatisfied((int)$date->format('s'), $value);
    }

    /**
     * 根据表达式递增或递减给定的日期
     * @param DateTimeInterface $date
     * @param bool $invert 是否递减而不是递增
     * @param string|null $parts 表达式的各个部分
     * @return FieldInterface
     */
    public function increment(DateTimeInterface &$date, $invert = false, $parts = null): FieldInterface
    {
        if ($parts === null) {
            // 如果没有指定具体的秒数，则直接递增或递减一秒
            $date = $this->timezoneSafeModify($date, ($invert ? '-' : '+') . '1 second');
            return $this;
        }

        $currentSecond = (int)$date->format('s'); // 获取当前秒数
        // 将表达式拆分为多个部分，并获取每个部分对应的秒数范围
        $seconds = array_unique(array_merge(...array_map(function ($part) {
            return $this->getRangeForExpression($part, $this->rangeEnd);
        }, explode(',', $parts))));
        sort($seconds); // 对秒数进行排序

        if (!$invert) {
            // 正向递增
            foreach ($seconds as $second) {
                if ($second > $currentSecond) {
                    $distance = $second - $currentSecond; // 计算需要递增的距离
                    $date = $this->timezoneSafeModify($date, "+{$distance} seconds");
                    return $this;
                }
            }
            // 如果没有找到更大的秒数，则移动到下一分钟的第一个秒数
            $date = $this->timezoneSafeModify($date, '+1 minute');
            $date->setTime((int)$date->format('H'), (int)$date->format('i'), reset($seconds));
        } else {
            // 反向递减
            foreach (array_reverse($seconds) as $second) {
                if ($second < $currentSecond) {
                    $distance = $currentSecond - $second;
                    $date = $this->timezoneSafeModify($date, "-{$distance} seconds");
                    return $this;
                }
            }
            // 如果没有找到更小的秒数，则移动到上一分钟的最后一个秒数
            $date = $this->timezoneSafeModify($date, '-1 minute');
            $date->setTime((int)$date->format('H'), (int)$date->format('i'), end($seconds));
        }

        return $this;
    }
}
