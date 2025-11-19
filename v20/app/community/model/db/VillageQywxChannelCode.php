<?php
/**
 * 渠道活码信息
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/15 13:11
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class  VillageQywxChannelCode extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * Notes: 查询渠道活码数据列表
     * @param array $where
     * @param int $page
     * @param string|bool $field
     * @param string $order
     * @param int $page_size
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/3/15 13:27
     */
    public function getList($where,$whereRaw='',$page=0,$field ='a.*,c.name as group_name',$order='a.code_id ASC',$page_size=10) {
        $db_list = $this->alias('a')
            ->leftJoin('village_qywx_code_bind_work b', 'a.code_id=b.code_id')
            ->leftJoin('village_qywx_channel_group c', 'c.id=a.code_group_id')
            ->group('a.code_id')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        if ($whereRaw) {
            $list = $db_list->whereRaw($whereRaw)->select();
        } else {
            $list = $db_list->where($where)->select();
        }
        return $list;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/3/15 13:50
     */
    public function getContentCount($where,$whereRaw='') {
        $db_list = $this->alias('a')
            ->leftJoin('village_qywx_code_bind_work b', 'a.code_id=b.code_id');
        if ($whereRaw) {
            $db_list->whereRaw($whereRaw);
        } else {
            $db_list->where($where);
        }
        $count = $db_list->group('a.code_id')->count();
        return $count;
    }

    /**
     * Notes:更新渠道活码分组下数量
     * @param $code_group_id
     * @return VillageQywxChannelGroup
     * @author: wanzy
     * @date_time: 2021/3/30 15:35
     */
    public function updateNum($code_group_id) {
        $where = [];
        $where[] = ['code_group_id', '=', $code_group_id];
        $where[] = ['status', '=', 1];
        $count_num = $this->where($where)->count();
        if (!$count_num) $count_num = 0;
        $dbVillageQywxChannelGroup = new VillageQywxChannelGroup();
        $where_group = [];
        $where_group[] = ['id', '=', $code_group_id];
        $data  = [
            'number' => $count_num,
        ];
        $set = $dbVillageQywxChannelGroup->editFind($where_group, $data);
        return $set;
    }
}