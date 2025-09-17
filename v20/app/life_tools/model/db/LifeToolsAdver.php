<?php
/**
 * 景区首页装修model
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsAdver extends Model
{
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
     * @return MallAdver
     */
    public function editOne($where, $param)
    {
        $res = $this->where($where)->update($param);
        return $res;
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
     * 根据条件获取广告列表
     * @param $where
     * @param $order 排序
     * @param $limit 查询记录条数限制
     * @return array|bool|Model|null
     */
    public function getAdverListByCondition($where, $order = '', $limit = 0)
    {
        $arr = $this->where($where)->order($order)->limit($limit)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 增加曝光次数
     * @param int $id 广告id
     */
    public function exposureNumberInc($id)
    {
        $adver = $this->find($id);
        if($adver){
            $adver->exposure_number ++;
            $adver->save();
        }
    }

    /**
     * 增加点击次数
     * @param int $id 广告id
     */
    public function clickNumberInc($id)
    {
        $adver = $this->find($id);
        if($adver){
            $adver->click_number ++;
            $adver->save();
            return $adver;
        }else{
            return [];
        }
    }
}