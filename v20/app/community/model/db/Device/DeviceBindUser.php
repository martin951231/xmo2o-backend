<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      设备和人员绑定相关记录（所有相关下发记录）
 */


namespace app\community\model\db\Device;

use think\Model;
use think\facade\Db;

class DeviceBindUser extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;


    public function deleteInfo($where) {
        return $this->where($where)->delete();
    }


    public function getColumn($where, $column, $key = '', $limit = 0, $order = '')
    {
        $sql = $this->where($where);
        if ($limit && $order) {
            $sql = $sql->limit($limit)->order($order);
        }
        $data = $sql->column($column,$key);
        return $data;
    }
}