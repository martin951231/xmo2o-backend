<?php
/**
 * App配置服务类.
 * @author: 张涛
 * @date: 2020/9/7
 */

namespace app\common\model\service;

use app\common\model\db\AppapiAppConfig;

class AppapiAppConfigService
{
    /**
     * 获取配置项
     * @author: 张涛
     * @date: 2020/9/7
     */
    public function getOne($var)
    {
        $item = (new AppapiAppConfig())->where(['var' => $var])->find();
        return $item ? $item->toArray() : [];
    }

    /**
     * 获取所有app配置项
     * @author: 张涛
     * @date: 2020/9/18
     */
    public function getAll()
    {
        $item = (new AppapiAppConfig())->select()->toArray();
        return $item;
    }

    /**
     * 获取所有app配置项,格式 key=>value
     * @author: 张涛
     * @date: 2020/9/18
     */
    public function getAllWithIndex()
    {
        $appConfig = $this->getAll();
        $appConfig = array_combine(array_column($appConfig, 'var'), array_column($appConfig, 'value'));
        return $appConfig;
    }
}