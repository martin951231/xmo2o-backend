<?php
/**
 * 渠道活码绑定标签
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/22 20:34
 */

namespace app\community\model\db;

use think\Model;
class VillageQywxCodeBindLabel  extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * Notes: 按条件删除
     * @param $where
     * @return bool
     * @throws \Exception
     * @author: wanzy
     * @date_time: 2021/3/22 19:45
     */
    public function delWhere($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * Notes: 获取对应渠道活码标签数据
     * @param $where
     * @param int $page
     * @param string $field
     * @param string $order
     * @param int $page_size
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/3/23 14:42
     */
    public function getCodeLabel($where,$page=0,$field ='a.*,b.label_name',$order='a.label_id ASC',$page_size=10) {
        $db_list = $this->alias('a')
            ->leftJoin('village_qywx_code_label b', 'b.label_id=a.label_id')
            ->leftJoin('village_qywx_channel_code c', 'a.code_id=c.code_id')
            ->group('a.code_id,a.label_id')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }
}