<?php
/**
 * Created by PhpStorm.
 * Author: win7
 * Date Time: 2020/4/29 21:02
 */

namespace app\community\model\service;

use app\community\model\db\ConfigData;

class ConfigDataService
{
    public function get_one($whereArr) {
        // 初始化 物业管理员 数据层
        $db_config = new ConfigData();
        $info = $db_config->get_one($whereArr);
        if ($info && !$info->isEmpty()) {
            $info = $info->toArray();
        }else{
            $info = array();
        }
        return $info;
    }

    public function addConfig($addData=array()) {
        // 初始化 物业管理员 数据层
        $db_config = new ConfigData();
        $info = $db_config->addConfig($addData);
        return $info;
    }

    public function updateConfig($where=array(),$saveData=array()) {
        // 初始化 物业管理员 数据层
        $db_config = new ConfigData();
        $info = $db_config->updateConfig($where,$saveData);
        return $info;
    }
}