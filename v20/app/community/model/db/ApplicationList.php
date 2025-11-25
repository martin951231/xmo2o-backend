<?php
/**
 * 功能应用表格
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/9 15:22
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class ApplicationList extends Model{

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * Notes: 查询字段
     * @param $where
     * @param bool $field
     * @param string $order
     * @author: wanzy
     * @date_time: 2021/3/9 15:35
     */
    public function getColumn($where,$field=true) {
        $list = $this->alias('a')
            ->leftjoin('application_bind b','a.application_id=b.application_id')
            ->where($where)
            ->group('a.application_id')
            ->column($field);
        return $list;
    }
}