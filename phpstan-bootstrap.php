<?php
// phpstan bootstrap: map production ROOT to local workspace `www` folder
// This file is only used by PHPStan to resolve absolute runtime paths
if (!defined('ROOT')) {
    define('ROOT', __DIR__ . '/www');
}
