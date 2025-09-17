<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      西函门禁设备信息
 */

namespace app\community\model\db\Device;

use think\Model;

class HouseVillageUserBindIccard extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function deleteInfo($where) {
        return $this->where($where)->delete();
    }

    public function getColumn($where,$column, $key = '', $order = 'last_time DESC, add_time DESC', $limit = 0)
    {
        $sql = $this->where($where)->order($order);
        if ($limit > 0) {
            $data = $sql->limit($limit)->column($column,$key);
        } else {
            $data = $sql->column($column,$key);
        }
        return $data;
    }
    public function getOneData($where = [], $field = '*', $order = 'id desc') {
        $result = $this->field($field)->where($where)->order($order)->find();
        return $result;
    }

    public function saveOneData($where, $data=array())
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    public function updateOneData($where, $data=array())
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    public function addOneData($data=array())
    {
        $result = $this->insertGetId($data);
        return $result;
    }
}