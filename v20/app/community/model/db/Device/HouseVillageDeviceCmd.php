<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      门禁权限下发状态订阅消息(目前仅用于海康云眸内部)
 */


namespace app\community\model\db\Device;

use think\Model;
use think\facade\Db;

class HouseVillageDeviceCmd extends Model
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

    public function getGroupList($field = '', $where = [], $group = 'unique_cmd') {
        $result = $this->field($field)->where($where)->group($group)->order(null)->select();
        return $result;
    }
}