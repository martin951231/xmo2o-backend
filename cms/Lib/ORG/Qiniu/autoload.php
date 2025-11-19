<?php
function classLoaderQiniu($class)
{
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
	$path = str_replace('Qiniu\\', '', $path);
    $path = str_replace('Qiniu/', '', $path);
    $file = __DIR__ . '/' . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}
spl_autoload_register('classLoaderQiniu');

require_once  __DIR__ . '/functions.php';
