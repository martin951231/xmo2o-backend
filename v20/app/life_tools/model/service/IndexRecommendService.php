<?php


namespace app\life_tools\model\service;


use app\life_tools\model\db\IndexRecommend;
use app\life_tools\model\db\IndexRecommendGoods;
use app\life_tools\model\db\LifeTools;
use app\life_tools\model\db\LifeToolsTicket;
use app\mall\model\db\MallGoods;
use app\shop\model\db\ShopGoods;

class IndexRecommendService
{
    /**
     * 文旅
     */
    public function getList($param)
    {
        try{
        $out['recommend']=[];
        $out['recommend_goods']=[];
        $msg=(new IndexRecommend())->getDetail(['goods_type'=>$param['goods_type']]);
        if(empty($msg)){
           return $out;
        }
        $out['recommend']=$msg;
        $where=['rg.recommend_id'=>$msg['id']];
        $field='rg.*';
        $order='rg.sort desc';
        $out['recommend_goods']=(new IndexRecommendGoods())->getGoodsList($param,$where,$field,$order);
        return $out;
        }catch (\Exception $e){
            dd($e);
        }
    }

    /**
     * @return \json
     * 更新推荐
     */
    public function updateRec($param)
    {
        $where=['goods_type'=>$param['goods_type']];
        $data['is_show']=$param['is_show'];
        $data['title']=$param['title'];
        $data['sort']=$param['sort'];
        $data['column_num']=$param['column_num'] ?: 3;
        $ret=(new IndexRecommend())->updateThis($where,$data);
        if($ret!==false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @return \json
     * 更新商品
     */
    public function updateRecGoods($param)
    {
        $where=['id'=>$param['id']];
        $data['sort']=$param['sort'];
        $ret=(new IndexRecommendGoods())->updateThis($where,$data);
        if($ret!==false){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 添加商品
     */
    public function addRecGoods($param)
    {
        switch ($param['goods_type']){
            case 'ticket':
                if(!empty($param['selectedRowKeys'])){
                    foreach ($param['selectedRowKeys'] as $k=>$v){
                        $where=[['r.ticket_id','=',$v]];
                        $list=(new LifeToolsTicket())->getDetail($where,'r.mer_id');
                        $data['goods_id']=$v;
                        $data['mer_id']=$list['mer_id'];
                        $data['recommend_id']=$param['recommend_id'];
                        (new IndexRecommendGoods())->add($data);
                    }
                }
                break;
            case 'sport':
                if(!empty($param['selectedRowKeys'])){
                    foreach ($param['selectedRowKeys'] as $k=>$v){
                        $where=[['tools_id','=',$v]];
                        $list=(new LifeTools())->getDetail($where,'mer_id');
                        $data['goods_id']=$v;
                        $data['mer_id']=$list['mer_id'];
                        $data['recommend_id']=$param['recommend_id'];
                        (new IndexRecommendGoods())->add($data);
                    }
                }
                break;
            case 'shop':
                if(!empty($param['selectedRowKeys'])){
                    foreach ($param['selectedRowKeys'] as $k=>$v){
                        $where=[['r.goods_id','=',$v]];
                        $list=(new ShopGoods())->getDetail($where,'m.mer_id,r.store_id');
                        $data['goods_id']=$v;
                        $data['mer_id']=$list['mer_id'];
                        $data['store_id']=$list['store_id'];
                        $data['recommend_id']=$param['recommend_id'];
                        (new IndexRecommendGoods())->add($data);
                    }
                }
                break;
            case 'mall':
                if(!empty($param['selectedRowKeys'])){
                    foreach ($param['selectedRowKeys'] as $k=>$v){
                        $list=(new MallGoods())->getOne($v);
                        $data['goods_id']=$v;
                        $data['mer_id']=$list['mer_id'];
                        $data['store_id']=$list['store_id'];
                        $data['recommend_id']=$param['recommend_id'];
                        (new IndexRecommendGoods())->add($data);
                    }
                }
                break;
        }
        return true;
    }

    /**
     * 删除商品
     */
    public function delRecGoods($param)
    {
        if(is_array($param['id'])){
            $where=[['id','in',$param['id']]];
        }else{
            $where=[['id','=',$param['id']]];
        }
        $ret=(new IndexRecommendGoods())->where($where)->delete();
        return $ret;
    }
    /**
     * 商城商品
     */
    public function getGoodsList($param)
    {
        $list=[];
        $msg=(new IndexRecommend())->getDetail(['goods_type'=>$param['goods_type']]);
        $arr=(new IndexRecommendGoods())->getCol(['recommend_id'=>$msg['id']],'goods_id');
        switch ($param['goods_type']){
            case 'ticket':
                $field='g.title as goods_name,r.ticket_id as goods_id,r.price as goods_price,m.name';
                $where=[['r.status','=',1],['r.is_del','=',0],['g.status','=',1],['g.is_del','=',0],['r.ticket_id','not in',$arr],['g.type','=','scenic']];
                if(!empty($param['keyWords'])){
                    array_push($where,['r.title','like','%'.$param['keyWords'].'%']);
                }
                $list=(new LifeToolsTicket())->getListByTool($where,$field,$param['page'],$param['pageSize']);
                break;
            case 'sport':
                $where=[['r.status','=',1],['r.is_del','=',0],['r.type','in',['stadium','course']],['r.tools_id','not in',$arr]];
                if(!empty($param['keyWords'])){
                    array_push($where,['r.title','like','%'.$param['keyWords'].'%']);
                }
                $list=(new LifeTools())->getListTool($where,'r.title as goods_name,r.tools_id as goods_id,r.money as goods_price,m.name',$param['page'],$param['pageSize']);
                break;
            case 'shop':
                $where=[['r.status','=',1],['r.goods_type','=',0],['r.goods_id','not in',$arr],['m.status','=',1],['ms.status','=',1],['ms.have_shop','=',1]];
                if(!empty($param['keyWords'])){
                    array_push($where,['r.name','like','%'.$param['keyWords'].'%']);
                }
                $list=(new ShopGoods())->getListTool($where,'r.name as goods_name,r.goods_id,r.price as goods_price,m.name',$param['page'],$param['pageSize']);
                break;
            case 'mall':
                $where=[['r.status','=',1],['r.is_del','=',0],['r.goods_id','not in',$arr],['m.status','=',1],['ms.status','=',1],['ms.have_mall','=',1]];
                if(!empty($param['keyWords'])){
                    array_push($where,['r.name','like','%'.$param['keyWords'].'%']);
                }
                $list=(new MallGoods())->getListTool($where,'r.name as goods_name,r.goods_id,r.price as goods_price,m.name',$param['page'],$param['pageSize']);
                break;
        }
        return $list;
    }
}