<?php
/**
 * 团购与店铺
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/6/10 19:14
 */

namespace app\group\model\db;

use think\Model;
use think\facade\Db;
class GroupStore extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 获取相关店铺下团购
     * @author: wanziyang
     * @date_time: 2020/6/10 19:19
     * @param array $where
     * @param bool|string $field
     * @param string $order
     * @param int $page
     * @param int $page_size
     * @return mixed
     */
    public function getStoreGroupList($where, $field=true, $order='sort DESC', $page=0, $page_size=20) {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $db_list = $this
            ->table($prefix._view('group').' g,'.$prefix.'group_store a')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->where([['g.group_id', 'exp', Db::raw("=a.group_id")]])->select();

        return $list;
    }


    /**
     * 获取相关店铺下团购
     * @author: wanziyang
     * @param array $where
     * @param bool|string $field
     * @param array $order
     * @param int $page
     * @param int $page_size
     * @return mixed
     */
    public function getStoreByGroup($where, $field=true, $order = [], $page=0, $page_size=20) {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $db_list = $this->alias('g')
            ->leftJoin($prefix.'merchant_store s', 'g.store_id=s.store_id')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    /**
     * @param $where
     * @param string $field
     * @param string $order
     * @param string $group
     * @return mixed
     * 取相应分组的团购商品
     */
    public function getGroupTypeMsg($where, $field="g.*", $order='g.sale_count DESC',$group="g.group_cate"){
        $db_list = $this->alias('a')
            ->leftJoin('group g', 'g.group_id=a.group_id')
            ->field($field)
            ->where($where);
            $assign['count']=$db_list->count();

            $assign['list']=$db_list->group($group)
            ->order($order)
            ->select()
            ->toArray();
        return $assign;
    }

    /**
     * 获取分销团购列表
     * @param $store_id 店铺id
     * @param $pageSize 每页显示的数量
     * @return Object
     */
    public function getDistributeGroupList($store_id, $pageSize)
    {
        $field = 'gs.store_id,g.group_id,g.name,g.s_name,g.intro,g.old_price,g.price,g.pic'; 
        $where = array(
            ['gs.store_id', '=', $store_id],
            ['g.status', '=', 1]
        );
 
        $list = $this->alias('gs')
        ->leftJoin('group g', 'g.group_id=gs.group_id')
        ->field($field)
        ->where($where)
        ->paginate($pageSize);
        return $list;
    }
}