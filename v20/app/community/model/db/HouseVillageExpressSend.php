<?php
/**
 * @author : liukezhu
 * @date : 2021/11/11
 */
namespace app\community\model\db;

use think\Model;

class HouseVillageExpressSend extends Model{


    /**
     * 统计数量
     * @author: liukezhu
     * @date : 2021/11/11
     * @param $where
     * @return mixed
     */
    public function getCount($where) {
        $count =  $this->alias('s')
            ->leftJoin('house_village v','v.village_id = s.village_id')
            ->leftJoin('express e','e.code = s.express and e.status=1')
            ->where($where)->count();
        return $count;
    }

    /**
     * 查询列表数据
     * @author: liukezhu
     * @date : 2021/11/11
     * @param $where
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getList($where,$field='s.*',$order='s.send_id desc',$page=0,$limit=10)
    {
        $sql = $this->alias('s')
            ->leftJoin('house_village v','v.village_id = s.village_id')
            ->leftJoin('express e','e.code = s.express and e.status=1')
            ->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }
}