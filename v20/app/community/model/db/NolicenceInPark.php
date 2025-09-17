<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/2/15 14:58
 */

namespace app\community\model\db;

use think\Model;
class NolicenceInPark extends Model
{

    /**
     * Notes: 获取数量
     * @param $where
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/3 17:44
     */
    public function getOne($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 添加数据
     * @author weili
     * @datetime 2020/7/7 10:43
     * @param array $data
     * @return integer
     **/
    public function insertOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }


    /**
     *更新数据
     * @author: zhubaodi
     * @date : 2022/8/17
     * @param $where
     * @param $data
     * @return mixed
     */
    public function saveOne($where,$data)
    {
        $data = $this->where($where)->save($data);
        return $data;
    }
}