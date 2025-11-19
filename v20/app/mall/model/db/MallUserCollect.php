<?php
/**
 * 商品收藏
 */

namespace app\mall\model\db;
use think\Model;
class MallUserCollect extends Model
{
    //查询数据
    public function getOne($goods_id,$uid,$store_id){
        $where = [
            ['goods_id','=',$goods_id],
            ['uid','=',$uid],
            ['is_del','=',0],
            ['store_id','=',$store_id],
        ];
        $info = $this->where($where)->find();
        return empty($info)?[]:$info->toArray();
    }

    //插入收藏数据
    public function insertDate($data){

        if($data['id']){
            $this->find($data['id']);
             $ret=$this->update($data);
            //var_dump($this->getLastSql());
            return $ret;
        }else{
            return $this->save($data);
        }

    }
    //获取某个用户的收藏
    public function getCollections($where){
        $arr = $this->field(true)->where($where)->select()->toArray();
        return $arr;
    }
}