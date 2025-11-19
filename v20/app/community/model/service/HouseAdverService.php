<?php


namespace app\community\model\service;

use app\community\model\db\HouseAdver;

class HouseAdverService
{
    /**
     * 获取接到首页轮播
     * @author lijie
     * @date_time 2020/09/10
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return \think\Collection
     */
    public function getAdverLists($where=[],$field=true,$order='id desc')
    {
        $db_house_adver = new HouseAdver();
        $data = $db_house_adver->getList($where,$field,$order);
        return $data;
    }
}