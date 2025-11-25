<?php

/**
 * 商品收藏
 */
namespace app\mall\model\service;


use app\mall\model\db\MallUserCollect;

class MallUserCollectService
{
    public $MallUserCollect = null;

    public function __construct()
    {
        $this->MallUserCollect = new MallUserCollect();
    }
    /**
     * [addCollect 商品收藏]
     * @Author   Mrdeng
     * @DateTime 2020-07-09T14:17:45+0800
     * @param    [type]                   $goods_id [description]
     * @param    [type]                   $uid     [description]
     * @param    [type]                   $store_id     [description]
     */
    public function addCollect($goods_id,$uid,$store_id){
        if(empty($goods_id)){
            throw new \think\Exception("商品信息缺失！");
        }

        $exist = $this->MallUserCollect->getOne($goods_id,$uid,$store_id);
        $data = [];
        $return=[];
        if($exist){
            //存在修改收藏转态
            $data['id']          = $exist['id'];
            if($exist['is_collect']==1){
                $return['is_col']=0;
                $data['is_collect']=2;
            }else{
                $return['is_col']=1;
                $data['is_collect']=1;
            }
            $data['update_time'] =  time();
        }
        else{
            //不存在添加收藏
            $return['is_col']=1;
            $data['id']="";
            $data['store_id']    =  $store_id;
            $data['uid']         =  $uid;
            $data['goods_id']    =  $goods_id;
            $data['is_collect']=1;
            $data['create_time'] =  time();
        }
        $ret=$this->MallUserCollect->insertDate($data);
        if($ret){
            return $return;
        }else{
            return false;
        }
    }

    /**
     * @param $log_uid
     */
    public function getCollections($log_uid, $goods_id){
        if(empty($log_uid)) return [];
        $where = ['uid'=>$log_uid,'is_collect'=>1,'goods_id'=>$goods_id];
        $arr = $this->MallUserCollect->getCollections($where);
        if(!empty($arr)){
            return $arr;
        }else{
            return [];
        }
    }
}