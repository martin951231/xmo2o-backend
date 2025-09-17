<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      图片处理


 */

namespace app\community\model\db\Car;

use think\Model;
use think\facade\Db;

class HandleImg extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function deleteInfo($where)
    {
        return $this->where($where)->delete();
    }

    public function getList($where, $field = true, $order = '', $page = 0, $limit = 20)
    {
        $sql = $this->where($where)->field($field);
        if ($order) {
            $sql->order($order);
        }
        if ($page) {
            $list = $sql->page($page, $limit)->select();
        } else {
            $list = $sql->select();
        }
        return $list;
    }
}