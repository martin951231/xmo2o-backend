<?php

/**
 * @Author: jjc
 * @Date:   2020-06-16 15:34:48
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-18 10:58:37
 */
namespace app\mall\model\db;
use think\Model;

class MallCartNew extends Model {
    use \app\common\model\db\db_trait\CommonFunc;
	public function getOne($sku_id,$uid, $share_id){
        $where = [
            ['sku_id','=',$sku_id],
            ['uid','=',$uid],
            ['share_id','=',$share_id],
            ['is_del','=',0],
        ];
        $info = $this->where($where)->find();
        return empty($info)?[]:$info->toArray();
    }

    public function getOneBySkuAndProperty($sku_id,$property,$uid, $share_id){
        $where = [
            ['sku_id','=',$sku_id],
            ['notes','=',$property],
            ['uid','=',$uid],
            ['share_id','=',$share_id],
            ['is_del','=',0],
        ];
        $info = $this->where($where)->find();
        return empty($info)?[]:$info->toArray();
    }

    //插入数据
    public function insertDate($data){
    	if($data['id']){
    		$this->find($data['id']);
    		return $this->update($data);
    	}else{
    		return $this->save($data);
    	}
    	
    }

    //获取购物车列表
    public function getAll($where){

    	$prefix = config('database.connections.mysql.prefix');//表前缀

    	return $this ->alias('c')
            	->join($prefix.'mall_goods_sku'.' s','c.sku_id = s.sku_id')
            	->where($where)
            	->field('c.id as cart_id,c.join_price,c.num,s.*')
            	->select()->toArray();

    }

    //删除购物车
    public function delCart($where){
    	return $this->where($where)->update(['is_del'=>1]);
    }

}