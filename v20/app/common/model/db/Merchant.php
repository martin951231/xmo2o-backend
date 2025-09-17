<?php
/**
 * 商户表
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 18:26
 */

namespace app\common\model\db;

use think\Model;

class Merchant extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根据mer_id获取商户信息
     * User: chenxiang
     * Date: 2020/6/1 18:28
     * @param $id
     * @return array|bool|Model
     */
    public function getInfo($id){
        $now_merchant = $this->field(true)->where(array('mer_id'=>$id))->find();
        if(empty($now_merchant)){
            return false;
        }else{
            $now_merchant=$now_merchant->toArray();
        }
        return $now_merchant;
    }
    /**
     * 查询有效的店铺
     */
    public function getStoreList($where, $page, $pageSize,$field='b.*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('a')
            ->join($prefix.'merchant_store b','a.mer_id = b.mer_id')
            ->where($where)->field($field);
        if($page==0){
            $result=$result->select();
        }else{
            $result=$result->page($page, $pageSize)->select();
        }
        if (!empty($result)) {
            return $result->toArray();
        } else {
            return [];
        }
    }
    public function getStoreListCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('a')
            ->join($prefix.'merchant_store b','a.mer_id = b.mer_id')
            ->where($where)->count();
        return $count;
    }
}