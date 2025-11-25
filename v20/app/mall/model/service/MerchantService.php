<?php
/**
 * MerchantService.php
 * 商家service
 * Create on 2020/9/14 9:26
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\mall\model\db\Merchant;

class MerchantService
{
    public function __construct()
    {
        $this->merchantModel = new Merchant();
    }

    public function getByMerId($mer_id,$field = 'name')
    {
        $where = [
            'mer_id' => $mer_id
        ];
        $arr = $this->merchantModel->getOne($where, $field);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }
    public function getMerList($where,$page,$pageSize)
    {
        $field = 'name,mer_id';
        $arr = $this->merchantModel->getMerList($where, $field,$page,$pageSize);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }
    public function getMerListCount($where)
    {
        $field = 'name,mer_id';
        $count = $this->merchantModel->getMerListCount($where, $field);
        $list['count'] = $count;
        if (!empty($count)) {
            return $count;
        } else {
            return [];
        }
    }
    public function getMerList1($where)
    {
        $field = 'name,mer_id';
        $arr = $this->merchantModel->getMerList1($where,$field);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

}