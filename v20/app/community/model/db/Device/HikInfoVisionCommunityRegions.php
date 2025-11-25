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

class HikInfoVisionCommunityRegions extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    
    public function deleteInfo($where) {
        return $this->where($where)->delete();
    }
    
    public function getListData($where, $field = true, $orderby = 'id DESC', $page = 0, $page_size = 10)
    {
        $data_sql = $this->field($field)->where($where)->order($orderby);
        if ($page > 0) {
            $data_sql->page($page, $page_size);
        }
        $data = $data_sql->select();
        return $data;
    }
}