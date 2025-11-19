<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      社区消息推送类型
 */
namespace app\community\model\db\pushMessage;

use think\Model;
use think\facade\Db;

class CommunityPushMessageClassify extends Model
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

    public function getColumn($where,$field, $key = '')
    {
        $info = $this->where($where)->column($field, $key);
        return $info;
    }
}