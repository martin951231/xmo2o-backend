<?php
/**
 * 用户积分记录 model
 * Created by.PhpStorm
 * User: chenxiang
 * Date: 2020/5/26 17:14
 */

namespace app\common\model\db;

use think\Model;

class UserScoreList extends Model
{
    /**
     * 获取用户积分列表
     * User: chenxiang
     * Date: 2020/5/26 17:17
     * @param array $where
     * @return \think\Collection
     */
    public function getUserScoreList($where = []) {
        $result = $this->where($where)->select();
        return $result;
    }

    /**
     * 更新用户积分记录
     * User: chenxiang
     * Date: 2020/5/26 17:28
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveUserScoreList($data = []) {
        $where = [
            'id' => $data['id']
        ];
        unset($data['id']);
        $result = $this->where($where)->update($data);
        if($result !== true) {
            return false;
        }
        return $result;
    }

    /**
     * 添加用户积分
     * User: chenxiang
     * Date: 2020/5/26 17:41
     * @param array $data
     * @return int|string
     */
    public function addUserScoreList($data = []) {
        $result = $this->insert($data);
        return $result;
    }

    /**
     * 获取用户积分信息
     * User: chenxiang
     * Date: 2020/6/1 18:51
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     */
    public function getOne($where = [], $field = true) {
        $result = $this->field($field)->where($where)->find();
        return $result;
    }
}
