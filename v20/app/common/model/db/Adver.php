<?php
/**
 * 系统后台广告表
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/21 09:20
 */

namespace app\common\model\db;
use think\Model;
class Adver extends Model {
    /**
     * 根据条件获取广告列表
     * @param $where
     * @param $order 排序
     * @param $limit 查询记录条数限制
     * @param $whereRaw
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAdverListByCondition($where, $order='', $limit = 0,$whereRaw='') {
       if(empty($where)) {
            return false;
        }

        $result = $this->where($where)
                        ->order($order);
                        
        if($whereRaw){
            $result = $result->whereRaw($whereRaw);
        }
        $result = $result->limit($limit)->select();
        return $result;
    }

    /**
     * 通过条件获取列表
     * @param $field
     * @param $where
     * @param $order
     * @return array
     */
    public function getByCondition($field, $where, $order)
    {
        $arr = $this->field($field)->where($where)->order($order)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 删除
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function getDel($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * @param $field
     * @param $where
     * @return array
     * 通过id获取一条记录
     */
    public function getById($field, $where)
    {
        $arr = $this->field($field)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 添加一条
     * @param $param
     * @return int|string
     */
    public function addOne($param)
    {
        $res = $this->insert($param);
        return $res;
    }

    /**
     * 更新一条
     * @param $where
     * @param $param
     */
    public function editOne($where, $param)
    {
        $res = $this->where($where)->update($param);
        return $res;
    }

}