<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/11/12 19:24
 */
namespace app\community\model\db;

use think\Model;
class HouseVillageCheckauthDetail extends Model
{
    public function getOne($where,$field ='*'){
        $info = $this->field($field)->where($where)->find();
        if (!$info || $info->isEmpty()) {
            $info = [];
        }else{
            $info=$info->toArray();
        }
        return $info;
    }

    public function addDetail($addData=array()){
        if(empty($addData)){
            return false;
        }
        $idd=$this->insertGetId($addData);
        return $idd;
    }

    //更新数据
    public function updateDetail($where=array(),$updateData=array()){
        if(empty($where) || empty($updateData)){
            return false;
        }
        $ret=$this->where($where)->update($updateData);
        //echo $this->getLastSql();
        return $ret;
    }

    /**
     * 统计数量
     * User: zhanghan
     * Date: 2022/2/9
     * Time: 19:42
     * @param $where
     * @return false|int
     */
    public function statisticsCheck($where){
        if(empty($where)){
            return false;
        }
        $num = $this->where($where)->count('apply_id');
        if(!$num){
            $num = 0;
        }
        return $num;
    }

    /**
     * 获取我的审批列表
     * User: zhanghan
     * Date: 2022/2/9
     * Time: 20:53
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function myCheckList($where,$field = true,$order = 'a.id DESC',$page = 0,$limit = 10){
        if(empty($where)){
            return [];
        }
        $sql = $this->alias('d')
            ->leftjoin('house_village_checkauth_apply a','d.apply_id = a.id')
            ->leftjoin('house_new_pay_order o','d.order_id = o.order_id')
            ->leftjoin('house_worker w','d.wid = w.wid')
            ->field($field)
            ->where($where)
            ->order($order);
        $count = $sql->count();
        if(empty($page)){
            $data = $sql->select();
        }else{
            $data = $sql->page($page,$limit)->select();
        }
        if($data && !$data->isEmpty()){
            $list = $data->toArray();
            return ['list' => $list,'count' => $count];
        }else{
            return ['list' => [],'count' => 0];
        }
    }

    /**
     * 获取审核明细列表
     * User: zhanghan
     * Date: 2022/2/9
     * Time: 21:28
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array
     */
    public function getApplyList($where,$field = true,$order = 'id asc'){
        if(empty($where)){
            return [];
        }
        $data = $this->alias('d')
            ->leftjoin('house_worker w','d.wid = w.wid')
            ->field($field)
            ->order($order)
            ->where($where)
            ->select();
        if($data && !$data->isEmpty()){
            $list = $data->toArray();
            return $list;
        }else{
            return [];
        }
    }

    /**
     * 获取审核明细列表
     * User: zhanghan
     * Date: 2022/2/9
     * Time: 21:28
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array
     */
    public function getDetailLists($where,$field = true,$order = 'id asc'){
        if(empty($where)){
            return [];
        }
        $data = $this->field($field)->where($where)
            ->order($order)
            ->where($where)
            ->select();
        if($data && !$data->isEmpty()){
            $list = $data->toArray();
            return $list;
        }else{
            return [];
        }
    }
}