<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/4/27 13:20
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillagePileMonthCardUser extends Model
{

    /**
     * 获取月卡列表
     * @author:zhubaodi
     * @date_time: 2021/4/26 19:52
     */
    public function getList($where, $field = true, $page = 0, $limit = 20, $order = 'id DESC', $type = 0)
    {
        $list = $this->field($field)->where($where);
        if ($page)
            $list = $list->order($order)->page($page, $limit)->select();
        else
            $list = $list->select();
        return $list;
    }


    /**
     * 获取单个购买的月卡数据信息
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     * @author: zhubaodi
     * @date_time: 2021/4/26 19:52
     */
    public function getOne($where, $field = true)
    {
        $info = $this->field($field)->where($where)->find();
      //   print_r($this->getLastSql());exit;
        return $info;
    }

    /**
     * 获取单个购买的月卡数据信息
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     * @author: zhubaodi
     * @date_time: 2021/4/26 19:52
     */
    public function get_one($where, $field = true,$order='id DESC')
    {
        $info = $this->field($field)->order($order)->where($where)->find();
        //   print_r($this->getLastSql());exit;
        return $info;
    }

    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/4/26 19:52
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }


    /**
     * 添加添加购买的月卡信息
     * @author: zhubaodi
     * @date_time: 2021/4/26 19:52
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function insertCard($data) {
        $order_id = $this->insertGetId($data);
        return $order_id;
    }
}
