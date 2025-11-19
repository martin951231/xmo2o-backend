<?php


namespace app\flipping_event\model\service;


use app\common\model\service\UserService;
use app\flipping_event\model\db\FlippingEvent;
use app\flipping_event\model\db\FlippingEventAwards;
use app\flipping_event\model\db\FlippingEventAwardsLog;
use app\flipping_event\model\db\FlippingEventRules;
use app\flipping_event\model\db\FlippingEventRulesLog;
use think\facade\Db;

class FlippingEventRulesService
{
    /**
     * 获取活动规则列表
     * @param $params
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRulesList($params){
        $id = $params['id'];
        if(!$id){
            throw new \think\Exception(L_('参数错误！'));
        }
        $detail = (new FlippingEvent())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$detail){
            throw new \think\Exception(L_('信息不存在/已删除！'));
        }
        //获取奖项列表
        $awards = (new FlippingEventAwards())->field('id,title')->where(['event_id'=>$id,'type'=>0])->select()->toArray();
        $awards_name = [];
        foreach ($awards as $k=>$v){
            $awards[$k]['id'] = (string)$v['id'];
            $awards_name[$v['id']] = $v['title'];
        }
        $list = (new FlippingEventRules())->where(['event_id'=>$id])->select()->toArray();
        foreach ($list as $key=>$value){
            $list[$key]['awards_ids'] = $value['awards_ids']?explode(',',$value['awards_ids']):[];
            $list[$key]['img'] = $value['img']?replace_file_domain($value['img']):'';
            $list[$key]['awards_info'] = $value['awards_info']?json_decode($value['awards_info'],true):[];
            $awards_name_info = [];
            foreach ($list[$key]['awards_info'] as $kk=>$val){
                $awards_name_info[] = isset($awards_name[$val['awards_id']])?$awards_name[$val['awards_id']].'X'.$val['num']:'';
                $list[$key]['awards_info'][$kk]['title'] = $awards_name[$val['awards_id']]??'';
            }
            $list[$key]['awards_info_name'] = $awards_name_info;
        }
        $data = [
            'list' => $list?:[],
            'type_arr' => [
                ['label'=>'实物','value'=>0],
                ['label'=>'余额','value'=>1],
                ['label'=>'积分','value'=>2],
            ],
            'awards' => $awards?:[],
        ];
        return $data;
    }

    /**
     * 添加/编辑
     * @param $params
     */
    public function saveData($params){
        $id = $params['id'];
        $awards_info = $params['awards_ids'];
        $event_id = $params['event_id'];
        $save_data = [
            'title' => $params['title'],
            'type' => $params['type'],
            'awards_val' => $params['awards_val'],
            'stocks_num' => $params['stocks_num'],
            'img' => $params['img']
        ];
        if(!$save_data['title']){
            throw new \think\Exception(L_('兑奖名称不能为空！'));
        }

        if(!in_array($save_data['type'],[0,1,2])){
            throw new \think\Exception(L_('奖品类型错误！'));
        }
        if($save_data['stocks_num']<=0){
            throw new \think\Exception(L_('活动库存必须大于0！'));
        }
        if(in_array($save_data['type'],[1,2])&&(!$save_data['awards_val']||$save_data['awards_val']<=0)){
            throw new \think\Exception(L_('将品值必须大于0！'));
        }
        if(!$awards_info){
            throw new \think\Exception(L_('请选择抽卡奖品！'));
        }
        $awards_ids = array_column($awards_info,'awards_id');
        $value_ids = (new FlippingEventAwards())->where([['id','in',$awards_ids]])->column('id');
        if(!$value_ids){
            throw new \think\Exception(L_('选择抽卡奖品已删除！'));
        }
        foreach ($awards_info as $key=>$item){
            if(!in_array($item['awards_id'],$value_ids)){
                unset($awards_info[$key]);
            }else{
                if(!is_numeric($item['num'])||$item['num']<=0){
                    throw new \think\Exception(L_('抽卡数量必须大于0！'));
                }
            }
        }
        $save_data['awards_ids'] = implode(',',$value_ids);
        $save_data['awards_info'] = json_encode(array_values($awards_info));
        try {
            if($id){
                if(!$info = (new FlippingEventRules())->where(['id'=>$id,'event_id'=>$event_id])->findOrEmpty()->toArray()){
                    throw new \think\Exception(L_('修改信息不存在！'));
                }
                if((new FlippingEventRules())->where([['title','=',$save_data['title']],['id','<>',$id]])->findOrEmpty()->toArray()){
                    throw new \think\Exception(L_('兑奖名称已存在！'));
                }
                (new FlippingEventRules())->where(['id'=>$id])->save($save_data);
            }else{
                if(!$event_id){
                    throw new \think\Exception(L_('活动不存在！'));
                }
                if((new FlippingEventRules())->where(['title'=>$save_data['title']])->findOrEmpty()->toArray()){
                    throw new \think\Exception(L_('兑奖名称已存在！'));
                }
                $save_data['event_id'] = $event_id;
                (new FlippingEventRules())->insertGetId($save_data);
            }
            
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 规则删除
     * @return \think\response\Json
     */
    public function delete($params){
        $id = $params['id'];
        if(!$id){
            throw new \think\Exception(L_('参数错误！'));
        }
        $detail = (new FlippingEventRules())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$detail){
            throw new \think\Exception(L_('信息不存在/已删除！'));
        }
        try {
            (new FlippingEventRules())->where(['id'=>$id])->delete();
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 兑换中心
     * @param $params
     * @throws \think\Exception
     */
    public function exchangeCenter($params){
        $id = $params['id'];
        $uid = $params['uid'];
        if(!$id){
            throw new \think\Exception(L_('参数错误！'));
        }
        $event_info = (new FlippingEvent())->where(['id'=>$id])->findOrEmpty()->toArray();
        if(!$event_info){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        $rules_list = (new FlippingEventRules())->field('id,img,title,type,awards_val,awards_ids,awards_info')->where(['event_id'=>$id])->select()->toArray();
        $awards_log_list = (new FlippingEventAwardsLog())->field('count(awards_id) as total,awards_id')->where(['uid'=>$uid,'event_id'=>$id,'awards_type'=>0,'status'=>1])->group('awards_id')->select()->toArray();
        $user_awards_total = array_column($awards_log_list,'total','awards_id');
        foreach ($rules_list as $key=>$item){
            $rules_list[$key]['img'] =$item['img']?replace_file_domain($item['img']):'';
            $rules_list[$key]['can_exchange'] = 0;
            $awards_ids = $item['awards_ids']?explode(',',$item['awards_ids']):[];
            $awards_info = $item['awards_info']?json_decode($item['awards_info'],true):[];
            $awards_info = array_column($awards_info,'num','awards_id');
            $rules_list[$key]['awards_list'] = [];
            if($awards_ids){
                $awards_list = (new FlippingEventAwards())->field('id,img,title')->where([['event_id','=',$id],['id','in',$awards_ids]])->select()->toArray();
                $rules_list[$key]['can_exchange'] = 1;
                $awards_arr = [];
                foreach ($awards_list as $k=>$v){
                    $v['is_have'] = 1;
                    $v['ready_num'] = $user_awards_total[$v['id']]??0;
                    $v['img'] = $v['img']?replace_file_domain($v['img']):'';
                    if(empty($user_awards_total[$v['id']])){
                        $rules_list[$key]['can_exchange'] = 0;
                    }
                    $awards_num = $awards_info[$v['id']]??0;
                    for($i=0;$i<$awards_num;$i++){
                        if($i>=$v['ready_num']){
                            $rules_list[$key]['can_exchange'] = 0;
                            $v['is_have'] = 0;
                        }
                        $awards_arr[] = $v;
                    }
                }
                $rules_list[$key]['awards_list'] = $awards_arr?:[];
            }
        }
        $data = [
            'list' => $rules_list,
            'content' => $event_info['content'],
            'id' => $id,
            'img' => $event_info['img']?replace_file_domain($event_info['img']):''
        ];
        return $data;
    }

    /**
     * 兑换奖品
     */
    public function exchangeAwards($params){
        $event_id = $params['event_id'];
        $id = $params['id'];
        $uid = $params['uid'];
        $event_info = (new FlippingEvent())->where(['id'=>$event_id])->findOrEmpty()->toArray();
        if(!$event_info){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        if($event_info['status']!=1){
            throw new \think\Exception(L_('活动已关闭！'));
        }
        $rules_info = (new FlippingEventRules())->where(['id'=>$id,'event_id'=>$event_id])->findOrEmpty()->toArray();
        if(!$rules_info){
            throw new \think\Exception(L_('兑换奖项不存在！'));
        }
        if($rules_info['stocks_num']<=0){
            throw new \think\Exception(L_('该奖项已兑换完！'));
        }
        $awards_ids = $rules_info['awards_ids']?explode(',',$rules_info['awards_ids']):[];
        $awards_info = $rules_info['awards_info']?json_decode($rules_info['awards_info'],true):[];
        $awards_log = (new FlippingEventAwardsLog())->field('count(awards_id) as total,awards_id')->where([['uid','=',$uid],['status','=',1],['awards_id','in',$awards_ids],['event_id','=',$event_id]])->group('awards_id')->select()->toArray();
        $awards_log = array_column($awards_log,'total','awards_id');
        if(!$awards_ids||!$awards_log||!$awards_info){
            throw new \think\Exception(L_('未集齐卡牌，不能兑换！'));
        }
        foreach ($awards_info as $item){
            if(empty($awards_log[$item['awards_id']])||$awards_log[$item['awards_id']]<$item['num']){
                throw new \think\Exception(L_('未集齐卡牌，不能兑换！'));
            }
        }

        $saveData = [
            'uid' => $uid,
            'event_id' => $event_id,
            'rules_id' => $id,
            'rules_title' => $rules_info['title'],
            'rules_type' => $rules_info['type'],
            'create_time' => time(),
            'status' => $rules_info['type']==0?0:1,
            'rules_awards' => $rules_info['awards_val']
        ];
        Db::startTrans();
        try {
            $res_id = (new FlippingEventRulesLog())->insertGetId($saveData);
            if($rules_info['type']==1){
                (new UserService())->addMoney($uid,$rules_info['awards_val'],'兑换卡牌增加余额',25,$res_id);
            }elseif($rules_info['type']==2){
                (new UserService())->addScore($uid, $rules_info['awards_val'], L_('兑换卡牌增加') . cfg('score_name'),0,['order_type'=>'flipping_event_rules','order_id'=>$res_id]);
            }
            foreach ($awards_info as $item){
                $use_log_ids = (new FlippingEventAwardsLog())->where(['awards_id'=>$item['awards_id'],'status'=>1,'uid'=>$uid])->order('id asc')->limit(0,$item['num'])->select()->toArray();
                foreach ($use_log_ids as $k=>$v){
                    (new FlippingEventAwardsLog())->where(['id'=>$v['id']])->save(['status'=>2]);
                }
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
        $link_url = cfg('site_url').'/packapp/plat/pages/activity/mahjongCard/userPrize';
        if($rules_info['type'] == 0){
            $link_url = cfg('site_url').'/packapp/im/index.html#/chatInterface?from_user=user_'.$uid.'&to_user=plat_0_&relation=user2plat';
        }
        return [
            'type' => $rules_info['type'],
            'rules_awards' => $rules_info['awards_val'],
            'link_url' => $link_url,
            'img' => $rules_info['img']?replace_file_domain($rules_info['img']):'',
            'title' => $rules_info['title']
        ];
    }
}