<?php
/**
 * Created by PhpStorm.
 * User: zhubaodi
 * Date Time: 2021/4/9
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseMeterUserPayorder extends Model{

    /**
     * 获取单个订单信息
     * @author: zhubaodi
     * @date_time: 2021/4/12
     * @param int $order_id 订单id
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($order_id,$field =true){
        $info = $this->field($field)->where(array('id'=>$order_id))->find();
        return $info;
    }

    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2020/4/24
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取账单列表
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
    public function getList($where,$field=true,$page=0,$limit=20,$order='id DESC',$type=0) {
        $list = $this->alias('a')
            ->leftJoin('house_meter_admin_village b','a.village_id = b.village_id')
            ->leftJoin('user c','a.uid = c.uid')
            ->where($where)
            ->field($field);

        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
    //  print_r($this->getLastSql());exit;
        return $list;
    }




    /**
     * 获取用户缴费列表
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
    public function getLists($where,$field=true,$page,$limit=20,$order='id DESC',$type=0) {
        $list = $this->where($where)->field($field);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }

    /**
     * 添加数据
     * @author zhubaodi
     * @datetime 2021/4/10
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
     * @author zhubaodi
     * @datetime 2021/4/10
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
     * 删除订单
     * @author zhubaodi
     * @date_time 2021/4/10
     * @param $where
     * @param bool $field
     * @param string $group
     * @return mixed
     */
    public function deleteInfo($where)
    {
        $data = $this->where($where)->delete();
        return $data;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: zhubaodi
     * @datetime: 2021/4/12 17:20
     */
    public function getCount($where=[])
    {
        $list = $this->alias('a')
            ->leftJoin('house_meter_admin_village b','a.village_id = b.village_id')
            ->leftJoin('user c','a.uid = c.uid')
            ->group('a.id');
        $count = $list->where($where)->count();
        return $count;
    }
}
