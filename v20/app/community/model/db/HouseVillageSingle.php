<?php
/**
 * 小区楼栋
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 15:49
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillageSingle extends Model{

    /**
     * 获取单个数据信息
     * @author: wanziyang
     * @date_time: 2020/4/26 15:49
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    public function getRawOne($whereRaw,$field=true) {
        $info = $this->field($field)->whereRaw($whereRaw)->find();
        return $info;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/4/26 15:38
     * @param  array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }


    public function addOne($data) {
        $set_info = $this->insertGetId($data);
        return $set_info;
    }

    /**
     * 楼栋相关信息列表
     * @author: wanziyang
     * @date_time: 2020/5/6 14:45
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param array $order 排序
     * @return array|null|Model
     */
    public function getList($where,$field = true,$order=['id'=>'desc']) {
        $list = $this->field($field)->where($where)->order($order)->select();
        return $list;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/10/13 18:41
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取楼栋和小区名称
     * @author lijie
     * @date_time 2020/01/07
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getSingleVillageName($where,$field=true)
    {
        $data = $this->alias('s')
            ->leftJoin('house_village v','s.village_id = v.village_id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }

    /**
     * 查询一列值
     * User: zhanghan
     * Date: 2022/2/21
     * Time: 16:19
     * @param $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getOneColumn($where,$field = 'id',$key=''){
        return $this->where($where)->column($field,$key);
    }

    public function getWhereRawColumn($where, $field = 'id', $key = '')
    {
        return $this->whereRaw($where)->column($field, $key);
    }

    public function addAll($data)
    {
        $res = $this->insertAll($data);
        return $res;
    }
}