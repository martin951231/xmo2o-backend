<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      物料类明细别表
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class MaterielCheckoutList extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function deleteInfo($where) {
        return $this->where($where)->delete();
    }

    public function getList($where = [], $field = true, $order = true, $page = 0, $listRows = 0)
    {
        $sql = $this->field($field)->where($where)->order($order);
        if ($listRows) {
            $sql->page($page, $listRows);
        }
        $result = $sql->select();
        return $result;
    }

    public function getColumn($where, $column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }
}