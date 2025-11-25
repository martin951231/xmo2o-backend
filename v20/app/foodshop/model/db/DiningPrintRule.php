<?php
/**
 * 餐饮打印规则model
 * Author: hengtingmei
 * Date Time: 2020/09/21 14:47
 */

namespace app\foodshop\model\db;
use think\Model;
class DiningPrintRule extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据条件获取打印机以及打印规则的列表
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getRuleAndPrint($where) {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('r')
            ->field('r.id,r.number,r.print_type as rule_print_type,r.front_print,r.back_print,r.dangkou_select,p.*')
            ->where($where)
            ->leftJoin($prefix.'dining_print_rule_print b','r.id=b.rule_id')
            ->leftJoin($prefix.'orderprinter p','p.pigcms_id=b.print_id')
            ->group('b.print_id')
            ->select();

        return $result;
    }

    /**
     * 根据条件获取标签打印机以及打印规则的列表
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getRuleAndPrintLabel($where) {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('r')
            ->field('r.id,r.number,r.print_type as rule_print_type,r.front_print,r.back_print,r.dangkou_select,p.*')
            ->where($where)
            ->leftJoin($prefix.'dining_print_rule_print b','r.id=b.rule_id')
            ->leftJoin($prefix.'label_printer p','p.pigcms_id=b.print_id')
            ->group('b.print_id')
            ->select();
        return $result;
    }

}