<?php
/**
 * 街道社区管理员
 * Created by PhpStorm.
 * Author: win7
 * Date Time: 2020/4/23 17:33
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;


class HouseVillageDeviceCount extends Model{

    /**
     * 获取单个街道社区管理员数据信息
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }



    /**
     * 获取街道下社区数量
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }


    /**
     * 修改信息
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 修改信息
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     * @param array $data 添加内容
     * @return bool
     */
    public function addOne($data) {
        $area_id = $this->insertGetId($data);
        return $area_id;
    }

    /**
     * 获取街道列表
     * @author:zhubaodi
     * @date_time: 2021/12/20 13:56
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field=true,$page=1,$limit=20,$order='id DESC')
    {
        if($page) {
            $res = $this->where($where)->field($field)->order($order)->page($page, $limit)->select();
            return $res;
        }else{
            $res = $this->where($where)->field($field)->order($order)->select();

            return $res;
        }
    }


    /**
     * Notes: 删除
     * @param $where
     * @return bool
     * @throws \Exception
     * @author: weili
     * @datetime: 2020/9/15 10:09
     */
    public function del($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }
}