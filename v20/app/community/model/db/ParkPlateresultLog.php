<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/12/7 15:22
 */
namespace app\community\model\db;

use think\Model;
class ParkPlateresultLog extends Model
{
    /**
     * 添加数据
     * @author:zhubaodi
     * @date_time: 2021/12/7 15:13
     */
    public function add($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }
    /**
     * 获取访客和主人信息
     * @author:zhubaodi
     * @date_time: 2021/12/7 15:13
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function get_one($where,$field=true,$order='id DESC')
    {
        $data = $this->field($field)->where($where)->order($order)->find();
        return $data;
    }
}