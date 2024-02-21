<?php

$myprocess = new \Controllers\Process("sed -i s#resources/icons/#assets/icons/#g " . LOGS_DIR . '/main/*.log');
$myprocess->execute();
$myprocess->close();

$myprocess = new \Controllers\Process("sed -i s#resources/images/#assets/images/#g " . LOGS_DIR . '/main/*.log');
$myprocess->execute();
$myprocess->close();
