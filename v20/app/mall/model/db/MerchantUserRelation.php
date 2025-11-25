<?php


namespace app\mall\model\db;

use think\Model;
class MerchantUserRelation extends Model
{
    /**
     * [addCollect 商品收藏]
     * @Author   Mrdeng
     * @DateTime 2020-07-09T14:17:45+0800
     * @param    [type]                   $goods_id [description]
     * @param    [type]                   $uid     [description]
     * @param    [type]                   $store_id     [description]
     */
    public function addCollect($goods_id,$uid,$store_id,$mer_id){
        if(empty($goods_id)){
            throw new \think\Exception("商品信息缺失！");
        }
        $exist = $this->getOne($goods_id,$uid,$store_id);
        $data = [];
        $return=[];
        if($exist){
            //存在修改收藏转态
           $ret=$this->del($goods_id,$uid,$store_id);
           if($ret){
               $return['is_col']=0;
           }
        }
        else{
            //不存在添加收藏
            $return['is_col']=1;
            $data['store_id']    =  $store_id;
            $data['uid']         =  $uid;
            $data['goods_id']    =  $goods_id;
            $data['mer_id']=$mer_id;
            $data['dateline'] =  time();
            $data['from_merchant'] =  0;
            $data['type']= 'mall';
            $ret=$this->insertDate($data);
        }
        if($ret){
            return $return;
        }else{
            return false;
        }
    }

//插入收藏数据
    public function insertDate($data){
            return $this->save($data);
    }

    //查询数据
    public function getOne($goods_id,$uid,$store_id){
        $where = [
            ['goods_id','=',$goods_id],
            ['uid','=',$uid],
            ['store_id','=',$store_id],
        ];
        $info = $this->where($where)->find();
        return empty($info)?[]:$info->toArray();
    }

    //查询数据
    public function del($goods_id,$uid,$store_id){
        $where = [
            ['goods_id','=',$goods_id],
            ['uid','=',$uid],
            ['store_id','=',$store_id],
        ];
        $info = $this->where($where)->delete();
        return $info;
    }

}