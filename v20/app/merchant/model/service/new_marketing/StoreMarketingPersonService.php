<?php

namespace app\merchant\model\service\new_marketing;
use app\common\model\db\User;
use app\merchant\model\db\StoreMarketingPerson;
use app\store_marketing\model\db\StoreMarketingPersonStore;

class StoreMarketingPersonService
{
    /**
     * 验证手机号
     */
    public function regPhone($param)
    {
        if(empty($param['phone'])){
            $assign['status'] = 3;
            $assign['data'] = [];
            return $assign;
        }
        $ret = (new User())->getOne(['phone'=>$param['phone']]);
        if (!empty($ret)) {
            $ret = $ret->toArray();
            $user = (new StoreMarketingPerson())->getOne(['sp.uid' => $ret['uid'], 's.is_del' => 0, 's.store_id' => $param['store_id']],'sp.*,s.status');
            if (empty($user)) {//这个用户可以添加
                $assign['status'] = 1;
                $assign['data'] = $ret;
            } else {//有这个分销员了
                if($user['status']!=3){
                    $assign['status'] = 2;
                    $assign['data'] = [];
                }else{
                    $assign['status'] = 1;
                    $assign['data'] = $ret;
                }
            }
        } else {//没有这个用户
            $assign['status'] = 1;
            $assign['data'] = [];
        }
        return $assign;
    }

    /**
     * @return \json
     * 分销员列表
     */
    public function getPersonList($param){
        $where[] = ['s.is_del','=',0];
        if(!empty($param['store_id'])){
            array_push($where, ['s.store_id', '=', $param['store_id']]);
        }
        if (!empty($param['start_time']) && !empty($param['end_time'])) {
            $param['start_time'] = strtotime($param['start_time']);
            array_push($where, ['sp.create_time', '>', $param['start_time']]);
        }

        if (!empty($param['end_time']) && $param['start_time'] != $param['end_time']) {
            $param['end_time'] = strtotime($param['end_time'] . " 23:59:59");
            array_push($where, ['sp.create_time', '<=', $param['end_time']]);
        }

        if(!empty($param['status'])){
            array_push($where, ['s.status', '=', $param['status']]);
        }

        if(!empty($param['name'])){
            array_push($where, ['sp.name', 'like', '%' . $param['name'] . '%']);
        }
        $list=(new StoreMarketingPerson())->getSome($where,"sp.*,s.mer_id,s.store_id,s.ratio_type,s.ratio,s.status,s.all_achievement,s.all_percentage",'s.id desc');
        if(!empty($list['list'])){
            foreach ($list['list'] as $k=>$v){
              /*if($v['status']==1){
                  $list['list'][$k]['status']="待确认";
              }elseif ($v['status']==2){
                  $list['list'][$k]['status']="已绑定";
              }else{
                  $list['list'][$k]['status']="已拒绝";
              }*/

              if(!empty($v['create_time'])){
                  $list['list'][$k]['create_time']=date("Y-m-d H:i:s",$v['create_time']);
              }

              if($v['ratio_type']==1){
                  $list['list'][$k]['ratio_type']="统一比例";
              }else{
                  $list['list'][$k]['ratio_type']="商品比例";
              }
            }
        }
        return $list;
    }

    /**
     * @param $id
     * @return array
     * 编辑营销人员
     */
    public function editPerson($param){
        $msg=(new StoreMarketingPerson())->getOne(['sp.id'=>$param['id'],'s.store_id'=>$param['store_id']],'sp.*,s.mer_id,s.store_id,s.ratio_type,s.ratio,s.status,s.all_achievement,s.all_percentage');
        return $msg;
    }

    /**
     * @param $param
     * 添加保存分销员
     */
    public function addPerson($param){
        $data['uid']=$param['uid'];
        $data['name']=$param['name'];
        $data['phone']=$param['phone'];
        $data['create_time']=time();
        $find=(new StoreMarketingPerson())->getOneMsg(["phone"=>$data['phone']]);
        if(empty($find)){
            $ret=(new StoreMarketingPerson())->add($data);
        }else{
            $ret=$find['id'];
            $store=(new StoreMarketingPersonStore())->getOne(['person_id'=>$ret,"store_id"=>$param['store_id']]);
            if(!empty($store)){
                $store=$store->toArray();
                if($store['status']==3){
                    (new StoreMarketingPersonStore())->updateThis(['person_id'=>$ret,"store_id"=>$param['store_id']],['status'=>1]);
                    return $ret;
                }
            }
        }
            $data1['person_id']=$ret;
            $data1['mer_id']=$param['mer_id'];
            $data1['store_id']=$param['store_id'];
            $data1['ratio_type']=$param['ratio_type'];
            $data1['ratio']=$param['ratio'];
            $data1['create_time']=time();
            if($param['ratio_type']==2){
                unset($data1['ratio']);
            }
            $ret=(new StoreMarketingPersonStore())->add($data1);
        return $ret;
    }

    /**
     * @param $param
     * @return bool
     * 修改保存数据
     */
    public function savePerson($param){
        $data['uid']=$param['uid'];
        $data['name']=$param['name'];
        $data['phone']=$param['phone'];
        $ret=(new StoreMarketingPerson())->updateThis(['id'=>$param['id']],$data);
        if($ret!==false){
            $data1['ratio_type']=$param['ratio_type'];
            $data1['ratio']=$param['ratio'];
            if($param['ratio_type']==2){
                unset($data1['ratio']);
            }
            (new StoreMarketingPersonStore())->updateThis(['person_id'=>$param['id'],'store_id'=>$param['store_id']],$data1);
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $param
     * @return bool
     * 删除数据
     */
    public function delPerson($param){
        /*$ret=(new StoreMarketingPerson())->updateThis(['person_id'=>$param['id'],['is_del'=>1]);
        if($ret){*/
            (new StoreMarketingPersonStore())->updateThis(['person_id'=>$param['id'],'store_id'=>$param['store_id']],['is_del'=>1]);
            return true;
       /* }else{
            return false;
        }*/
    }
}