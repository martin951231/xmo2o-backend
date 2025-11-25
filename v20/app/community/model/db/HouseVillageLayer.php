<?php
/**
 * 小区单元下层级信息
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/6/12 17:40
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class HouseVillageLayer extends Model{

    /**
     * 获取单个数据信息
     * @author: wanziyang
     * @date_time: 2020/6/12 17:40
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    /**
     * 层级相关信息列表
     * @author: wanziyang
     * @date_time: 2020/6/12 17:40
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param string $order 排序
     * @return array|null|Model
     */
    public function getList($where,$field = true,$order='id desc',$page=0,$limit=10) {
        if(!$page)
            $list = $this->field($field)->where($where)->order($order)->select();
        else
            $list = $this->field($field)->where($where)->page($page,$limit)->order($order)->select();
        return $list;
    }


    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }


    public function getRawOne($whereRaw,$field=true) {
        $info = $this->field($field)->whereRaw($whereRaw)->find();
        return $info;
    }

    public function addOne($data) {
        $set_info = $this->insertGetId($data);
        return $set_info;
    }

    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    public function addAll($data)
    {
        $res = $this->insertAll($data);
        return $res;
    }

    /**
     * 获取一列值
     * User: zhanghan
     * Date: 2022/2/21
     * Time: 16:24
     * @param $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getOneColumn($where,$field = 'id',$key=''){
        return $this->where($where)->column($field,$key);
    }


    /**
     * 查询某个字段最大值
     * @param array      $where 查询条件
     * @param string     $field 字段名
     * @param bool       $force 强制转为数字类型
     * @return mixed
     */
    public function getMax(array $where,string $field, bool $force = true)
    {
        $data = $this->where($where)->max($field, $force);
        return $data;
    }
    public function getValues($where,$field)
    {
        $data = $this->where($where)->value($field);
        return $data;
    }
}