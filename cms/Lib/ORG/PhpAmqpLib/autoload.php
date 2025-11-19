<?php

function classLoaderPhpAmqpLib($class)
{
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $path = str_replace('PhpAmqpLib\\', '', $path);
    $path = str_replace('PhpAmqpLib/', '', $path);
    $file = __DIR__ . DIRECTORY_SEPARATOR . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}
spl_autoload_register('classLoaderPhpAmqpLib');