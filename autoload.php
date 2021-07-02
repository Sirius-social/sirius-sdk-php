<?php

require_once 'vendor/autoload.php';

spl_autoload_register(function ($class) {
    $SiruisToSrc = str_replace('Siruis\\', 'src/', $class);
    require str_replace('\\', '/', $SiruisToSrc) . '.php';
});