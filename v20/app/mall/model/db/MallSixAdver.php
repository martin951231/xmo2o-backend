<?php
/**
 * MallSixAdver.php
 * 六宫格model
 * Create on 2020/10/21 11:31
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use think\Model;

class MallSixAdver extends Model
{
    /**
     * 根据条件获取
     * @param $field
     * @param $where
     * @return array
     */
    public function getByCondition($order, $field, $where, $limit)
    {
        if (intval($limit) != 0) {
            $arr = $this->field($field)->where($where)->order($order)->select();
        } else {
            $arr = $this->field($field)->where($where)->order($order)->limit($limit)->select();
        }
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 添加
     * @param $param
     * @return int|string
     */
    public function addOne($param)
    {
        $id = $this->insert($param);
        return $id;
    }

    /**
     * 更新
     * @param $param
     * @param $where
     * @return MallSixAdver
     */
    public function updateOne($param, $where)
    {
        $res = $this->where($where)->update($param);
        return $res;
    }

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 删除
     */
    public function delSixAdver($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * @param $where
     * @return array
     * 根据条件获取一个
     */
    public function getOne($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 点击次数累累计
     * @param $id  
     */
    public function clickNumberInc($id)
    {
        $banner = $this->find($id);
        if($banner){
            $banner->click_number ++;
            $banner->save();
            return $banner;
        }else{
            return [];
        }
    }
}