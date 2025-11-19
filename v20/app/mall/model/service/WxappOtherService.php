<?php
/**
 * WxappOtherService.php
 * 平台小程序支持打开别的小程序列表service
 * Create on 2020/10/21 9:06
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\mall\model\db\WxappOther;

class WxappOtherService
{
    public function __construct()
    {
        $this->wxappOtherModel = new WxappOther();
    }

    /**
     * 通过id获取
     * @param $field
     * @param $where
     * @return array
     */
    public function getById($field, $where)
    {
        $arr = $this->wxappOtherModel->getById($field, $where);
        return $arr;
    }

    /**
     * 获取所有小程序
     * @return array
     */
    public function getAll()
    {
        $arr = $this->wxappOtherModel->getAll();
        return $arr;
    }
}