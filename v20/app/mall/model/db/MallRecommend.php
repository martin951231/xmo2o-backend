<?php
/**
 * MallRecommend.php
 * 首页推荐（猜你喜欢）模块model
 * Create on 2020/10/21 14:39
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use \think\Model;

class MallRecommend extends Model
{
    /**
     * 根据条件获取
     * @param $field
     * @param $where
     * @return array
     */
    public function getByCondition($field, $where)
    {
        $arr = $this->field($field)->where($where)->order(['sort' => 'DESC', 'id' => 'DESC'])->select();
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
        $res = $this->insert($param);
        return $res;
    }

    /**
     * 更新
     * @param $param
     * @param $where
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
    public function delRecAdver($where)
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
}