<?php
$publicConfig = include(PLUGIN_PATH . '../plugin_public/plugin_config.php');

$imConfig = array(
    'APP_GROUP_LIST' => 'System,Index',
    'DEFAULT_GROUP' => 'Index'
);

return array_merge($publicConfig, $imConfig);