<?php
/**
 * 快递列表
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 16:48
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillageExpress extends Model{

    /**
     * 查询对应条件下快递数量
     * @author: wanziyang
     * @date_time: 2020/4/25 16:49
     * @param array $where
     * @return int
     */
    public function get_village_express_num($where) {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 根据条件获取快递列表
     * @author lijie
     * @date_time 2020/08/17 16:40
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param $order
     * @return mixed
     */
    public function getLists($where,$field=true,$page=1,$limit=10,$order='hve.id DESC')
    {
        $data = $this->alias('hve')
            ->leftJoin('express e','e.id = hve.express_type')
            ->field($field)
            ->where($where)
            ->page($page,$limit)
            ->order($order)
            ->select();
        return $data;
    }

    /**
     * 添加快递
     * @author lijie
     * @date_time 2020/08/17 16:42
     * @param $data
     * @return mixed
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 获取快递详情
     * @author lijie
     * @date_time 2020/08/17 17:01
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getOne($where,$field)
    {
        $data = $this->alias('hve')
            ->leftJoin('express e','e.id = hve.express_type')
            ->field($field)
            ->where($where)
            ->find();
        return $data;
    }


    public function getList($where,$field='*',$order='p.id desc',$page=0,$limit=10)
    {
        $sql = $this->alias('p')
            ->leftJoin('house_village v','v.village_id = p.village_id')
            ->leftJoin('express e','e.id = p.express_type')
            ->leftJoin('house_village_express_order o','o.express_id = p.id')
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

    public function getCount($where) {
        $count =$this->alias('p')
            ->leftJoin('house_village v','v.village_id = p.village_id')
            ->leftJoin('express e','e.id = p.express_type')
            ->leftJoin('house_village_express_order o','o.express_id = p.id')
            ->where($where)->count();
        return $count;
    }

}