<?php
/**
 * 餐饮打印规则绑定的打印机model
 * Author: hengtingmei
 * Date Time: 2020/09/21 14:47
 */

namespace app\foodshop\model\db;
use think\Model;
class DiningPrintRulePrint extends Model {

    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根据条件获取列表
     * @param $where
     * @author 衡婷妹
     * @date 2020/09/29
     */
    public function getPrintList($where, $field = true, $order = []){
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('b')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix.'orderprinter p','p.pigcms_id=b.print_id')
            ->order($order)
            ->select();
        return $result;
    }

    /**根据条件获取列表
     * @param $where
     * @param bool $field
     * @param array $order
     * @return mixed
     */
    public function getBindPrintList($where, $field = true, $order = []){
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('b')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix.'dining_print_rule r','b.rule_id=r.id')
            ->order($order)
            ->select();
        return $result;
    }
}