<?php
/**
 * 应用中心
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/12/18
 */

namespace app\common\model\db;
use think\Model;
// use think\facade\Db;
class Plugin extends Model {
	use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     */
    public function getPluginMenuByJoin($table, $where, $field, $order) {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('p')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix.$table.' m','p.system_menu_id=m.id')
            ->order($order)
            ->select();
        return $result;
    }
}