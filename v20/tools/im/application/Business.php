<?php

use Workerman\MySQL\Connection;

/**
 * 业务层面逻辑
 *
 * @date: 2024/07/18
 */
class Business
{
    private $db;

    public function __construct()
    {
        $db_config = require __DIR__ . '/../../../../conf/db.php';
        $this->db = new Connection($db_config['DB_HOST'], $db_config['DB_PORT'], $db_config['DB_USER'], $db_config['DB_PWD'], $db_config['DB_NAME']);
    }

    /**
     * 校验连接合法性
     *
     * @return void
     * @date: 2024/07/18
     */
    public function checkToken($params)
    {
        $appkey = $params['appkey'] ?? '';
        $timestamp = $params['timestamp'] ?? '';
        $random_str = $params['random_str'] ?? '';
        $token = $params['token'] ?? '';
        if (empty($appkey) || empty($timestamp) || empty($random_str) || empty($token)) {
            return false;
        }

        if (abs(time() - $timestamp / 1000) > 86400) {
            return false;
        }

        $res = $this->db->select('name,value')->from('pigcms_config')->where("name in ('im_key','im_secret','site_url')")->query();
        $res = array_column($res, 'value', 'name');
        if ($appkey != $res['im_key']) {
            return false;
        }

        $new_token = md5('appkey=' . ($res['im_key'] ?? '') . '&timestamp=' . $timestamp . '&random_str=' . $random_str . '&key=' . ($res['im_secret'] ?? ''));
        return $new_token == $token;
    }

    public function getConfig()
    {
        $res = $this->db->select('name,value')->from('pigcms_config')->where("name in ('im_key','im_secret','site_url')")->query();
        return array_column($res, 'value', 'name');
    }
}
