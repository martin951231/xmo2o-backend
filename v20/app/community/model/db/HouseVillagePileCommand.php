<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/5/26 13:20
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseVillagePileCommand extends Model
{

    /**
     * 获取指令列表
     * @author:zhubaodi
     * @date_time: 2021/5/26 17:23
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
     * 获取指令详情
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     * @author:zhubaodi
     * @date_time: 2021/5/26 17:23
     */
    public function getOne($where, $field = true)
    {
        $info = $this->field($field)->where($where)->find();
        return $info;
    }


    /**
     * 修改信息
     * @author:zhubaodi
     * @date_time: 2021/5/26 17:23
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 删除指令
     * @author lijie
     * @date_time 2021/07/23
     * @param array $where
     * @return bool
     * @throws \Exception
     */
    public function delOne($where=[])
    {
        $res = $this->where($where)->delete();
        return $res;
    }


    /**
     * 添加指令
     * @author:zhubaodi
     * @date_time: 2021/5/26 17:23
     * @param array $data 要进行添加的数据
     * @return mixed
     */
    public function insertCommand($data) {
        $order_id = $this->insertGetId($data);
        return $order_id;
    }
}
