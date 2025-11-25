<?php
/**
 * 小区左侧目录
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/23 15:44
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseMenuNew extends Model{

    /**
     * Notes: 获取单个目录信息
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2020/4/23 15:44
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * Notes: 获取小区目录信息
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2020/4/23  15:46
     */
    public function getList($where,$field =true,$order='sort desc,fid ASC,id ASC') {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }
}