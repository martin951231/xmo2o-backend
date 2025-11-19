<?php
/**
 * 运费模板
 * Author: hengtingmei
 * Date Time: 2021/5/21 11:40
 */

namespace app\merchant\model\db;
use think\Model;
class ExperssTemplate extends Model {

    use \app\common\model\db\db_trait\CommonFunc;    
    
    /**
    * 获取运费模板列表
    * @param array $where 
    * @param string $field 
    * @param array $order 
    * @param int $page 
    * @param int $limit 
    * @return object
    */
   public function getExpressList($where = [], $field = true,$order=true,$page=0,$limit=0){
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $sql = $this->field($field)
                    ->alias('e')
                    ->leftJoin($prefix.'express_template_area a','e.id = a.tid')
                    ->leftJoin($prefix.'express_template_value v','a.vid = v.id')
                    ->where($where)
                    ->order($order);
        if($limit)
        {
            $sql->page($page,$limit);
        }
        $result = $sql->select();
        return $result;
   }
}