<?php
/**
 * 实时电量
 * Created by PhpStorm.
 * User: wanziyang
 * Date Time: 2021/4/9 11:23
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseMeterElectricRealtime extends Model{

    /**
     * 获取单个数据信息
     * @author: zhubaodi
     * @date_time: 2021/4/9 11:23
     * @param int $village_id 社区id
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($village_id,$field =true){
        $info = $this->field($field)->where(array('village_id'=>$village_id))->find();
        return $info;
    }

    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/4/9 11:23
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取列表
     * @author: zhubaodi
     * @date_time: 2021/4/10 15:23
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array|null|Model
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
     * 添加数据
     * @author: zhubaodi
     * @date_time: 2021/4/9 11:23
     * @param array $data
     * @return integer
    **/
    public function insertOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }
    /**
     * 获取单个数据
     * @author: zhubaodi
     * @date_time: 2021/4/9 11:23
     * @param array $where
     * @param bool $field
     * @return array
    **/
    public function getInfo($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    /**
     * 获取实时电量
     * @author zhubaodi
     * @date_time 2021/4/10
     * @param $where
     * @param  $field
     * @param string $group
     * @return mixed
     */
    public function getLists($where=[] ,$field=true,$group='',$order='id DESC',$page=0,$limit=20)
    {
        $data = $this->where($where)
            ->field($field)
            ->group($group)
            ->order($order);
        if($page)
            $data = $data->page($page,$limit)->select();
        else
            $data = $data->select();
        return $data;
    }

    /**
     * 统计电表列表
     * @author: zhubaodi
     * @date_time: 2021/4/10
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return array|null|Model
     */
    public function getCount($where,$group) {
        $list = $this->group($group)
            ->where($where)->count();
        return $list;
    }

    /**
     * 获取用电量
     * @author lijie
     * date_time 2021/05/21
     * @param string $sum
     * @param string $group
     * @param string $order
     * @return string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSum($sum='r.end_num - r.begin_num',$group='',$order='')
    {
        if(!$group){
            $sum = $this->field("sum($sum) as sum")->select();
            return $sum;
        }
        if($group){
            $data = $this->alias('r')
                ->leftJoin('house_village v','v.village_id = r.village_id')
                ->field("v.village_name,sum($sum) as sum")
                ->group($group)
                ->select()->toArray();
            return $data;
        }
    }
}
