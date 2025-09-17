<?php


namespace app\merchant\model\service\deposit;


use app\common\model\db\CardUserlist;
use app\common\model\service\weixin\TemplateNewsService;
use app\mall\model\db\User;
use app\merchant\model\db\CardNewDepositGoods;
use app\merchant\model\db\CardNewDepositGoodsBindGoods;
use app\merchant\model\db\CardNewDepositGoodsBindUser;
use app\merchant\model\db\CardNewDepositGoodsMessage;
use app\merchant\model\db\CardNewDepositGoodsSort;
use think\facade\Db;
class DepositGoodsService
{
    /**
     * @return mixed
     * 添加的商品列表
     */
    public function getGoodsMenuList($param)
    {
        $sort_list=(new CardNewDepositGoodsSort())->where(['mer_id'=>$param['mer_id'],'is_del'=>0])->order('sort desc')->select()->toArray();
        $list=[];
        foreach ($sort_list as $k=>$v){
            $goods_list=(new CardNewDepositGoods())->getSome(['sort_id'=>$v['sort_id'],'is_del'=>0])->toArray();
            if(empty($goods_list)){
                continue;
            }else{
                $msg_array=[];
                $data['cat_name']=$v['name'];
                $data['sort_id']=$v['sort_id'];
                foreach ($goods_list as $k1=>$v1){
                   $child['goods_image']=empty($v1['image'])?"":replace_file_domain($v1['image']);
                   $child['goods_name']=$v1['name'];
                   $child['term_time']=date("Y.m.d",$v1['start_time'])."-".date("Y.m.d",$v1['end_time']);
                   $child['stock_num']=$v1['stock_num'];
                   $child['start_time']=$v1['start_time'];
                   $child['end_time']=$v1['end_time'];
                   $child['sort_id']=$v1['sort_id'];
                    $child['goods_id']=$v1['goods_id'];
                   $msg_array[]=$child;
                }
                $data['children']=$msg_array;
            }
            $list[]=$data;
        }
        return $list;
    }

    /**
     * 添加商品
     */
    public function addGoods($param)
    {
        Db::startTrans();
        try{
            $user=(new CardUserlist())->getOne(['id'=>$param['card_id'],'mer_id'=>$param['mer_id']]);
            $openid=0;
            if(!empty($user)){
                $user=$user->toArray();
                $info = (new User())->getUserById($user['uid']);
                $openid=$info['openid'];
            }
            foreach ($param['goods_list'] as $k=>$v){
                $message['mer_id']=$good_add['mer_id']=$param['mer_id'];
                $message['store_id']=$good_add['store_id']=$param['store_id'];
                $message['staff_id']=$good_add['staff_id']=$param['staff_id'];
                $message['create_time']=time();
                $good_add['card_id']=$param['card_id'];

                $good_add['goods_id']=$v['goods_id'];
                $good_add['start_time']=$v['start_time'];
                $good_add['end_time']=$v['end_time'];
                $good_add['num']=$v['num'];

                $good_add['number'] = "deposit".createRandomStr();//商品二维码
                $good_add['use_num']=$param['use_num'];
                $good_add['create_time']=$param['create_time'];
                $bind_goods['bind_id']=(new CardNewDepositGoodsBindUser())->add($good_add);
                if($bind_goods['bind_id']){
                    $message['bind_id']=$bind_goods['bind_id'];
                    $message['type']=1;
                    (new CardNewDepositGoodsMessage())->add($message);//添加消息
                    (new CardNewDepositGoods())->setDec(['goods_id'=>$good_add['goods_id']],'stock_num',$v['num']);
                    (new CardNewDepositGoods())->setInc(['goods_id'=>$good_add['goods_id']],'deposit_count',$v['num']);
                    for ($i=0;$i<$v['num'];$i++){
                        $bind_goods['mer_id']=$good_add['mer_id'];
                        $bind_goods['goods_id']=$good_add['goods_id'];
                        $bind_goods['number']=time().mt_rand(0,10000);
                        $bind_goods['create_time']=time();
                        (new CardNewDepositGoodsBindGoods())->add($bind_goods);
                    }
                }
            }
            if(!empty($openid)){
                $msgDataWx = [
                    'href' =>get_base_url('pages/deposit/msgList?mer_id='.$param['mer_id']),
                    'wecha_id' => $openid,
                    'first' => L_('店员已经添加了你的寄存商品'),
                    'keyword1' => "寄存商品添加",
                    'keyword2' => L_('店员已添加'),
                    'keyword3' => date("Y-m-d H:i"),
                    'remark' => L_('点击查看详情'),
                ];
                (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
            }

            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return false;
        }
        return true;
    }

    /**
     * 编辑商品
     */
    public function editGoods($param)
    {
        $where=[['a.staff_id','=',$param['staff_id']],['a.id','=',$param['id']]];
        $msg=(new CardNewDepositGoodsBindUser())->getGoodsByUser($where,'a.num,a.start_time,a.end_time,b.goods_id,b.name,b.sort_id');
        if(!empty($msg)){
            $msg['start_time']=date('Y.m.d',$msg['start_time']);
            $msg['end_time']=date('Y.m.d',$msg['end_time']);
            $msg['sort_name']=(new CardNewDepositGoodsSort())->getSortName(['sort_id'=>$msg['sort_id']],'name');
            $msg['num']=(new CardNewDepositGoodsBindGoods())->getCount(['bind_id'=>$param['id'],'goods_id'=>$msg['goods_id'],'is_used'=>0]);
            $msg['stock_num'] = (new CardNewDepositGoods())->getColumnValue(['goods_id'=>$msg['goods_id']],'stock_num');
            $deposit_count = (new CardNewDepositGoods())->getColumnValue(['goods_id'=>$msg['goods_id']],'deposit_count');
            $msg['stock_num'] = $msg['stock_num'] - $deposit_count;
            $msg['goods_name']=$msg['name'];
        }
        return $msg;
    }

    /**
     * 保存商品
     */
    public function saveGoods($param)
    {
        $where=[['id','=',$param['id']]];
        $data['start_time']=strtotime($param['start_time']);
        $data['end_time']=strtotime($param['end_time']);
        $data['num']=$param['num'];

        $msg = (new CardNewDepositGoodsBindUser())->getOne($where);
        if(!empty($msg)) {
            $msg = $msg->toArray();
        }else{
            return false;
        }

        $data['num'] = $param['num'] + $msg['use_num'];
        $ret=(new CardNewDepositGoodsBindUser())->updateThis($where,$data);
        $goods_list=(new CardNewDepositGoodsBindGoods())->getSome(['bind_id'=>$param['id'],'is_used'=>0]);
        if($ret!==false){
            $message['mer_id']=$msg['mer_id'];
            $message['staff_id']=$param['staff_id'] ?? 0;
            $message['store_id']=$msg['store_id'];
            $message['bind_id']=$param['id'];
            $message['create_time']=time();
            $message['type']=2;
            (new CardNewDepositGoodsMessage())->add($message);//修改消息
            // 变更数量
            $changeNum = $param['num'] - count($goods_list);
            if($changeNum < 0){//要是修改了数量
                $delNum = -1*$changeNum;
                for ($i=0;$i<$delNum;$i++){
                    (new CardNewDepositGoodsBindGoods())->delData(['id'=>$goods_list[$i]['id']]);
                }
                (new CardNewDepositGoods())->setDec(['goods_id'=>$msg['goods_id']],'deposit_count',$delNum);
                (new CardNewDepositGoods())->setInc(['goods_id'=>$msg['goods_id']],'stock_num',$delNum);
            }elseif($changeNum > 0){
                for ($i=0; $i<$changeNum; $i++){
                    $bind_goods = [];
                    $bind_goods['bind_id']=$msg['id'];
                    $bind_goods['mer_id']=$msg['mer_id'];
                    $bind_goods['goods_id']=$msg['goods_id'];
                    $bind_goods['number']=time().mt_rand(0,10000);
                    $bind_goods['create_time']=time();
                    (new CardNewDepositGoodsBindGoods())->add($bind_goods);
                }
                (new CardNewDepositGoods())->setDec(['goods_id'=>$msg['goods_id']],'stock_num',$changeNum);
                (new CardNewDepositGoods())->setInc(['goods_id'=>$msg['goods_id']],'deposit_count',$changeNum);
            }

            $user=(new CardUserlist())->getOne(['id'=>$msg['card_id'],'mer_id'=>$msg['mer_id']]);
            $openid=0;
            if(!empty($user)){
                $user=$user->toArray();
                $info = (new User())->getUserById($user['uid']);
                $openid=$info['openid'];
            }
            if(!empty($openid)){
                $msgDataWx = [
                    'href' => get_base_url('pages/deposit/msgList?mer_id='.$msg['mer_id']),
                    'wecha_id' => $openid,
                    'first' => L_('店员已经编辑了你的寄存商品'),
                    'keyword1' => "寄存商品有修改",
                    'keyword2' => L_('店员已编辑'),
                    'keyword3' => date("Y-m-d H:i"),
                    'remark' => L_('点击查看详情'),
                ];
                (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
            }

            return true;
        }else{
            return false;
        }
    }

    /**
     * @return \json
     * 删除商品
     */
    public function deleteGoods($param)
    {
        $where=[['id','=',$param['id']]];
        $data['is_del']=1;
        $bindInfo = (new CardNewDepositGoodsBindUser())->getOne($where);
        $goodsCount = (new CardNewDepositGoodsBindGoods())->getCount(['bind_id'=>$param['id'],'is_used'=>0]);
        $ret=(new CardNewDepositGoodsBindUser())->updateThis($where,$data);
        if(!empty($bindInfo)) {
            $bindInfo = $bindInfo->toArray();
        }else{
            return false;
        }
        if($ret!==false){ 
            (new CardNewDepositGoods())->setDec(['goods_id'=>$bindInfo['goods_id']],'deposit_count',$goodsCount);
            (new CardNewDepositGoods())->setInc(['goods_id'=>$bindInfo['goods_id']],'stock_num',$goodsCount);
            // 添加操作日志
            $message['mer_id'] = $bindInfo['mer_id'];
            $message['store_id'] = $bindInfo['store_id'];
            $message['staff_id'] = $param['staff_id'] ?? 0;           
            $message['bind_id'] = $bindInfo['id'];
            $message['create_time']=time();
            $message['type'] = 3;
            (new CardNewDepositGoodsMessage())->add($message);//添加消息

            $user=(new CardUserlist())->getOne(['id'=>$bindInfo['card_id'],'mer_id'=>$bindInfo['mer_id']]);
            $openid=0;
            if(!empty($user)){
                $user=$user->toArray();
                $info = (new User())->getUserById($user['uid']);
                $openid=$info['openid'];
            }
            if(!empty($openid)){
                $msgDataWx = [
                    'href' => get_base_url('pages/deposit/msgList?mer_id='.$bindInfo['mer_id']),
                    'wecha_id' => $openid,
                    'first' => L_('店员已经删除了你的寄存商品'),
                    'keyword1' => "寄存商品删除",
                    'keyword2' => L_('店员已删除'),
                    'keyword3' => date("Y-m-d H:i"),
                    'remark' => L_('点击查看详情'),
                ];
                (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $param
     * 根据状态查找商品列表
     */
    public function getGoodsListByStatus($param){
        $out_put=array();
        $where[]=['a.is_del','=',0];
        $sql="";
        $time=strtotime(date('Y-m-d',time()));
        if($param['status']==0){//未使用
            // $where[]=['a.start_time','<=',$time];
            $where[]=['a.end_time','>=',$time];
            $sql=1;
        }elseif ($param['status']==2){//已过期
            $where[]=['a.end_time','<',$time];
        }

        if(!empty($param['mer_id'])){
            $where[]=['a.mer_id','=',$param['mer_id']];
        }

        if(!empty($param['staff_id'])){
            $where[]=['a.staff_id','=',$param['staff_id']];
        }

        if(!empty($param['store_id'])){
            $where[]=['a.store_id','=',$param['store_id']];
        }
        if(!empty($param['uid'])){
            $user=(new CardUserlist())->getOne(['uid'=>$param['uid'],'mer_id'=>$param['mer_id']]);
            if(!empty($user)){
                $user=$user->toArray();
                $where[]=['a.card_id','=',$user['id']];
            }
        }elseif (!empty($param['card_id'])){
            $where[]=['a.card_id','=',$param['card_id']];
        }

        switch ($param['status']){
                case 0://未使用
                case 2://已过期
                    if($param['status']==0){
                        $list=(new CardNewDepositGoodsBindUser())->getGoodsByUserList($where,'a.*,b.image,b.name',$order='a.id desc',$param['page'],$param['pageSize'],$sql);
                    }else{
                        $list=(new CardNewDepositGoodsBindUser())->getGoodsByUserList($where,'a.*,b.image,b.name',$order='a.id desc',$param['page'],$param['pageSize']);
                    }
                    if(!empty($list['list'])) {
                        foreach ($list['list'] as $k => $v) {
                                $out['goods_image'] = empty($v['image']) ? "" : replace_file_domain($v['image']);
                                $out['goods_name'] = $v['name'];
                                $out['num'] = $v['num'] - $v['use_num'];
                                $out['start_time'] = date("Y.m.d", $v['start_time']);
                                $out['end_time'] = date("Y.m.d", $v['end_time']);
                                $out['goods_id'] = $v['goods_id'];
                                $out['bind_id'] = $v['id'];
                                $out['is_show'] =($v['num'] - $v['use_num']) > 0?1:0;
                                $out_put[] = $out;
                        }
                    }
                    break;
                case 1://已使用
                    $where[]=['c.is_used','=',1];
                    $list=(new CardNewDepositGoodsBindUser())->getGoodsByUserGoodsList($where,'a.*,b.image,b.name',$order='a.id desc',$param['page'],$param['pageSize']);
                    if(!empty($list['list'])){
                        foreach ($list['list'] as $k=>$v){
                            $left=(new CardNewDepositGoodsBindGoods())->getCount(['bind_id'=>$v['id'],'goods_id'=>$v['goods_id'],'is_used'=>1]);
                                $out['goods_image']=empty($v['image'])?"":replace_file_domain($v['image']);
                                $out['goods_name']=$v['name'];
                                $out['num']=$left;
                                $out['start_time']=date("Y.m.d",$v['start_time']);
                                $out['end_time']=date("Y.m.d",$v['end_time']);
                                $out['goods_id']=$v['goods_id'];
                                $out['bind_id']=$v['id'];
                                $out['is_show'] =1;
                                $out_put[]=$out;
                        }
                    }
                    break;
                default://已过期
                    break;
            }
        $ret['list']=$out_put;
        return $ret;
    }
}