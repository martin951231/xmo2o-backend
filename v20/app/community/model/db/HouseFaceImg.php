<?php
/**
 * 小区管理员
 * Created by PhpStorm.
 * Author: win7
 * Date Time: 2020/4/23 17:33
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseFaceImg extends Model{

    /**
     * 获取单个小区管理员数据信息
     * @author: wanziyang
     * @date_time: 2020/4/23 15:44
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true,$order='id DESC'){
        $info = $this->field($field)->where($where)->order($order)->find();
        return $info;
    }


    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/4/24 17:56
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }


    /**
     * 获取相关数量
     * @author: zhubaodi
     * @date_time: 2022/5/30 10:28
     * @param array $where 查询条件
     * @return mixed|array|null|Model
     */
    public function getCount($where){
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 查询图片列表 支持分页
     * @param array $where
     * @param bool|string $field
     * @param bool|array $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where = [], $field = true, $order = true, $page = 0, $limit = 0)
    {
        $sql = $this->field($field)->where($where)->order($order);
        if ($limit) {
            $sql->page($page, $limit);
        }
        return $sql->select();
    }
    
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }
}