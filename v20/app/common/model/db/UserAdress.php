<?php
/**
 * 用户收获地址
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/7/6 13:57
 */

namespace app\common\model\db;

use think\Model;

class UserAdress extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 获取用户地址
     * User: chenxiang
     * Date: 2020/7/6 14:17
     * @param bool $field
     * @param array $where
     * @return \think\Collection
     */
    public function getAdress($field = true, $where = [])
    {
        $result = $this->field($field)->where($where)->order('last_time desc')->select();
        if (!empty($result)) {
            return $result->toArray();
        } else {
            return [];
        }
    }

    /**
     * 添加或更新用户地址
     * User: 朱梦群
     * Date: 2020/8/28
     * @param array $data
     * @return int
     */
    public function add1($data){
        $result = $this->save($data);
        return $result;
    }
    /**
     * 编辑用户地址
     * User: 朱梦群
     * Date: 2020/8/28
     * @param array $where
     * @param array $data
     * @return UserAdress
     */
    public function edit($where,$data){
        $result = $this->where($where)->update($data);
        return $result;
    }
    /**
     * 删除用户地址
     * User: 朱梦群
     * Date: 2020/8/31
     * @param array $where
     * @return int
     */
    public function del($where){
        $result = $this->where($where)->delete();
        return $result;
    }

    /**
     * 取消其他用户的默认
     * User: 朱梦群
     * Date: 2020/8/31
     * @return
     */
    public function updateDefault($uid){
        $result = $this->where(['default'=>1,'uid'=>$uid])->update(['default'=>0]);
        return $result;
    }

    /**
     * 添加或更新用户地址
     * User: 朱梦群
     * Date: 2020/8/28
     * @param array $data
     * @return int
     */
    public function add($data){
        $result = $this->save($data);
        return $result;
    }
    /**
     * 编辑用户地址
     * User: 朱梦群
     * Date: 2020/8/28
     * @param array $where
     * @param array $data
     * @return UserAdress
     */
    public function edit1($where,$data){
        $result = $this->where($where)->update($data);
        return $result;
    }
    /**
     * 删除用户地址
     * User: 朱梦群
     * Date: 2020/8/31
     * @param array $where
     * @return int
     */
    public function del1($where){
        $result = $this->where($where)->delete();
        return $result;
    }
}