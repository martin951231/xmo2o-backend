<?php
/**
 * 省市区信息
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/8 14:02
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;
class AreaCode extends Model{

    /**
     * 获取单个数据信息
     * @author: zhubaodi
     * @date_time: 2021/11/24 16:03
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 获取信息
     * @author: zhubaodi
     * @date_time: 2021/11/24 16:03
     * @param array $where
     * @param bool|string $field
     * @param string $order
     * @return \think\Collection
     */
    public function getList($where,$field =true,$order='area_id ASC') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }

    /**
     * 获取信息
     * @author: zhubaodi
     * @date_time: 2021/11/24 16:03
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return mixed|null|Model
     */
    public function getLists($where,$field=true,$page=1,$limit=20,$order='area_sort DESC,area_id ASC') {
        $list = $this->field($field)->where($where);
        $list = $list->order($order)->page($page,$limit)->select();
        return $list;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: zhubaodi
     * @date_time: 2021/11/24 16:03
     */
    public function getCount($where=[])
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/11/24 16:03
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
     * @author: zhubaodi
     * @date_time: 2021/11/24 16:03
     * @param array $data 添加内容
     * @return bool
     */
    public function addOne($data) {
        $area_id = $this->insertGetId($data);
        return $area_id;
    }
}