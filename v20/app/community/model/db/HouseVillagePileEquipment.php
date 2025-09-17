<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2020/4/26 09:44
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillagePileEquipment extends Model{

    /**
     * 获取指定设备信息
     * @author:zhubaodi
     * @date_time: 2021/4/26 18:46
     * @param array $where
     * @param bool $field
     * @return array
     */
    public function getInfo($where,$field=true){
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    public function saveDataInfo($where, $data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
    
    public function getCount($where)
    {
        $w='';
        if(isset($where['_string'])){
            $w=$where['_string'];
            unset($where['_string']);
        }
        $count = $this->where($where)->where($w)->count();
        return $count;
    }

    /**
     * 获取订单列表
     * @author:zhubaodi
     * @date_time: 2021/8/26 13:52
     */
    public function getList($where,$field=true,$page=0,$limit=20,$order='id DESC') {
        $list = $this->field($field)->where($where);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }
    /**
     * 充电桩数量
     * @author lijie
     * @date_time 2021/11/22
     * @param array $where
     * @return int
     * 查询数据
     * @author: liukezhu
     * @date : 2021/11/26
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getLists($where,$field=true,$order='e.id desc',$page=0,$limit=10)
    {
        $w='';
        if(isset($where['_string'])){
            $w=$where['_string'];
            unset($where['_string']);
        }
        $sql = $this->alias('e')
            ->leftJoin('house_village v','v.village_id = e.village_id')
            ->where($w)->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    /**
     * 查询总数
     * @author: liukezhu
     * @date : 2021/11/26
     * @param $where
     * @return mixed
     *
     */
    public function getCounts($where)
    {
        $w='';
        if(isset($where['_string'])){
            $w=$where['_string'];
            unset($where['_string']);
        }
        $count = $this->alias('e')
            ->leftJoin('house_village v','v.village_id = e.village_id')
            ->where($w)->where($where)->count();
        return $count;
    }

}
