<?php

include_once 'Autoloader.php';
//include_once 'HttpHelper.php';
//include_once 'HttpResponse.php';
//include_once 'ClientException.php';


//config sdk auto load path.
Autoloader::addAutoloadPath("juhepay");
Autoloader::addAutoloadPath("pufapay");
Autoloader::addAutoloadPath("wftpay");
Autoloader::addAutoloadPath("paylinx");
Autoloader::addAutoloadPath("wepayez");
Autoloader::addAutoloadPath("weikebaba");
Autoloader::addAutoloadPath("fubei");
Autoloader::addAutoloadPath("xiaoma");
Autoloader::addAutoloadPath("nong");
Autoloader::addAutoloadPath("xkypay");
define('ENABLE_HTTP_PROXY', false);
define('HTTP_PROXY_IP', '127.0.0.1');



