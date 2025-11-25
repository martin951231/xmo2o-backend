<?php
/**
 * 首页常用应用表
 * Created by subline.
 * Author: hengtingmei
 */

namespace app\common\model\db;
use think\Model;
// use think\facade\Db;
class HotMenu extends Model {


    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 查询已选择的列表数据
     * @param $data array 数据
     * @return array
     */
    public function getList($where, $field, $order) {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('h')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix.'plugin p','p.plugin_id=h.plugin_id')
            ->leftJoin($prefix.'system_menu m','m.id=p.system_menu_id')
            ->order($order)
            ->select();

        return $result;
    }
}