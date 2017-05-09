<?php
$mapping = array(
    'ClickFWD\Bitly\Bitly' => __DIR__ . '/Bitly/bitly.php',
    'Detection\MobileDetect' => __DIR__. '/Mobile-Detect-2.8.24/Mobile_Detect.php'
);

spl_autoload_register(function ($class) use ($mapping) {
    if (isset($mapping[$class]) && file_exists($mapping[$class])) {
        require $mapping[$class];
    }
}, true);

