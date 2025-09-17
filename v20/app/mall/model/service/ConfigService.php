<?php
/**
 * Created by PhpStorm.
 * Author: win7
 * Date Time: 2020/4/29 21:02
 */

namespace app\mall\model\service;

use app\mall\model\db\Config;

class ConfigService
{
    /**
     * 获取数据库配置
     * @author: wanziyang
     * @date_time: 2020/4/30 9:11
     * @param string $name 配置项名称
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function get_config($name,$field = true) {
        // 初始化 物业管理员 数据层
        $db_config = new Config();
        $info = $db_config->get_one($name,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }

    /**
     * 条件获取数据库配置
     * @author: wanziyang
     * @date_time: 2020/4/30 9:13
     * @param array $where 查询条件
     * @param string|bool $field 需要获取的对应字段
     * @return array|null|\think\Model
     */
    public function get_config_list($where,$field = 'name,value') {
        // 初始化 物业管理员 数据层
        $db_config = new Config();
        $configs = $db_config->get_list($where,$field);
        $config = [];
        if (!($configs->isEmpty())) {
            foreach($configs as $key=>$value){
                $config[$value['name']] = $value['value'];
            }
            if (isset($config['Currency_txt'])) {
                $config['Currency_txt'] = $config['Currency_txt'] ? $config['Currency_txt'] : '元';
            }
            if (isset($config['Currency_symbol'])) {
                $config['Currency_symbol'] = $config['Currency_symbol'] ? $config['Currency_symbol'] : '￥';
            }
        }
        return $config;
    }
}