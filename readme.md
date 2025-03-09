
## 使用
```
<?php

$expression = '0 0 2 * 10 *';

$isValid = CronExpression::isValidExpression($expression);

$cron = new CronExpression($expression);

$cron->getNextRunDate()->format('Y-m-d H:i:s');

```
