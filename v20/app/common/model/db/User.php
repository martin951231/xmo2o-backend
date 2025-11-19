<?php
/**
 * 用户model
 * Author: chenxiang
 * Date Time: 2020/5/25 14:06
 */
namespace app\common\model\db;

use think\Model;

class User extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    protected $pk = 'uid';
    /**
     * 获取用户信息
     * User: chenxiang
     * Date: 2020/5/25 14:42
     * @param bool $field
     * @param array $where
     * @return array|Model|null
     */
    public function getUser($field = true, $where = []) {
        $result = $this->field($field)->where($where)->find();
        return $result;
    }

    public function getUserMsg($field = true, $where = []) {
        $result = $this->field($field)->where($where)->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * 更新用户数据
     * User: chenxiang
     * Date: 2020/5/25 15:33
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveUser($data = []) {
        $result = $this->save($data);
        if($result !== true) {
            return false;
        }
        return $result;
    }

    /**
     * update更新数据
     * User: chenxiang
     * Date: 2020/5/26 20:08
     * @param array $where
     * @param array $data
     * @return User
     */
    public function updateValue($where = [], $data = []) {
        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * 添加用户信息
     * User: chenxiang
     * Date: 2020/5/27 10:37
     * @param array $data
     * @return int|string
     */
    public function addUser($data = []) {
        $result = $this->insertGetId($data);
        return $result;
    }

    /**
     * 自增
     * User: chenxiang
     * Date: 2020/5/27 11:19
     * @param array $where
     * @param string $field
     * @param int $num
     * @return mixed
     */
    public function setInc($where = [], $field = '', $num = 1) {
        $result = $this->where($where)->inc($field, $num)->update();
        return $result;
    }

    /**
     * 自减
     * User: chenxiang
     * Date: 2020/5/28 21:00
     * @param array $where
     * @param string $field
     * @param int $num
     * @return mixed
     */
    public function setDec($where = [], $field = '', $num = 1) {
        $result = $this->where($where)->dec($field, $num)->update();
        return $result;
    }


    /**
     * 获取一个字段信息
     * User: chenxiang
     * Date: 2020/5/28 21:03
     * @param array $where
     * @param string $value
     * @return mixed
     */
    public function getField($where= [], $value = '') {
        $result = $this->where($where)->value($value);
        return $result;
    }
}
