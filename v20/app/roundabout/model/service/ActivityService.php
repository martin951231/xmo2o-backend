<?php
namespace app\roundabout\model\service;

use app\common\model\db\AppointOrder;
use app\common\model\db\CardNewCoupon;
use app\common\model\db\ShopOrder;
use app\common\model\db\SystemCoupon;
use app\common\model\db\SystemCouponStore;
use app\common\model\service\UserService;
use app\mall\model\db\MallOrder;
use app\mall\model\db\User;
use app\roundabout\model\db\Roundabout;
use app\roundabout\model\db\RoundaboutDetail;
use app\roundabout\model\db\RoundaboutUser;
use think\facade\Db;

class ActivityService
{
    /**
     * 活动列表
     * @param $params
     * @return array
     */
    public function getLsit($params){
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where[] = ['is_del','=',0];
        $field = 'id,start_time,end_time,status,name,join_people';
        $list = (new Roundabout())->getList($where,$field,$page,$pageSize);
        foreach ($list['data'] as $key=>$item){
            $list['data'][$key]['active_date'] = ($item['start_time']?date('Y-m-d H:i',$item['start_time']):'').'-'.($item['end_time']?date('Y-m-d H:i',$item['end_time']):'');
            $list['data'][$key]['active_url'] = cfg('site_url').'/packapp/plat/pages/activity/lottery/turntableLottery?id='.$item['id'];
        }
        return $list;
    }

    /**
     * 修改状态/删除
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function updataActivity($params){
        $id = $params['id'];
        $status = $params['status']??0;
        $type = $params['type']; //1-修改状态，2-删除
        if(!$id){
            throw new \think\Exception(L_('请选择要操作的活动！'));
        }
        $saveData = $where = [];
        if($type==1){
            if(!in_array($status,[0,1])){
                throw new \think\Exception(L_('状态错误！'));
            }
            $saveData['status'] = $status;
            $where[] = ['id','=',$id];
        }else{
            if(!is_array($id)){
                $id = array($id);
            }
            $saveData['is_del'] = 1;
            $where[] = ['id','in',$id];
        }
        try {
            (new Roundabout())->where($where)->save($saveData);
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 保存活动信息
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function saveActivity($params){
        $id = $params['id']??0;
        $saveData = [
            'name'=>$params['name'],
            'start_time'=>$params['start_time'],
            'end_time'=>$params['end_time'],
            'num'=>$params['num'],
            'bg_pic'=>$params['bg_pic'],
            'user_type'=>$params['user_type'],
            'user_register_day'=>$params['user_register_day'],
            'business_type'=>$params['business_type'],
            'amount'=>$params['amount'],
            'kefu_pic'=>$params['kefu_pic'],
            'bg_audio'=>$params['bg_audio'],
            'win_audio'=>$params['win_audio'],
            'not_win_audio'=>$params['not_win_audio'],
            'rule_des'=>$params['rule_des'],
            'type' => $params['type'],
            'show_coupon_name' => $params['show_coupon_name'],
        ];
        $prize_info = $params['prize_info'];
        if(!$saveData['name']){
            throw new \think\Exception(L_('活动名称不能为空！'));
        }
        if(!$saveData['start_time']||!$saveData['end_time']||$saveData['end_time']<$saveData['start_time']){
            throw new \think\Exception(L_('活动时间不对！'));
        }
        if($saveData['num']<0){
            throw new \think\Exception(L_('抽奖次数不能小于0！'));
        }
        if(!in_array($saveData['type'],[0,1])){
            throw new \think\Exception(L_('抽取次数类型不对！'));
        }
        if(!in_array($saveData['show_coupon_name'],[0,1])){
            throw new \think\Exception(L_('大转盘优惠券显示类型不对！'));
        }
        if(!in_array($saveData['user_type'],[0,1,2])){
            throw new \think\Exception(L_('抽奖用户类型不对！'));
        }
        if($saveData['user_type']==1&&$saveData['user_register_day']<0){
            throw new \think\Exception(L_('新用户注册天数不能为空！'));
        }
        if($saveData['user_type']==1&&$saveData['user_register_day']<0){
            throw new \think\Exception(L_('新用户注册天数不能为空！'));
        }
        if($saveData['user_type']==2&&(!is_array($saveData['business_type'])||$saveData['amount']<0)){
            throw new \think\Exception(L_('业务类型/消费金额有误！'));
        }
        if(!$prize_info||!is_array($prize_info)){
            throw new \think\Exception(L_('奖项设置有误！'));
        }
        $rate_num = 0;
        foreach ($prize_info as $key=>$item){
            $rate_num += $item['rate'];
            if(isset($item['id'])){
                unset($prize_info[$key]['id']);
            }
        }
        if($rate_num!=100){
            throw new \think\Exception(L_('中奖率之和要等于100！'));
        }
        $saveData['start_time'] = strtotime($saveData['start_time']);
        $saveData['end_time'] = strtotime($saveData['end_time']);
        $saveData['business_type'] = implode(',',$saveData['business_type']);
        Db::startTrans();
        try {
            if($id){
                $info = (new Roundabout())->where(['id'=>$id,'is_del'=>0])->findOrEmpty()->toArray();
                if(!$info){
                    throw new \think\Exception(L_('活动不存在/已删除！'));
                }
                if($info['start_time']<=time()){
                    unset($saveData['start_time']);
                    unset($saveData['end_time']);
                }
                $saveData['update_time'] = time();
                (new Roundabout())->where(['id'=>$id])->save($saveData);
                if($info['start_time']>time()){
                    (new RoundaboutDetail())->where(['a_id'=>$id])->delete();
                    foreach ($prize_info as $key=>$item){
                        $prize_info[$key]['a_id'] = $id;
                    }
                    (new RoundaboutDetail())->saveAll($prize_info);
                }
                
            }else{
                $saveData['add_time'] = time();
                $saveData['is_del'] = 0;
                $id = (new Roundabout())->insertGetId($saveData);
                foreach ($prize_info as $key=>$item){
                    $prize_info[$key]['a_id'] = $id;
                }
                (new RoundaboutDetail())->saveAll($prize_info);
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }

    /**
     * 活动详情
     * @param $params
     * @return mixed
     * @throws \think\Exception
     */
    public function detailActivity($params){
        $id = $params['id'];
        if(!$id){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        $info = (new Roundabout())->where(['id'=>$id,'is_del'=>0])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        $prize_info = (new RoundaboutDetail())->where(['a_id'=>$id])->select()->toArray();
        foreach ($prize_info as $key=>$item){
            $prize_info[$key]['type'] = (string)$item['type'];
            $prize_info[$key]['pic'] = $item['pic']?replace_file_domain($item['pic']):'';
        }
        $info['prize_can_edit'] = 1;
        if($info['start_time']<time()&&$info['end_time']>time()){
            $info['prize_can_edit'] = 0;
        }
        if($info['end_time']<time()){
            $info['prize_can_edit'] = 0;
        }
        $info['start_time'] = $info['start_time']?date('Y-m-d H:i:s',$info['start_time']):'';
        $info['end_time'] = $info['end_time']?date('Y-m-d H:i:s',$info['end_time']):'';
        $info['bg_pic'] = $info['bg_pic']?replace_file_domain($info['bg_pic']):'';
        $info['kefu_pic'] = $info['kefu_pic']?replace_file_domain($info['kefu_pic']):'';
        $info['bg_audio'] = $info['bg_audio']?replace_file_domain($info['bg_audio']):'';
        $info['win_audio'] = $info['win_audio']?replace_file_domain($info['win_audio']):'';
        $info['not_win_audio'] = $info['not_win_audio']?replace_file_domain($info['not_win_audio']):'';
        $info['business_type'] = $info['business_type']?explode(',',$info['business_type']):[];
        $info['prize_info'] = $prize_info;
        return $info;
    }

    /**
     * 中将列表
     * @param $params
     * @throws \think\Exception
     */
    public function getUserList($params){
        $id = $params['id'];
        $page = $params['page']??0;
        $pageSize = $params['pageSize']??10;
        $search_type = $params['search_type'];
        $start_time = $params['start_time'];
        $end_time = $params['end_time'];
        $keywords = $params['keywords'];
        if(!$id){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        $info = (new Roundabout())->where(['id'=>$id,'is_del'=>0])->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        $where[] = ['ru.a_id','=',$id];
        if($keywords){
            if($search_type==1){
                $where[] = ['u.nickname','like','%'.$keywords.'%'];
            }else{
                $where[] = ['u.phone','=',$keywords];
            }
        }
        if($start_time&&$end_time){
            $start_time = strtotime($start_time);
            $end_time = strtotime($end_time);
            if($start_time>$end_time){
                throw new \think\Exception(L_('开始时间不能大于结束时间！'));
            }
            $where[] = ['ru.add_time','between',[$start_time,$end_time]];
        }
        $field = 'ru.*,u.nickname,u.phone';
        $list = (new RoundaboutUser())->getList($where,$field,$page,$pageSize);
        if($page>0){
            $data = $list['data'];
        }else{
            $data = $list;
        }
        foreach ($data as $key=>$item){
            $data[$key]['add_time'] = $item['add_time']?date('Y-m-d H:i:s',$item['add_time']):'';
        }
        if($page>0){
            $list['data'] = $data;
        }else{
            $list = $data;
        }
        return $list;
    }
    
    /**
     * 导出中奖记录
     */
    public function exportUserList($params){
        $list = $this->getUserList($params);
        $title = '中奖记录';
        $xlsCell = [
            ['add_time', '中奖日期'],
            ['nickname', '昵称'],
            ['phone', '手机号'],
            ['name', '将品名称'],
            ['type_name', '奖品类型'],
        ];
        $fileName = date("Y-m-d", time()) . '-' . $params['rand_number'];
        $exportResult = export_excel($title, $xlsCell, $list, $fileName);
        return ['file_url' => $exportResult['url']];
    }

    /**
     * 平台优惠券列表
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponList($params){
        $keywords = $params['keywords'];
        $where[] = ['delete','=',0];
        $where[] = ['status','=',1];
        $where[] = ['is_partition','=',0];
        $where[] = ['end_time','>',time()];
        if($keywords){
            $where[] = ['name','like','%'.$keywords.'%'];
        }
        $list = (new SystemCoupon())->field('coupon_id,name')->where($where)->order('coupon_id desc')->select()->toArray();
        return $list;
    }

    /**
     * 商家优惠券列表
     */
    public function getMerCouponList($params){
        $keywords = $params['keywords'];
        $where[] = ['c.status','=',1];
        $where[] = ['c.is_partition','=',0];
        $where[] = ['c.end_time','>',time()];
        if($keywords){
            $where[] = ['c.name','like','%'.$keywords.'%'];
        }
        $list = (new CardNewCoupon())->alias('c')->field("c.coupon_id,CONCAT(c.name,'(',m.name,')') as name")
            ->join('merchant m','c.mer_id = m.mer_id and m.status = 1')
            ->where($where)->order('c.coupon_id desc')
            ->select()->toArray();
        return $list;
    }

    /**
     * 活动详情
     * @param $params
     * @return mixed
     * @throws \think\Exception
     */
    public function getActivity($params){
        $id = $params['id'];
        $uid = $params['uid'];
        if(!$id){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        $where[] = ['id','=',$id];
        $where[] = ['is_del','=',0];
        $info = (new Roundabout())->field('id,name,bg_pic,bg_audio,rule_des,status,start_time,end_time,num,kefu_pic,win_audio,not_win_audio,user_register_day,user_type,business_type,amount,show_coupon_name,type as select_type')->where($where)->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        if($info['status']!=1){
            throw new \think\Exception(L_('活动未开启！'));
        }
        $info['type'] = 0;
        if($info['start_time']>time()){
            $info['type'] = 1;
        }
        if($info['end_time']<time()){
            $info['type'] = 2;
        }
        $user_num = 0;
        if($uid){
            $user_use_where = [
                ['uid', '=', $uid],
                ['a_id', '=', $id],
            ];
            if(!empty($info['select_type']) && $info['select_type']==1){
                $user_use_where[] = ['add_time','between',[strtotime(date('Y-m-d')), strtotime(date('Y-m-d') . ' 23:59:59')]];
            }
            $user_num = (new RoundaboutUser())->where($user_use_where)->count();
        }
        $check_res = $this->checkUserAct($uid,$id,$info);
        
        $prize_info = (new RoundaboutDetail())->field('id,name,type,pic,num,total,coupon_id,amount')->where(['a_id'=>$id])->order('id asc')->select()->toArray();
        foreach ($prize_info as $key=>$item){
            $prize_info[$key]['pic'] = $item['pic']?replace_file_domain($item['pic']):'';
            $prize_info[$key]['coupon_name'] = '';
            $prize_info[$key]['coupon_price'] = 0;
            if($item['type']==2){
                $coupon = (new SystemCoupon())->field('name,discount')->where(['coupon_id'=>$item['coupon_id']])->findOrEmpty()->toArray();
                if($coupon){
                    $prize_info[$key]['coupon_name'] = $coupon['name'];
                    $prize_info[$key]['coupon_price'] = $coupon['discount'];
                }
            }elseif($item['type']==6){
                $coupon = (new CardNewCoupon())->field('name,discount')->where(['coupon_id'=>$item['coupon_id']])->findOrEmpty()->toArray();
                if($coupon){
                    $prize_info[$key]['coupon_name'] = $coupon['name'];
                    $prize_info[$key]['coupon_price'] = $coupon['discount'];
                }
            }
        }
        $info['start_time'] = $info['start_time']?date('Y-m-d H:i:s',$info['start_time']):'';
        $info['end_time'] = $info['end_time']?date('Y-m-d H:i:s',$info['end_time']):'';
        $info['bg_pic'] = $info['bg_pic']?replace_file_domain($info['bg_pic']):'';
        $info['bg_audio'] = $info['bg_audio']?replace_file_domain($info['bg_audio']):'';
        $info['kefu_pic'] = $info['kefu_pic']?replace_file_domain($info['kefu_pic']):'';
        $info['win_audio'] = $info['win_audio']?replace_file_domain($info['win_audio']):'';
        $info['not_win_audio'] = $info['not_win_audio']?replace_file_domain($info['not_win_audio']):'';
        $info['num'] = ($info['num']-$user_num)<0?0:($info['num']-$user_num);
        $info['prize_info'] = $prize_info;
        $info['can_use'] = $check_res['status'];
        $info['can_use_msg'] = $check_res['message'];
        return $info;
    }

    /**
     * 用户中奖记录列表
     * @param $params
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getActivityUser($params){
        $uid = $params['uid'];
        $id = $params['id'];
        if(!$id){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        $where[] = ['id','=',$id];
        $where[] = ['is_del','=',0];
        $info = (new Roundabout())->field('id,name,bg_pic,num,bg_audio,rule_des,status,start_time,end_time')->where($where)->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        if($info['status']!=1){
            throw new \think\Exception(L_('活动未开启！'));
        }
        $list = (new RoundaboutUser())->where([['uid','=',$uid],['a_id','=',$id],['type','<>',5]])->order('add_time asc')->select()->toArray();
        foreach ($list as $key=>$item){
            $list[$key]['add_time'] = $item['add_time']?date('Y-m-d',$item['add_time']):'';
        }
        return $list;
    }

    /**
     * 用户抽奖
     * @param $params
     * @throws \think\Exception
     */
    public function userDraw($params){
        $uid = $params['uid'];
        $id = $params['id'];
        if(!$id){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        $where[] = ['id','=',$id];
        $where[] = ['is_del','=',0];
        $info = (new Roundabout())->field('id,name,bg_pic,num,bg_audio,rule_des,status,start_time,end_time,user_register_day,user_type,business_type,amount,show_coupon_name,type as select_type')->where($where)->findOrEmpty()->toArray();
        if(!$info){
            throw new \think\Exception(L_('活动不存在/已删除！'));
        }
        if($info['status']!=1){
            throw new \think\Exception(L_('活动未开启！'));
        }
        if($info['start_time']>time()){
            throw new \think\Exception(L_('活动未开始！'));
        }
        if($info['end_time']<time()){
            throw new \think\Exception(L_('活动已结束！'));
        }
        $check_res = $this->checkUserAct($uid,$id,$info);
        if($check_res['status']>1){
            throw new \think\Exception(L_($check_res['message']));
        }
        
        $prize_info = (new RoundaboutDetail())->where(['a_id'=>$id])->order('id asc')->select()->toArray();
        $prize_arr = $prize_out_arr = []; //有商品/商品已抽完/总奖品数
        $out_rate = $rand_num = 0;
        foreach($prize_info as $key=>$item){
            if(($item['total']>0&&$item['total']<=$item['win_num'])||$item['rate']<=0){
                $prize_out_arr[] = $item;
                $out_rate += $item['rate'];
            }else{
                $prize_arr[] = $item;
            }
        }
        if(!$prize_arr){
            throw new \think\Exception(L_('奖品已抽完！'));
        }
        $avger_rate = 0;
        if(count($prize_out_arr)>0){
            $avger_rate = round(($out_rate/count($prize_out_arr)),2);
        }
        foreach ($prize_arr as $key=>$item){
            $prize_arr[$key]['min_num'] = $rand_num;
            $rand_num += 10000*($item['rate']+$avger_rate)/100;
            $prize_arr[$key]['max_num'] = $rand_num;
        }
        $number = rand(1,$rand_num);
        $user_prize = [];
        foreach($prize_arr as $key=>$item){
            if($number>$item['min_num']&&$number<=$item['max_num']){
                $user_prize = $item;
                break;
            }
        }
        if(!$user_prize){
            throw new \think\Exception(L_('请重试！'));
        }
        if((new RoundaboutDetail())->where(['id'=>$user_prize['id'],'version_no'=>$user_prize['version_no']])->save(['win_num'=>($user_prize['win_num']+1),'version_no'=>($user_prize['version_no']+1)])){
            $ready_join = (new RoundaboutUser())->where(['a_id'=>$id,'uid'=>$uid])->findOrEmpty()->toArray();
            //修改活动参与人数
            if(!$ready_join){
                (new Roundabout())->where(['id'=>$id])->inc('join_people',1)->update();
            }
            $saveData = [
                'a_id' => $id,
                'uid' => $uid,
                'add_time' => time(),
                'type' => $user_prize['type'],
                'name' => '',
                'type_name' => '',
                'coupon_id' => 0,
                'coupon_num' => 0,
                'amount' => 0,
                'd_id' => $user_prize['id'],
            ];
            $coupon_link = '';
            //创建用户获得奖项信息
            switch ($user_prize['type']){
                case 1:
                    $saveData['name'] = $user_prize['name'];
                    $saveData['type_name'] = '实物';
                    break;
                case 2:
                    $res = invoke_cms_model('System_coupon/had_pull',['coupon_id'=>$user_prize['coupon_id'],'uid'=>$uid,'card_code'=>'','get_all'=>0,'is_system_send'=>0,'reduce_num'=>$user_prize['num']]);
                    fdump_api([$uid,$id,$user_prize,$saveData,$res],'roundabout/roundabout',1);
                    $system_coupon = (new SystemCoupon())->where(['coupon_id'=>$user_prize['coupon_id']])->findOrEmpty()->toArray();
                    $saveData['name'] = $system_coupon['name']??'平台优惠券';
                    $saveData['type_name'] = '平台优惠券';
                    $coupon_link = $this->getCouponLink($system_coupon);
                    break;
                case 3:
                    $res = (new UserService())->addMoney($uid,$user_prize['amount'],'大转盘抽奖活动获得红包');
                    fdump_api([$uid,$id,$user_prize,$saveData,$res],'roundabout/roundabout',1);
                    $saveData['name'] = $user_prize['amount'].'红包';
                    $saveData['type_name'] = '红包';
                    break;
                case 4:
                    $res = (new UserService())->addScore($uid,$user_prize['amount'],'大转盘抽奖活动获得积分');
                    fdump_api([$uid,$id,$user_prize,$saveData,$res],'roundabout/roundabout',1);
                    $saveData['name'] = $user_prize['amount'].'积分';
                    $saveData['type_name'] = '积分';
                    break;
                case 5:
                    $saveData['name'] = $saveData['type_name'] = '未中奖';
                    break;
                case 6:
                    $res = invoke_cms_model('Card_new_coupon/had_pull',['coupon_id'=>$user_prize['coupon_id'],'uid'=>$uid,'card_code'=>'','not_wxapp_hadpull'=>false,'is_auto'=>false,'get_all'=>0, 'muti_hadpull'=>false,'is_system_send'=>0,'is_assign'=>true,'reduce_num'=>$user_prize['num']]);
                    fdump_api([$uid,$id,$user_prize,$saveData,$res],'roundabout/roundabout',1);
                    $merchant_coupon = (new CardNewCoupon())->where(['coupon_id'=>$user_prize['coupon_id']])->findOrEmpty()->toArray();
                    $saveData['name'] = $merchant_coupon['name']??'商家优惠券';
                    $saveData['type_name'] = '商家优惠券';
                    $coupon_link = $this->getCouponLink($merchant_coupon,'merchant');
                    break;
            }
            $res = (new RoundaboutUser())->save($saveData);
            if(!$res){
                fdump_api([$uid,$id,$user_prize,$saveData,(new RoundaboutUser())->getLastSql()],'roundabout/roundaboutError',1);
            }
            return ['id'=>$user_prize['id'],'type'=>$user_prize['type'],'coupon_link'=>$coupon_link];
        }else{
            fdump_api([$uid,$id,$user_prize,(new RoundaboutDetail())->getLastSql()],'roundabout/roundaboutError',1);
            throw new \think\Exception(L_('目前抽奖人数较多，请重试！'));
        }
    }
    
    private function getCouponLink($coupon,$type='system'){
        if($coupon['coupon_url']){
            $link_url = $coupon['coupon_url'];
        }else{
            $house_village_base_url = cfg('system_type') == 'village'?'/packapp/village/':'/packapp/plat/';
            $house_village_home_url = cfg('system_type') == 'village'?'pages/village_menu/index':'pages/plat_menu/index';
            $site_url = cfg('site_url');
            if($type=='system'){
                if ($coupon['use_type'] == 1) {
                    $store_ids = (new SystemCouponStore())->field('store_id')->where(['coupon_id' => $coupon['coupon_id']])->select()->toArray();
                    if (count($store_ids) == 1) { //单店铺
                        $link_url = $site_url . $house_village_base_url . 'pages/store/homePage?store_id=' . $store_ids[0]['store_id'];
                    } else { //多店铺
                        $link_url = $site_url . $house_village_base_url . 'pages/store/storeList?coupon_id=' . $coupon['coupon_id'] . '&coupon_type=system';
                    }
                } else {
                    if ($coupon['cate_name'] == '社区拼团') { //跳转 社区团购首页
                        $link_url = $site_url . '/wap.php?g=Wap&c=Village_group&a=index';
                    } else {
                        $link_url = $site_url . $house_village_base_url . $house_village_home_url;
                    }
                }
            }else{
                $store = explode(',', $coupon['store_id']);
                if (count($store) == 1) {  //单店铺
                    $link_url = $site_url . $house_village_base_url . 'pages/store/homePage?store_id=' . $coupon['store_id'];
                } else { //多店铺
                    $link_url = $site_url . $house_village_base_url . 'pages/store/storeList?coupon_id=' . $coupon['coupon_id'];
                }
            }
        }
        return $link_url;
    }
    
    private function checkUserAct($uid,$id,$info){
        $user_use_where = [
            ['uid', '=', $uid],
            ['a_id', '=', $id],
        ];
        $use_type_name = '';
        if(!empty($info['select_type']) && $info['select_type']==1){
            $user_use_where[] = ['add_time','between',[strtotime(date('Y-m-d')), strtotime(date('Y-m-d') . ' 23:59:59')]];
            $use_type_name = '今天';
        }
        $user_num = (new RoundaboutUser())->where($user_use_where)->count();
        if($user_num>=$info['num']){
            return ['status'=>2,'message'=>L_('X1抽奖次数已用完！', ['X1' => $use_type_name])];
        }
        if($info['user_type']==1){
            $user = (new User())->where(['uid'=>$uid])->findOrEmpty()->toArray();
            if((time()-$user['add_time'])>$info['user_register_day']*3600*24){
                return ['status'=>3,'message'=>'需要新用户才能参与抽奖！'];
            }
        }elseif($info['user_type']==2){
            $business_type = explode(',',$info['business_type']);
            $business_money = 0;
            $business_message = '';
            $business_type_name = $this->businessTtype();
            foreach ($business_type as $item){
                if($item=='shop'){
                    $order_money = (new ShopOrder())->field('sum(price) as total_money')
                        ->where([['uid','=',$uid],['status','in','0,1,2,3,11'],['paid','=',1],['use_time','between',[$info['start_time'],$info['end_time']]]])->findOrEmpty()->toArray();
                }elseif ($item=='appoint'){
                    $order_money = (new AppointOrder())->field('sum(pay_money+product_real_balace_price+product_merchant_balance+product_card_give_money+product_balance_pay) as total_money')
                        ->where([['uid','=',$uid],['service_status','in','1,2'],['is_del','=',0],['paid','=',1],['verify_time','between',[$info['start_time'],$info['end_time']]]])->findOrEmpty()->toArray();
                }else{
                    $order_money = (new MallOrder())->field('sum(money_real) as total_money')
                        ->where([['uid','=',$uid],['status','not in','0,50,51,52,53,70'],['pay_time','>',0],['complete_time','between',[$info['start_time'],$info['end_time']]]])->findOrEmpty()->toArray();
                }
                $business_money += $order_money['total_money'];
                $business_message .= isset($business_type_name[$item])?($business_message?'、'.$business_type_name[$item]:$business_type_name[$item]):'';
            }
            if($business_money<$info['amount']){
                return ['status'=>4,'message'=>'需要在'.$business_message.'消费金额达到'.getFormatNumber($info['amount'],2).'元才能抽奖！'];
            }
        }
        return ['status'=>1,'message'=>'ok'];
    }
    
    private function businessTtype(){
        $data = [
            'shop' => cfg('shop_alias_name'),
            'mall' => cfg('mall_alias_name'),
            'appoint' => cfg('appoint_alias_name')
        ];
        return $data;
    }
}