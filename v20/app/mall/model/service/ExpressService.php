<?php
/**
 * ExpressService.php
 * 快递service
 * Create on 2020/9/19 16:28
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\mall\model\db\Express;

class ExpressService
{
    public function __construct()
    {
        $this->expressModel = new Express();
    }

    /**
     * 获取可用的快递列表
     * @return array
     */
    public function getExpress()
    {
        $field = 'id,name';
        $arr = $this->expressModel->getExpress($field, ['status' => 1]);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }


    /**
     * 获取已配置物流面单模板id的快递列表
     * @return array
     */
    public function getExpressList()
    {
        $field = 'id,code,name,tempid,pic';
        $where = [
            ['status', '=', 1],
            ['tempid', '<>', '']
        ];
        $arr = $this->expressModel->getExpress($field, $where);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }
}