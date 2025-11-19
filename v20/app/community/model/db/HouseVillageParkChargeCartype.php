<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/11/3 18:02
 */
namespace app\community\model\db;

use think\Model;
class HouseVillageParkChargeCartype extends Model
{
    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author zhubaodi
     * @date_time 2022/11/21
     */
    public function getFind($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     */
    public function addFind($data)
    {
        $id =$this->insertGetId($data);
        return $id;
    }

    /**
     *编辑数据
     * @author zhubaodi
     * @date_time 2022/11/21
     * @param $where
     * @param $save
     * @return mixed
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取数量
     * @author zhubaodi
     * @date_time 2022/11/21
     * @param $where
     * @return int
     */
    public function getCounts($where)
    {
        $num = $this->alias('a')->leftJoin('house_new_charge_rule r', 'r.id=a.rule_id')
            ->where($where)->count();
        return $num;
    }

    /**
     * 根据条件获取列表
     * @author zhubaodi
     * @date_time 2022/11/21
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field,$page=0,$limit=15,$order='a.id DESC')
    {
        if ($page){
            $data = $this->alias('a')
                ->leftJoin('house_new_charge_rule r', 'r.id=a.rule_id')
                ->where($where)->field($field)->page($page,$limit)->order($order)->select();
        }else{
            $data = $this->alias('a')
                ->leftJoin('house_new_charge_rule r', 'r.id=a.rule_id')
                ->where($where)->field($field)->order($order)->select();
        }
        //  print_r($data);exit;
        return $data;
    }



    /**
     * 获取数量
     * @author zhubaodi
     * @date_time 2022/11/21 
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $num = $this->where($where)->count();
        return $num;
    }

    /**
     * 根据条件获取列表
     * @author zhubaodi
     * @date_time 2022/11/21
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$field,$page=0,$limit=15,$order='id DESC')
    {
        if ($page){
            $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        }else{
            $data = $this->where($where)->field($field)->order($order)->select();
        }
        return $data;
    }
    
    
    public function getColumn($where,$field){
        $count = $this->where($where)->column($field);
        return $count;
    }
}