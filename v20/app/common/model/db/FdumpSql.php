<?php
/**
 * 缓存
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/09/26 17:52
 */

namespace app\common\model\db;
use think\Model;
// use think\facade\Db;
class FdumpSql extends Model {


    use \app\common\model\db\db_trait\CommonFunc;
    public function getListData($where, $field = '*', $orderby = 'id DESC', $page = 1, $page_size = 10)
    {
        $data_sql = $this->field($field)->where($where)->order($orderby);
        if ($page > 0) {
            $data_sql->page($page, $page_size);
        }
        $data = $data_sql->select();
        return $data;
    }
    public function del_data($where=array())
    {
        $res=$this->where($where)->delete();
        return $res;
    }
}