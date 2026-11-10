<?php
header('Content-type: text/plain; charset=utf-8');

if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    function merchant_allinpay_stripslashes_deep($value)
    {
        return is_array($value)
            ? array_map('merchant_allinpay_stripslashes_deep', $value)
            : stripslashes($value);
    }
    $_POST = array_map('merchant_allinpay_stripslashes_deep', $_POST);
    $_GET = array_map('merchant_allinpay_stripslashes_deep', $_GET);
    $_COOKIE = array_map('merchant_allinpay_stripslashes_deep', $_COOKIE);
}

define('APP_NAME', 'cms');
define('APP_PATH', '../cms/');
define('CONF_PATH', '../conf/');
define('RUNTIME_PATH', '../runtime/');
define('TMPL_PATH', '../tpl/');
define('APP_DEBUG', true);
define('MEMORY_LIMIT_ON', function_exists('memory_get_usage'));

$_GET['g'] = 'Appapi';
$_GET['c'] = 'MerchantAllinpay';
$_GET['a'] = 'notify';

define('RUNTIME_FILE', RUNTIME_PATH . '~MerchantAllinpay_source_runtime.php');
define('THINK_PATH', dirname(__FILE__) . '/../core/');
require THINK_PATH . 'Common/runtime.php';
