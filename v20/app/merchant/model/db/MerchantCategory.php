<?php


namespace app\merchant\model\db;

use think\Model;
class MerchantCategory extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获得一级分类列表
     * @param $where array 条件
     * @return array
     */
    public function categoryOneList($where = [], $order = '', $field = '*'){
        $where = ['cat_fid'=>0];
        $result = $this->where($where)->field($field)->order($order)->select()->toArray();
        return $result;
    }

    /**
     * 获得绑定了话题的分类列表
     * @param $where array 条件
     * @return array
     */
    public function getListByTopic($where = []){
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $res = $this ->alias('c')
                        ->join($prefix.'grow_grass_category g','g.cat_id = c.cat_id')
                        ->where($where)
                        ->group('c.cat_id')
                        ->order(['c.cat_id'=>'desc'])
                        ->select()
                       ;
     
        return $res;
    }

    public function getOneData($where) {
        $data = $this->where($where)->find();
        return $data;
    }

}