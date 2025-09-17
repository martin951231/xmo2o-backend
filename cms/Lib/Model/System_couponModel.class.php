<?php

class System_couponModel extends Model{
    public function get_qrcode($id){
        $condition_store['coupon_id'] = $id;
        $qrcode_id = $this->field('`coupon_id`,`qrcode_id`')->where($condition_store)->find();
        if(empty($qrcode_id)){
            return false;
        }
        return $qrcode_id;
    }
    //保存优惠券二维码
    public function save_qrcode($id,$qrcode_id){
        $coupon_where['coupon_id'] = $id;
        $data_coupon['qrcode_id'] = $qrcode_id;
        if($this->where($coupon_where)->data($data_coupon)->save()){
            return(array('error_code'=>false));
        }else{
            return(array('error_code'=>true,'msg'=>L_('保存二维码至平台优惠券失败！请重试。')));
        }
    }

    public function get_coupon($coupon_id){
        $coupon = $this->field(true)->where(array('coupon_id'=>$coupon_id))->find();
        return $coupon;
    }

    //根据已领表id获取优惠券信息,不筛选时间等条件 链表查询
    public function get_coupon_info($coupon_id,$now_order=array()){
        include_once ('Membership_cardModel.class.php'); // 有的服务器无法直接加载Membership_cardModel类
        if($coupon_id >= Membership_cardModel::HADPULL_BEGIN_ID){
            $membership = (new Membership_cardModel())->getCurrentMembershipCard();
            $field = $coupon_id.' AS id,c.coupon_id,c.name,c.des,c.des_detial,c.had_pull,c.num,c.limit,c.use_limit,c.order_money,c.discount as price,c.discount,c.is_discount,c.discount_value,c.discount_type,c.is_share_with_discount';
            $res = M('System_coupon')
                ->join('as c left join '.C('DB_PREFIX').'system_coupon_hadpull AS h ON h.coupon_id=c.coupon_id')
                ->field($field)
                ->where(['c.coupon_id'=>$membership['coupon_id']])
                ->find();
        }else{
            $where['h.id']=$coupon_id;
            $where['h.is_use']= 0;
            $where['c.end_time'] = array('gt',time());
            $field = 'h.id,c.coupon_id,c.name,c.des,c.des_detial,c.had_pull,c.num,c.limit,c.use_limit,c.order_money,c.discount as price,c.discount,c.is_discount,c.discount_value,c.discount_type,c.is_share_with_discount';
            $res = M('System_coupon_hadpull')
                ->join('as h left join '.C('DB_PREFIX').'system_coupon c ON h.coupon_id=c.coupon_id')
                ->field($field)
                ->where($where)->find();
        }

        if($res['discount_value']==0){
            $res['discount_value'] = 10;
        }
        if($now_order){

            $res['discount_money']  = floatval($now_order['total_money']-floatval($res['discount_value']*$now_order['total_money']/10));
        }else{
            $res['discount_money'] = 0;
        }
        return $res;
    }

    public function getCouponDiscountMoney($order_info){
        if ($order_info['coupon_id'] > 0 && $order_info['coupon_price'] > 0) {
            $use_system_coupon = $this->get_coupon_hadpull(['s.uid' => $order_info['uid'], 's.id' => $order_info['coupon_id']]);
            if ($use_system_coupon) {
                if ($use_system_coupon['plat_money'] == 0 && $use_system_coupon['merchant_money'] == 0) {
                    $use_system_coupon['plat_money'] = 1;
                }
            }
            $system_coupon_plat_money = getFormatNumber($order_info['coupon_price'] * ($use_system_coupon['plat_money'] / ($use_system_coupon['plat_money'] + $use_system_coupon['merchant_money'])));
            $system_coupon_merchant_money = getFormatNumber($order_info['coupon_price'] - $system_coupon_plat_money);
        }
        return ['plat_money' => $system_coupon_plat_money ?? 0,'merchant_money' => $system_coupon_merchant_money ?? 0];
    }

    //获取该手机号码领取的优惠券数量
    public function get_coupon_count_by_phone($coupon_id,$phone){
        $where['coupon_id'] = $coupon_id;
        $where['phone'] = $phone;
        return M('System_coupon_hadpull')->where($where)->count();

    }

    //获取该手机号码领取的优惠券数量
    public function get_coupon_category_by_phone($uid){
        $where['uid'] = $uid;
        $coupon_list = M('System_coupon_hadpull')->where($where)->group('coupon_id')->select();
		fdump(M('System_coupon_hadpull')->getLastSql(), 'systemCoupon_user', true);
		return $coupon_list;
    }

    public function get_coupon_by_id($id){
        if ($id >= Membership_cardModel::HADPULL_BEGIN_ID) {
            $membership = (new Membership_cardModel())->getCurrentMembershipCard();
            $field = $id . ' AS id,c.coupon_id,h.uid,h.phone,c.end_time,h.is_use ,c.discount as price,c.discount,c.is_discount,c.discount_value,c.order_money,c.discount_type';
            $res = M('System_coupon')
                ->join('as c left join ' . C('DB_PREFIX') . 'system_coupon_hadpull h ON h.coupon_id=c.coupon_id')
                ->field($field)
                ->where(['c.coupon_id' => $membership['coupon_id']])
                ->find();
        }else{
            $where['c.end_time'] = array('gt',time());
            $where['c.status'] = array('neq','4');
            $where['h.id']=$id;
            $field = 'h.id,c.coupon_id,h.uid,h.phone,c.end_time,h.is_use ,c.discount as price,c.discount,c.is_discount,c.discount_value,c.order_money,c.discount_type';
            $res = M('System_coupon_hadpull')
                ->join('as h left join '.C('DB_PREFIX').'system_coupon c ON h.coupon_id=c.coupon_id')
                ->field($field)
                ->where($where)
                ->find();
        }

        return $res;
    }

    //获取适用不同分类的优惠券 接口
    public function get_coupon_list_by_type($type,$cat_id,$limit=6,$is_new=-1,$allow_gift=false){
        if(!empty($type)){
            $where['cate_name'] = array(array('eq',$type),array('eq','all'), 'or');
        }
        if($is_new!=-1){
            $where['allow_new'] = $is_new;
        }
        $where['end_time'] = array('gt',time());
        $where['status'] = 1;
        $where['is_hide'] = 0;
        $where['start_time'] = array('lt',time());

        //是否只允许在积分商城中兑换获得
        if ($allow_gift) {
            $where['allow_gift'] = 1;
        }else{
            $where['allow_gift'] = 0;
        }
        //去除仅限签到领取的平台优惠券
        $where['allow_sign']=0;

        $res = $this->where($where)->order('allow_new DESC,discount DESC')->limit($limit)->getField('coupon_id,name,img,had_pull,num,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,limit,is_discount,discount_value');
		
        foreach($res as $key=>&$v){
            $v['cate_id'] = unserialize($v['cate_id']);
            if($cat_id!=0){
                if($v['cate_id']['cat_id']!=$cat_id) {
                    unset($res[$key]);
                    continue;
                }
            }
			$v['img'] = replace_file_domain($v['img']);
            $v['url'] = C('config.config_site_url').'/coupon/'.$v['coupon_id'].'.html';
        }
        return $res;
    }

    //获取可以领取的优惠券种类
    public function get_coupon_list($where =array(),$allow_gift=false,$allow_sign=false){
        $where['end_time'] = array('gt',time());
        if(!isset($where['status'])){
            $where['status'] =  1;
        }
        //隐藏券不显示
        $where['is_partition'] = 0;
        // $where['_string'] && krsort($where);//将_string 排在后面

        if ($where['_string']) {
            $where['_string'] .= ' AND ((get_end_time>='.time().' AND get_start_time<='.time().') OR (get_start_time=0 AND get_end_time=0))';
            krsort($where);
        }else{
            $where['_string'] = ' ((get_end_time>='.time().' AND get_start_time<='.time().') OR (get_start_time=0 AND get_end_time=0))';
            krsort($where);
        }

        //是否只允许在积分商城中兑换获得
        if ($allow_gift) {
            $where['allow_gift'] = 1;
        }else{
            $where['allow_gift'] = 0;
        }

        //是否只允许在签到中领取
        if($allow_sign){
            $where['allow_sign']=1;
        }else{
            $where['allow_sign']=0;
        }
		
        //$where['start_time'] = array('lt',time());
        // $order_str = 'coupon_id DESC,status ASC,allow_new DESC,discount DESC';
		$order_str = 'coupon_id DESC';
        $res = M(_view('System_coupon'))->where($where)->order($order_str)->select();

        $info = array();
        foreach ($res as $val) {
            $info[$val['coupon_id']] = $val;
        }
		
        return $info;
    }

    public function weixin_send($money,$uid){
        //if($money>=C('config.weixin_send_money')){
        $coupon_list = explode(',',C('config.weixin_send_coupon_list'));
        if(empty($coupon_list)){
            return  array('error'=>1,'msg'=>L_('没有微信派发的优惠券'));
        }
        foreach ($coupon_list as $item) {
            $this->had_pull($item,$uid);
        }
        //}

    }


    //is_num 要求领取数量限制
    public function send_coupon_by_id($coupon_id,$uid,$is_num=false,$href='',$order_id=0,$gift_order_id=0){
        $where['coupon_id']=$coupon_id;
        $coupon = $this->get_coupon($coupon_id);
        if($coupon['rand_send_num']>0 && $coupon['had_pull']>=$coupon['rand_send_num'] && !$is_num){
            return array('error'=>1,'msg'=>L_('随机已经派发完了'));
        }
        //判断优惠券是否新用户领取
        if ($coupon['allow_new'] && !D('User')->check_new($uid, 'shop')) {
            return array('error' => 1, 'msg' => L_('该优惠券只允许新用户领取'));
        }

        if(($coupon['num']<=$coupon['had_pull']||$coupon['status']==3) && !$is_num){
            $this->field(true)->where($where)->setField('status',3);
            return array('error'=>3,'msg'=>L_('优惠券已领完'));
        }else{
            $hadpull = M('System_coupon_hadpull');
            if ($this->where($where)->setInc('had_pull')) {
                $this->where($where)->setField('last_time',$_SERVER['REQUEST_TIME']);
                $data['coupon_id'] = $coupon_id;
                $data['num'] = 1;
                $data['receive_time'] =$_SERVER['REQUEST_TIME'];
                $data['status'] = 0;
                $data['uid']  = $uid;
                $order_id && $data['share_get']  = $order_id;
                $gift_order_id && $data['gift_get']  = $gift_order_id;
                $coupon = $this->get_coupon($coupon_id);
                if ($hadpull->add($data)) {
                    if($now_user = M('User')->where(array('uid'=>$uid))->find()){
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $cate_platform = $this->cate_platform();
                        $href = empty($href)?C('config.site_url').'/wap.php?c=My&a=card_list&coupon_type=system':$href;
                        $model->sendTempMsg('TM00251', array('href' =>$href , 'wecha_id' => $now_user['openid'], 'first' => L_("您成功领取了X1优惠券",array("X1" => $cate_platform['category'][$coupon['cate_name']])) , 'toName' => $now_user['nickname'], 'gift' => $coupon['name'],'time'=>date("Y年m月d日 H:i"), 'remark' => L_('有效期').date("Y-m-d",$coupon['start_time']).' 至 '.date("Y-m-d",$coupon['end_time'])));
                    }
                    return array('error'=>0,'msg'=>L_("领取优惠券成功，优惠券【X1】",array("X1" => $coupon['name']))); 
                }
            } else {
                return array('error'=>1,'msg'=>L_('领取失败'));
            }

        }
    }

    public function  get_coupon_list_by_ids($ids){
        $where['coupon_id']=array('in',$ids);
        $res = $this->where($where)->getField('coupon_id,name,img,had_pull,num,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,is_discount,discount_value,limit,platform');

        return $res;
    }

    //根据类型获取系统商家券

    public function get_sys_mer_coupon_by_type($type,$other='',$platform='',$store_id=0){


        $mer_where =$sys_where= array('status'=>array('gt',0),'end_time'=>array('gt',time()),'send_type'=>array('neq',1));
        $mer_where['_string'] = $sys_where['_string']="(cate_name='{$type}' OR cate_name='all')";
        
        //平台优惠券领取时间限制
        $sys_where['_string'] .= ' AND ((get_end_time>='.time().' AND get_start_time<='.time().') OR (get_start_time=0 AND get_end_time=0))';
      
        if($other['mer_condition']){
            $mer_where = array($mer_where,$other['mer_condition']);
        }
        //除去只能签到领取的优惠券
        $sys_where['allow_sign']=0;
		$sys_where['allow_gift']=0;

        $mer_coupont_list =  M('Card_new_coupon')->where($mer_where)
            ->order('status ASC,allow_new DESC,discount DESC')
            ->field('coupon_id,name,img,platform,had_pull,num,limit,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,store_id ,is_discount,discount_value,0 as is_system')->select();

        // 仅手动派发的不显示
        $sys_where['is_hide'] = 0;
        $sys_coupont_list =  $this->where($sys_where)
            ->order('status ASC,allow_new DESC,discount DESC')
            ->field('coupon_id,name,img,platform,had_pull,num,limit,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,is_discount,discount_value,1 as is_system')->select();


        $coupon_list = array_merge($mer_coupont_list,$sys_coupont_list);
        $coupon_list =  sortArrayAsc($coupon_list,'start_time');
        $coupon_list =  array_reverse($coupon_list);

        $cate_platform = $this->cate_platform();

        foreach($coupon_list as $key=>&$v){
            $flag = false;
            $v['platform']= unserialize($v['platform']);
            $v['cate_id'] = empty($v['cate_id'])?'0':unserialize($v['cate_id']);
            $store_arr = explode(',',$v['store_id']);
            if($v['store_id']!='' && !in_array($store_id,$store_arr)){
                unset($coupon_list[$key]);
                continue;
            }
            foreach($v['platform'] as $vp){
                if($vp==$platform) {
                    $flag = true;
                }
            }
            if(!$flag){
                unset($coupon_list[$key]);
                continue;
            }

            foreach($v['platform'] as &$vv){
                $vv=$cate_platform['platform'][$vv];
            }
            
                $v['cate_name'] = $cate_platform['category'][$v['cate_name']];

            $v['platform']=trim(implode(',',$v['platform']),',');
        }

        return $coupon_list;
    }


    public function get_user_coupon_list($uid,$phone='',$is_use='',$type='' ){
        $where['_string'] = '(c.status <> 0 AND c.status<>4)';   //状态正常
        $where['h.uid'] = $uid;
        $type && $where['c.cate_name'] = $type;
        if(!empty($is_use)){
            if($is_use==1){
                $where['h.is_use'] = 0;
                $where['c.end_time'] = array('gt',time());
            }
        }
        $n = 1;
        $cate_platform = $this->cate_platform();
        $res = M(_view('System_coupon'))
            ->join('as c right join '.C('DB_PREFIX').'system_coupon_hadpull h ON h.coupon_id=c.coupon_id')
            ->field('h.id,h.phone,h.receive_time,h.is_use,c.*')->order('h.is_use ASC ,c.add_time DESC')->where($where)->select();

        foreach($res as $k => &$v){
            //合并商家+平台优惠券
            $v['coupon_type'] = 'system'; //平台券 标识
            $v['coupon_type_name'] = '平台券'; //标识名称

            $v['type'] = $v['cate_name'];
            if(empty($v['uid'])){
                M('System_coupon_hadpull')->where(array('id'=>$v['id']))->setField('uid',$uid);
            }
            if($v['is_use']==1&&$n==1){
                $v['line']=1;
                $n++;
            }
            $v['platform_s'] = $v['platform'];
            $v['platform']=unserialize($v['platform']);
            if(!empty($v['cate_id'])) {
                $v['cate_id'] = unserialize($v['cate_id']);
            }

            foreach($v['platform'] as &$vv){
                $vv=$cate_platform['platform'][$vv];
            }
            $v['cate_name_s'] = $v['cate_name'];
            if($v['cate_name']!='all') {
                $v['cate_name'] = $cate_platform['category'][$v['cate_name']];
            }
            $v['platform']=trim(implode(',',$v['platform']),',');
            if($v['end_time']<$_SERVER['REQUEST_TIME']&&$v['is_use']!=1){
                $v['is_use'] = 2;
            }

            if ($v['valid_time']) {
                if ($v['receive_time']<$v['start_time']) {
                    $v['end_time'] = min($v['end_time'],$v['start_time'] + $v['valid_time']*86400);
                }else{
                    $v['start_time'] = $v['receive_time'];
                    $v['end_time'] = min($v['end_time'],$v['receive_time'] + $v['valid_time']*86400);
                }
            }
        }
        return $res;
    }

    //优惠券改版
    public function get_user_coupon_list_where($uid, $phone='', $param=[], $is_use='',$type='' ){
        //$where['c.end_time'] = array('gt',time());
        switch ($param['status']) {
            case 1:
                $where['h.is_use'] = 0;   //未使用                
                $where['c.end_time'] = array('gt', time());
                $sort = 'c.end_time ASC,c.coupon_id ASC';
                break;
            case 2:
                $where['h.is_use'] = 1;   //已使用
                $sort = 'h.use_time DESC,c.coupon_id ASC';
                break;
            case 3:
                // $where['c.status|h.is_use'] = 2;   //不能使用
                //$where['_string'] = '(c.status = 2 OR h.is_use=2 OR (c.is_partition=1 AND c.end_time<'.time().'))';   //不能使用

                $where['h.is_use'] = ['neq', 1];
                $sort = 'c.end_time DESC,c.coupon_id ASC';
                break;
            default:
                $where['h.is_use'] = 0;   //未使用
                $where['c.end_time'] = array('gt', time());
                $sort = 'c.end_time ASC,c.coupon_id ASC';
        }
        if (!empty($param['cate_name'])) {
            $where['c.cate_name'] = $param['cate_name'];
        }

        if($param['status'] == 1){
            if($where['_string']){
                $where['_string'] .= ' AND (c.status <> 0 AND c.status<>4)';   //状态正常
            }else{
                $where['_string'] = '(c.status <> 0 AND c.status<>4)';   //状态正常
            }
        }
     
        $where['h.uid'] = $uid;
        $type && $where['c.cate_name'] = $type;
        $n = 1;
        $tm = time();
        $cate_platform = $this->cate_platform();
        $res = M(_view('System_coupon'))
            ->join('as c right join '.C('DB_PREFIX').'system_coupon_hadpull h ON h.coupon_id=c.coupon_id')
            ->field('h.id,h.phone,h.receive_time,h.is_use,h.membership_record_id,c.*')->order($sort)->where($where)->select();

        foreach($res as $k => &$v){
            //合并商家+平台优惠券
            $v['coupon_type'] = 'system'; //平台券 标识
            $v['coupon_type_name'] = '平台券'; //标识名称

            $v['type'] = $v['cate_name'];
            if(empty($v['uid'])){
                M('System_coupon_hadpull')->where(array('id'=>$v['id']))->setField('uid',$uid);
            }
            if($v['is_use']==1&&$n==1){
                $v['line']=1;
                $n++;
            }
            $v['platform_s'] = $v['platform'];
            $v['platform']=unserialize($v['platform']);
            if(!empty($v['cate_id'])) {
                $v['cate_id'] = unserialize($v['cate_id']);
            }

            foreach($v['platform'] as &$vv){
                $vv=$cate_platform['platform'][$vv];
            }
            $v['cate_name_s'] = $v['cate_name'];
//            if($v['cate_name']!='all') {
//                $v['cate_name'] = $cate_platform['category'][$v['cate_name']];
//            }
            $v['platform']=trim(implode(',',$v['platform']),',');
            if($v['end_time']<$_SERVER['REQUEST_TIME']&&$v['is_use']!=1){
                $v['is_use'] = 2;
            }

            if ($v['valid_time']) {
                if ($v['receive_time']<$v['start_time']) {
                    $v['end_time'] = min($v['end_time'],$v['start_time'] + $v['valid_time']*86400);
                }else{
                    $v['end_time'] = min($v['end_time'],$v['receive_time'] + $v['valid_time']*86400);
                }
            }
            //去掉过期的优惠券
            if($param['status'] == 1 && $v['end_time'] < $tm){
                unset($res[$k]);
            }
            if($param['status'] == 3 && $v['end_time'] > $tm){
                unset($res[$k]);
            }
        }
        return array_values($res);
    }

    //转赠券
    public function get_user_card_new_coupon($udata = [], $param = []){
        $time    = time();
        $where   = [];
        $where[] = ['a.uid' => $udata['uid']];
        switch ($param['status']) {
            case 1: //未使用
                $where[] = ['a.is_use' => 0];
                $where[] = ['a.end_time' => ['GT',$time]];
                break;
            case 2: //已使用
                $where[] = ['a.is_use' => 1];
                break;
            case 3: //已过期
                $where[] = ['_string' => 'a.is_use = 2 or (a.is_use = 0 and a.end_time > 0 and a.end_time <= '.$time.')'];
                break;
            case 4:  //查询我领取的转赠券
                $where[] = ['a.is_use != 3'];
                if(isset($param['mer_id'])&&$param['mer_id']){
                    $where[] = ['a.mer_id' => $param['mer_id']];
                }
                break;
            case 5:  //查询我转增成功的转赠券
                unset($where[0]);
                $where[] = ['a.pass_id'=>['in', $param['pass_id']]];
                if(isset($param['mer_id'])&&$param['mer_id']){
                    $where[] = ['a.mer_id' => $param['mer_id']];
                }
                $where = array_values($where);
                break;
            default:
                $where[] = ['a.is_use' => 0];
                $where[] = ['a.end_time' => ['GT',$time]];
                break;
        }
        if($param['status']==4){
            $sort = 'a.is_use ASC,a.receive_time DESC,a.end_time DESC';
        }elseif($param['status']==5){
            $sort = 'a.receive_time DESC,a.end_time DESC';
        }else{
            $sort = 'a.receive_time ASC';
        }
        $res  = M('card_new_coupon_give')
            ->join('as a left join '.C('DB_PREFIX').'merchant b ON a.mer_id = b.mer_id')
            ->join(' inner join '.C('DB_PREFIX').'user c ON a.uid = c.uid')
            ->where($where)
            ->order($sort)
            ->field('a.*,b.name as mer_name,b.pic_info,c.nickname,'.$param['status'].' as status')
            ->select();
        $card_arr = [];
        $type = isset($param['type'])?$param['type']:0; //1-我的领取，2-我的转赠
        if ($res) {
            foreach ($res as $dk => $dv) {
                $card_arr[$dk]['use_end_time'] = $dv['end_time'] > 0 ? date('Y.m.d', $dv['end_time']) : '永久';
                $card_arr[$dk]['use_start_time'] = date('Y.m.d', $dv['receive_time']);
                $card_arr[$dk]['discount'] = $dv['name'];
                $card_arr[$dk]['is_discount'] = 0;//配送费类型（无）
                $card_arr[$dk]['id'] = $dv['id'];
                $images = explode(";", explode(";", $dv['pic_info'])[0]) ?? [];
                $pic = $images[0] ? (new merchant_image())->get_image_by_path($images[0]) : '';
                $card_arr[$dk]['store_img'] = $pic;
                $card_arr[$dk]['plat_name'] = $dv['mer_name'];
                $card_arr[$dk]['img'] = replace_file_domain($dv['image']);
                $card_arr[$dk]['is_time_out'] = $dv['status'] == 1 ? 0 : 1;
                $card_arr[$dk]['is_used'] = $dv['is_use'];//是否已使用 0-正常，1-已使用，2-不能使用，3-赠送中
                $card_arr[$dk]['name'] = '团购商品券';
                $card_arr[$dk]['use_title'] = $dv['status'] == 3 ? '已过期' : ($dv['status'] == 2 ? '已使用' : '立即使用');
                $card_arr[$dk]['order_title'] = $dv['name'];
                $card_arr[$dk]['use_limit'] = '团购业务使用\\n使用说明：团购券需要指定店铺核销使用';
                $card_arr[$dk]['url'] = C('config.site_url') . '/wap.php?' . $dv['coupon_url'].'&type='.$type;
                $card_arr[$dk]['type'] = 2;//转赠券
                $card_arr[$dk]['user_name'] = $dv['nickname'];//转赠券
                $card_arr[$dk]['order_id'] = $dv['order_id'];//订单
                $card_arr[$dk]['coupon_time'] = $dv['receive_time'] > 0 ? date('Y.m.d H:i', $dv['receive_time']) : '永久';//领取时间
                if($type==1){
                    $card_arr[$dk]['pass_id'] = $dv['pass_id'];
                }
            }
            $card_arr = array_values($card_arr);
        }
        return $card_arr;
    }

    
    public function get_use_coupon_by_params($uid,$cate_name)
    {
        $now_time = time();
        $sql = "SELECT h.*, c.* FROM " . C('DB_PREFIX') . "system_coupon_hadpull AS h INNER JOIN " . C('DB_PREFIX') . "system_coupon AS c ON h.coupon_id = c.coupon_id WHERE h.uid='{$uid}' AND h.is_use=0 AND c.status<>0 AND c.end_time>'{$now_time}' AND (c.cate_name='all' OR c.cate_name='{$cate_name}')";
        $res = $this->query($sql);
        $coupon_list = array();
        foreach ($res as $row) {
            //查看使用期限是否到期
            // 用户可使用的天数
            if ($row['valid_time']) {
                if ($row['receive_time']<$row['start_time']) {
                    $end_time = $row['start_time'] + $row['valid_time']*86400;
                }else{
                    $end_time = $row['receive_time'] + $row['valid_time']*86400;
                }
                if ($end_time>time()) {
                    $coupon_list[] = array('coupon_id' => $row['id'], 'is_discount' => $row['is_discount'],'discount_value' => $row['discount_value'], 'full_money' => floatval($row['order_money']), 'reduce_money' => floatval($row['discount']), 'end_time' => date('Y-m-d', $row['end_time']));
                }
            }else{
                $coupon_list[] = array('coupon_id' => $row['id'], 'is_discount' => $row['is_discount'],'discount_value' => $row['discount_value'], 'full_money' => floatval($row['order_money']), 'reduce_money' => floatval($row['discount']), 'end_time' => date('Y-m-d', $row['end_time']));
            }
        }
        return $coupon_list;
    }

    public function get_noworder_coupon_list($now_order,$order_type,$phone,$uid,$platform,$business_type='',$extra='',$is_limit_platform=true, $isOpenMembershipCard = false){
        if($business_type){
            $order_type = $business_type;
        }
        if($order_type == 'group'){
            $table = 'group';
        }else if($order_type == 'meal' || $order_type == 'food' || $order_type == 'foodPad' || $order_type == 'takeout'||$order_type=='foodshop'){
            $table = 'meal';
        }else if($order_type == 'appoint'){
            $table = 'appoint';
        }else if($order_type == 'shop' ){
            $table = 'shop';
        }else if($order_type == 'mall'){
            $table = 'mall';
        }else if($order_type == 'balance-appoint'){
            $table = 'appoint';
        }else if($order_type == 'store'){
            $table = 'store';
        }else if($order_type == 'village_group'){
            $table = 'village_group';
        }else{
            return array();
        }
        //$where['order_money'] = array('ELT',$now_order['total_money']);这里需求有修改，由订单总额修改成商品总额,即订单总额-运费;
		$where['order_money'] = array('ELT',floatval($now_order['total_money'])-floatval($now_order['freight_charge']));
        //$order_cate = D(ucfirst($table).'_order')->get_order_cate($now_order['order_id']);
        if($order_type!='store' && $order_type!='shop' && $order_type!='mall' && $order_type!='village_group'){
            $order_cate = PD(ucfirst($table).'_order')->get_order_cate($now_order['order_id']);
        }else if($order_type=='mall'){
            $order_cate =array('mall');
        }else if($order_type=='shop'){
            if(empty($now_order['order_id'])){
                $order_cate = PD(ucfirst($table).'_order')->get_order_cate_more(['store_id'=>$now_order['store_id']],true);
            }else{
                $order_cate = PD(ucfirst($table).'_order')->get_order_cate_more($now_order['order_id']);
            }
            foreach($order_cate as $s){
                $cate_arr[] = $s['cat_id'];
            }
        }else{
            $order_cate =array('store');
        }
		
        $where['c.end_time'] = array('gt',time());
        $where['c.start_time'] = array('lt',time());
        $where['c.status'] = array('in','1,3');  //状态正常
        $where['h.is_use'] = array('neq',1);  //状态正常
        if($extra){
            $key = array_keys($extra);
            $where['c.'.$key[0]] = $extra[$key[0]];
        }
        $where['uid'] = $uid;
        $where['_string'] = "(c.cate_name='".$table."') OR (c.cate_name ='all')";
		
		if($now_order['store_id']){
			$store_city_id = M('Merchant_store')->where(['store_id'=>$now_order['store_id']])->getField('city_id');
			$where['c.city_id'] = array(array('eq',$store_city_id),array('eq',0), 'or');
		}else{
			$user_city_id = PD('User')->where(['uid'=>$uid])->getField('city_id');
			$where['c.city_id'] = array(array('eq',$user_city_id),array('eq',0), 'or');
		}
		//外卖类型的优惠券
        if($order_type=='shop'){
            //echo 55;exit;
            if($now_order['is_pick_in_store']==2){//自提
                $where['c.deliver_type'] = array('neq',2);
            }else{//配送
                $where['c.deliver_type'] = array('neq',1);
                if(getFormatNumber($now_order['freight_charge']) <= 0){
                    $where['c.discount_type'] = array('neq',1);
                }
            }
            //dump($where);exit;
        }
        //echo 1;exit;
        $res = M(_view('System_coupon'))
            ->join('as c right join '.C('DB_PREFIX').'system_coupon_hadpull h ON h.coupon_id=c.coupon_id')
            ->field('h.id,h.receive_time,c.*')
            ->where($where)
            ->select();
        fdump(M(_view('System_coupon')));
        $cate_platform = $this->cate_platform();
        foreach($res as $key=>&$v){
            $v['cate_name_s'] = $v['cate_name'];

            //查看使用期限是否到期
            // 用户可使用的天数
            if ($v['valid_time']) {
                if ($v['receive_time']<$v['start_time']) {
                    $end_time = $v['start_time'] + $v['valid_time']*86400;
                }else{
                    $v['start_time'] = $v['receive_time'];
                    $end_time = $v['receive_time'] + $v['valid_time']*86400;
                }
                if ($end_time<time() || $v['start_time'] > time()) {
                    unset($res[$key]);
                    continue;
                }
            }

            //判断是否是指定店铺的优惠券
            if($counpon_store = M('system_coupon_store')->field('store_id')->where(['coupon_id'=>$v['coupon_id']])->select()){
                $store_arr = array_column($counpon_store,'store_id');
                if(!in_array($now_order['store_id'], $store_arr)){
                    unset($res[$key]);
                }
            }

            $flag = false;
            $v['platform_s'] = $v['platform'];
            $v['platform']= unserialize($v['platform']);
            $v['cate_id'] = empty($v['cate_id'])?'0':unserialize($v['cate_id']);
            foreach($v['platform'] as $vp){
               if($vp==$platform || !$is_limit_platform) {
                    $flag = true;
                }
            }
            if(!$flag){
                unset($res[$key]);
                continue;
            }
            if(!empty($v['cate_id'])) {
                if($order_type=='shop'){
                    if(!in_array($v['cate_id']['cat_id'],$cate_arr)){
                        unset($res[$key]);
                        continue;
                    }
                }else{

                    $cate_arr1 = array_diff($v['cate_id'],$order_cate);
                    $cate_arr2 = array_diff($v['cate_id'],$order_cate);
                    if((!empty($cate_arr1) || !empty($cate_arr2) ) && $v['cate_id']['cat_id'] != $order_cate['cat_id']){
                        unset($res[$key]);
                        continue;
                    }
                }
            }

            foreach($v['platform'] as &$vv){
                $vv=$cate_platform['platform'][$vv];
            }
            if($v['cate_name']!='all') {
                $v['cate_name'] = $cate_platform['category'][$v['cate_name']];
            }
            $v['platform']=trim(implode(',',$v['platform']),',');
            if($v['end_time']<$_SERVER['REQUEST_TIME']&&$v['is_use']!=1){
                $v['is_use'] = 2;
            }
            if($v['is_discount']==1){
                if($v['discount_type']==1){//减运费
					$v['discount_money'] =round( $now_order['freight_charge'] * (100-$v['discount_value']*10)/100,2);
				}else{//减餐费
					$v['discount_money'] =round( $now_order['total_money'] * (100-$v['discount_value']*10)/100,2);
				}		
            }else{
                $v['discount_money'] = $v['discount'];
            }
        }
        if ($isOpenMembershipCard) {
            $coupon = (new Membership_cardModel())->getCouponInfo();
            $coupon && $res = array_merge($coupon, $res);
        }

        //dump(sortArrayAsc($res,'discount_money'));exit;
        return sortArrayAsc($res,'discount_money');
    }

    public function cate_platform(){
		$category = [];
		if(C('config.group_alias_name')){
			$category['group'] = C('config.group_alias_name');
		}
		if(C('config.meal_alias_name')){
			$category['meal'] = C('config.meal_alias_name');
		}
		if(C('config.appoint_alias_name')){
			$category['appoint'] = C('config.appoint_alias_name');
		}
		if(C('config.shop_alias_name')){
			$category['shop'] = C('config.shop_alias_name');
		}
		if(C('config.mall_alias_name_new')){
			$category['mall'] = C('config.mall_alias_name_new');
		}
		if(C('config.village_group_alias')){
			$category['village_group'] = C('config.village_group_alias');
		}
		if(C('config.pay_in_store')){
            $category['store'] = C('config.cash_alias_name');
            if(C('customization.internal_membership_card')){
                $category['cash'] = L_('店内收银');
            }
        }


        if(C('customization.life_tools')){// 体育课程券
            $category['course'] = '课程券';
            $category['stadium'] = '场馆券';
        }


		$category['deliver'] = '跑腿';//跑腿

		if(count($category) > 1){
			$category['all'] = L_('全品类通用');
		}
        $platform = array('wap'=>L_('移动网页'),'app'=>'App','weixin'=>L_('微信'),'wxapp'=>L_('小程序'));
        return array('category'=>$category,'platform'=>$platform);
    }

    //检查平台优惠券状态
    public function check_coupon($record_id, $mer_id, $uid,$refund = false, $order_type='')
    {
        if (!$order_type || $order_type!='village_group') {
            $now_merchant = M('Merchant')->field(true)->where(array('mer_id'=>$mer_id,'status'=>'1'))->find();
            if(empty($now_merchant)){
                return array('error_code' => 1, 'msg' => L_('商家暂时歇业'));
            }
        }
        $condition_coupon_record = [
			'id' => $record_id, 
			//'uid' => $uid,		//在订单支付后，不再检测券的账号身份，以支付完成动作为主。
		];
        if(empty($refund)){
            $condition_coupon_record['is_use'] = '0';
        }else{
            $condition_coupon_record['is_use'] = '1';
        }
        $now_coupon_record = M("System_coupon_hadpull")->field(true)->where($condition_coupon_record)->find();
        if (empty($now_coupon_record)) {
            return array('error_code' => 1, 'msg' => L_('优惠券不可用'));
        }
        $now = time();
        $now_coupon = $this->field(true)->where(array('coupon_id' => $now_coupon_record['coupon_id'],'start_time'=>array('lt', $now),'end_time'=>array('gt', $now)))->find();
        if (empty($now_coupon_record)) {
            if ($order_type && $order_type=='village_group') {
                $msg = L_('优惠券已被取消了');
            } else {
                $msg = L_('优惠券已被商家取消了');
            }
            return array('error_code' => 1, 'msg' => $msg);
        }else{
            //查看使用期限是否到期
            // 用户可使用的天数
            if ($now_coupon['valid_time']) {
                if ($now_coupon_record['receive_time']<$now_coupon['start_time']) {
                    $end_time = $now_coupon['start_time'] + $now_coupon['valid_time']*86400;
                }else{
                    $end_time = $now_coupon_record['receive_time'] + $now_coupon['valid_time']*86400;
                }
                if(ACTION_NAME == 'return_url'){
                    $end_time = $end_time + 60;//支付回调检测时使用期限增加一分钟
                }
                if ($end_time<=time()) {
                    $msg = L_('优惠券已到期了');
                    return array('error_code' => 1, 'msg' => $msg);
                }
            }

        }
        return array('error_code' => 0, 'msg' => L_('优惠券可用'),'coupon'=>$now_coupon);
    }

    //消费平台优惠券记录在表中 record_id 是 hadpull 表中的id
    public function user_coupon($record_id,$order_id,$order_type, $mer_id, $uid)
    {
        $result = $this->check_coupon($record_id, $mer_id, $uid,false, $order_type);
        if ($result['error_code']) {
            return $result;
        }
        $now = time();
        $result_ = M("System_coupon_hadpull")->where(array('id' => $record_id))->save(array('use_time' => $now, 'is_use' => '1'));
        if (empty($result_)) {
            return array('error_code' => 1, 'msg' => L_('优惠券使用失败'));
        }
        $now_coupon_record = M("System_coupon_hadpull")->field(true)->where(array('id' => $record_id))->find();

        if($result['coupon']['is_wx_card']){
            import('ORG.Net.Http');
            $mode = D('Access_token_expires');
            $res = $mode->get_access_token();
            $wx_date['code'] = $now_coupon_record['wx_card_code'];
            $return = httpRequest('https://api.weixin.qq.com/card/code/consume?access_token=' . $res['access_token'], 'post', json_encode($wx_date, JSON_UNESCAPED_UNICODE));
            $return = json_decode($return[1], true);

        }
        $arr = array();
        $arr['coupon_id']  	= $now_coupon_record['coupon_id'];
        $arr['order_type']	= $order_type;
        $arr['order_id']	= $order_id;
        $arr['hadpull_id']	= $record_id;
        $arr['uid']	= $uid;
        $arr['num']	= 1;
        $arr['use_time']	= $now;

        M('System_coupon_use_list')->add($arr);
        return array('error_code' => 0, 'msg' => L_('优惠券使用成功'));
    }
	
	//订单状态：已支付店员未发货，取消订单会返还给用户优惠券 tapd_story_1001389/2019-11-08
	public function return_coupon($record_id,$order_id,$order_type, $mer_id, $uid){
		$now = time();
        $result_ = M("System_coupon_hadpull")->where(array('id' => $record_id))->save(array('is_use' => '0'));
		$now_coupon_record = M("System_coupon_hadpull")->field(true)->where(array('id' => $record_id))->find();
		$arr = array();
        $arr['coupon_id']  	= $now_coupon_record['coupon_id'];
        $arr['order_type']	= $order_type;
        $arr['order_id']	= $order_id;
        $arr['hadpull_id']	= $record_id;
        $arr['uid']	= $uid;
        $arr['num']	= 1;
        $arr['use_time']	= $now;
        $arr['type']	= 1;
		
        M('System_coupon_use_list')->add($arr);
        return array('error_code' => 0, 'msg' => L_('优惠券返还成功'));
	}

    //领取方法
    //@param int $get_all 是否一次领取用户当前可领取的最大数量
    public function had_pull($coupon_id,$uid,$card_code = '',$get_all=0, $is_system_send = 0,$reduce_num=1){
        $where['coupon_id']=$coupon_id;
        $coupon = $this->get_coupon($coupon_id);

        if(empty($coupon) || $coupon['status']==4){
            return array('error_code'=>1,'coupon'=>$coupon);
        }else if($coupon['allow_new'] && !PD('User')->check_new($uid,$coupon['cate_name'])){
            return array('error_code'=>4,'coupon'=>$coupon);
        }else if($coupon['end_time']<$_SERVER['REQUEST_TIME'] ){
            $this->where($where)->setField('status',2);
            return array('error_code'=>2,'coupon'=>$coupon);
        }else if($coupon['status']==0){
            return array('error_code'=>1,'coupon'=>$coupon);
        }else if($coupon['status']==2){
            return array('error_code'=>2,'coupon'=>$coupon);
        }else if($coupon['status']==4){
            return array('error_code'=>2,'coupon'=>$coupon);
        }else if($coupon['num']<=$coupon['had_pull']||$coupon['status']==3){
            $this->field(true)->where($where)->setField('status',3);
            return array('error_code'=>3,'coupon'=>$coupon);
        } else{
//			//判断用户城市
//			if($coupon['city_id']){
//				$now_user = PD('User')->get_user($uid);
//				if($now_user['city_id'] && $now_user['city_id'] != $coupon['city_id']){
//					return array('error_code'=>6,'coupon'=>$coupon);
//				}
//			}

            $hadpull = M('System_coupon_hadpull');
            $hadpull_count = $hadpull->where(array('uid'=>$uid,'coupon_id'=>$coupon_id))->count();
            if($hadpull_count<$coupon['limit']) {
                if($get_all==1){
                    //实际可领取的数量
                    $receive_num=$coupon['limit']-$hadpull_count;
                    //剩余的数量
                    $after_num=$coupon['num']-$coupon['had_pull'];
                    $reduce_num=min($receive_num,$after_num);
                }
                $where['_string'] = 'had_pull+' . $reduce_num . ' <= num';
                if ($this->where($where)->setInc('had_pull',$reduce_num)) {
                    $coupon = $this->get_coupon($coupon_id);
                    if($coupon['num']<=$coupon['had_pull']) {
                        $this->field(true)->where($where)->setField('status', 3);
                    }
                    $this->where($where)->setField('last_time',$_SERVER['REQUEST_TIME']);
                    $data_all=array();
                    for($i=0;$i<$reduce_num;$i++){
                        $data=array();
                        $data['coupon_id'] = $coupon_id;
                        $data['num'] = 1;
                        $data['receive_time'] =$_SERVER['REQUEST_TIME'];
                        $data['status'] = 0;
                        if($card_code){
                            $data['wx_card_code']  = $card_code;
                        }
                        $data['uid']  = $uid;
                        $data_all[]=$data;
                    }                    
                    $coupon = $this->get_coupon($coupon_id);
                    if ($hadpull_id = $hadpull->addAll($data_all)) {
                        if($now_user = M('User')->where(array('uid'=>$uid))->find()){
                            //微信小程序订阅消息提醒
                            $wxapp_message_send = false;
                            if($now_user['wxapp_openid'] && $is_system_send){
                                $param['page'] = 'pages/coupon/myCoupon';
                                $param['wxapp_openid'] = $now_user['wxapp_openid'];
                                $param['type'] = 'send_coupon';

                                $data_info['name'] = $coupon['name'];
                                $data_info['type'] = $coupon['is_discount'] ? L_('折扣券') : L_('代金券');
                                $data_info['get_time'] = time();
                                $data_info['end_time'] = $coupon['end_time'];
                                $data_info['note'] = L_('平台手动派发券给您，请及时关注~');
                                $data_info['time'] = time();
                                $param['data_info'] = $data_info;
                                $wxapp_message_send = D('Wxapp_template')->sendWxappMessage($param);
                            }
                            if(!$wxapp_message_send){
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $cate_platform = $this->cate_platform();
                                $model->sendTempMsg('TM00251', array('href' => C('config.site_url').'/wap.php?c=My&a=card_list&coupon_type=system', 'wecha_id' => $now_user['openid'], 'first' => L_("您成功领取了X1优惠券",array("X1" => $cate_platform['category'][$coupon['cate_name']])) , 'toName' => $now_user['nickname'], 'gift' => $coupon['name'],'time'=>date("Y年m月d日 H:i"), 'remark' => L_('有效期x1 至 x2',array('x1'=>date("Y-m-d",$coupon['start_time']),'x2'=>date("Y-m-d",$coupon['end_time'])))));
                            }
                        }
                        $coupon['has_get'] = $hadpull_count+$reduce_num;
                        $coupon['reduce_num']=$reduce_num;
                        return array('error_code'=>0,'coupon'=>$coupon,'hadpull_id'=>$hadpull_id);
                    }
                } else {
                    return array('error_code'=>1,'coupon'=>$coupon);
                }
            }else{
                return array('error_code'=>5,'coupon'=>$coupon);
            }
        }
    }

    //获取该用户领取的优惠券数量
    public function get_coupon_count_by_uid($coupon_id,$uid){
        $where['coupon_id'] = $coupon_id;
        $where['uid'] = $uid;
        return M('System_coupon_hadpull')->where($where)->count();
    }

    //减少卡券库存
    public function decrease_sku($add,$less,$coupon_id){
        $now_coupon  =$this->where(array('coupon_id'=>$coupon_id))->find();
        if(!$now_coupon['is_wx_card']){
            return ;
        }
        import('ORG.Net.Http');
        $res = D('Access_token_expires')->get_access_token();
        //修改库存
        $wx_data['card_id'] = $now_coupon['wx_cardid'];
        $wx_data['increase_stock_value'] = $add;
        $wx_data['reduce_stock_value'] = $less;
        $update_wx_card = httpRequest('https://api.weixin.qq.com/card/modifystock?access_token='.$res['access_token'],'post',json_encode($wx_data,JSON_UNESCAPED_UNICODE));
        $update_wx_card = json_decode($update_wx_card[1],true);
        $errorms = $update_wx_card['errmsg'];
        return $errorms;
    }

    //卡券颜色组合
    public function color_list(){
        return array(
            "Color010"=>"#63b359",
            "Color020"=>"#2c9f67",
            "Color030"=>"#509fc9",
            "Color040"=>"#5885cf",
            "Color050"=>"#9062c0",
            "Color060"=>"#d09a45",
            "Color070"=>"#e4b138",
            "Color080"=>"#ee903c",
            "Color081"=>"#f08500",
            "Color082"=>"#a9d92d",
            "Color090"=>"#dd6549",
            "Color100"=>"#cc463d",
            "Color101"=>"#cf3e36",
            "Color102"=>"#5E6671",
        );
    }

    //获取已领取的coupon的信息
    public  function get_coupon_hadpull($where){
        return M('System_coupon_hadpull')->field('c.*')->join('as s left join '.C('DB_PREFIX').'system_coupon c ON s.coupon_id = c.coupon_id')->where($where)->find();
    }

    public function share_coupon($param){
        $date['shera_num'] = C('config.share_coupon_num');
        $date['shera_get_num'] = C('config.share_coupon_get_num');
        $date['add_time']  = $_SERVER['REQUEST_TIME'];
        $date['had_pull']  = 0;
        $date['get_num']  = 0;
        $date['order_id']  = $param['order_id'];
        $date['uid']  = $param['uid'];
        $date['type']  = $param['type'];
        $where['order_id'] = $param['order_id'];
        $where['type'] = $param['type'];
        C('config.share_rand_send_coupon')==1 && $coupon_where['rand_send'] = 1;
        $share_info = M('Share_coupon_list')->where($where)->find();

        if($share_info){
            return array('error_code'=>1,'msg'=>L_('已经分享过了，不能再分享了'));
        }
        $share_coupon_num = $this->config['share_coupon_num'] > 0 ? $this->config['share_coupon_num'] : 0;
        $share_coupon_get_num = $this->config['share_coupon_get_num'] > 0 ? $this->config['share_coupon_get_num'] : 0;
        $num = $share_coupon_num + $share_coupon_get_num;
        $coupon_where['status']=1;
        $coupon_where['_string']='num>(had_pull+'.$num.')';
        $coupon_where['is_hide']=0; //增加手动派发券不可领取
        $coupon_list = $this->get_coupon_list($coupon_where);
        shuffle($coupon_list);
        $date['coupon_id'] = $coupon_list[0]['coupon_id'];
        if(M('Share_coupon_list')->add($date)){
            return array('error_code'=>0,'msg'=>L_('添加成功'));
        }else{
            return array('error_code'=>1,'msg'=>L_('添加分享记录失败'));
        }
    }

    public function share_coupon_rand_get_coupon($param){
        $where['order_id'] = $param['order_id'];
        $where['type'] = $param['type'];
        $share_info = M('Share_coupon_list')->where($where)->find();
        if( $param['uid']!=$share_info['uid'] && $share_info['had_pull'] >= $share_info['shera_num']){
            return array('error'=>1,'msg'=>L_('优惠券已经抢完了'));
        }

        if( $param['uid']==$share_info['uid'] && $share_info['get_num'] >= $share_info['shera_get_num'] ){
            return array('error'=>1,'msg'=>L_('您已经领取过了或优惠券已经抢完了!'));
        }

        if( strpos($share_info['userlist'],strval($param['uid']))!==false){
            return array('error'=>1,'msg'=>L_('您已经领取过了或优惠券已经抢完了'));
        }
        //if($param['coupon_id']){
        //    $coupon_list[0]['coupon_id']=$param['coupon_id'];
        //}else{
        //    $coupon_list = $this->get_coupon_list();
        //    shuffle($coupon_list);
        //}
        if( $param['uid']==$share_info['uid'] &&$share_info['shera_get_num'] >1 ){
            for($i=0;$i<$share_info['shera_get_num'];$i++){
                $result = $this->send_coupon_by_id($share_info['coupon_id'],$param['uid'],1,'',$param['order_id']);
            }
        }else{
            $result = $this->send_coupon_by_id($share_info['coupon_id'],$param['uid'],1,'',$param['order_id']);
        }
        if(!$result['error']){
            $param['uid']!=$share_info['uid'] && $date['userlist'] = empty($share_info['userlist'])?$param['uid']:$share_info['userlist'].','.$param['uid'];
            $param['uid']==$share_info['uid']?$date['get_num'] = $share_info['get_num']+$share_info['shera_get_num']:$date['had_pull'] = $share_info['had_pull']+1;
            M('Share_coupon_list')->where($where)->save($date);
            return array('error_code'=>0,'msg'=>L_('领取成功'));
        }else{
            return $result;
        }
    }

    // 积分兑换优惠券
    public function gift_get_coupon($param,$send=false){
        $where['coupon_id'] = $param['coupon_id'];
        $coupon_info = $this->where($where)->find();

        $where['uid'] = $param['uid'];
        // $hadpull_count = M('System_coupon_hadpull')->where($where)->count();
        
        if( $coupon_info['end_time']<time()||$coupon_info['status']==2){
            return array('error'=>1,'msg'=>L_('优惠券已过期，不能再兑换了!'));
        }

        if( $coupon_info['status']==0||$coupon_info['status']==4){
            return array('error'=>1,'msg'=>L_('优惠券已下架，不能再兑换了!'));
        }
        if ($send) {
            $result = $this->send_coupon_by_id($param['coupon_id'],$param['uid'],1,'','',$param['order_id']);
            if(!$result['error']){
                return array('error_code'=>0,'msg'=>L_('领取成功'));
            }else{
                return $result;
            }
        }else{
            return array('error'=>0,'msg'=>L_('可以兑换!'));
        }
        
    }

    // 瓜分券瓜分获得优惠券
    public function partition_coupon_get_coupon($id,$is_all = 0){
        $return = array('error_code'=>0,'msg'=>'优惠券分发成功!');
        $where['status'] = 0;
        if ($is_all == 1) {
            // 瓜分所有 id是发起者的id
            $where['_string'] = '(id='.$id.' OR pid ='.$id.')';
            $partition_list = M('System_partition_coupon_hadpull')->where($where)->select();
            if (!$partition_list) {
                return array('error_code'=>1,'msg'=>'没有需要瓜分数据');
            }

            // 查询已瓜分金额
            $where_had['status'] = 1;
            $where_had['_string'] = '(id='.$id.' OR pid ='.$id.')';
        }else{
            // 瓜分当前
            $where['id'] = $id;
            $now_partition = M('System_partition_coupon_hadpull')->where($where)->find();
            if (!$now_partition) {
                return array('error_code'=>1,'msg'=>'没有需要瓜分数据');
            }
            $partition_list[] = $now_partition;

            // 查询已瓜分金额
            $where_had = array();
            $where_had['status'] = 1;
            if ($now_partition['pid']) {
                $where_had['_string'] = '(id='.$now_partition['pid'].' OR pid ='.$now_partition['pid'].')';   
            }else{
                $where_had['_string'] = '(id='.$id.' OR pid ='.$id.')';  
            }
        }

        // 查询已瓜分金额
        $hadpull_money = M('System_partition_coupon_hadpull')->field('sum(money) as hadpull_money')->where($where_had)->find();
        $hadpull_money = $hadpull_money['hadpull_money'] ? getFormatNumber($hadpull_money['hadpull_money']) : 0;

        // 已瓜分人数
        $hadpull_count = M('System_partition_coupon_hadpull')->where($where_had)->count();

        // 参与人数
        unset($where_had['status']);
        $user_count = M('System_partition_coupon_hadpull')->where($where_had)->count();


        // 瓜分券详情
        $now_detail =  M('System_partition_coupon')->where(array('pigcms_id'=> $partition_list[0]['coupon_id'],'delete'=>0))->find();
        if (!$now_detail) {
            return array('error_code'=>1,'msg'=>'瓜分券不存在');
        }

        if ($user_count<$now_detail['user_num']) {
            return array('error_code'=>1,'msg'=>'瓜分人数不够');
        }

        // 生成优惠券
        // 使用有效期
        if ($now_detail['time_type']==0) {
            $data['start_time'] = $now_detail['use_start_time'];
            $data['end_time'] = $now_detail['use_end_time'];
        }else{
            $data['start_time'] = time();
            $data['end_time'] = time()+$now_detail['use_time']*86400;
        }

        // 使用条件
        if ($now_detail['use_condition']==1) {
            $data['order_money'] = $now_detail['order_money'];
        }else{
            $data['order_money'] =0;
        }
        $data['name'] = '瓜分券【'.$now_detail['name'].'】';
        $data['platform'] = serialize(array('wap','app','weixin','wxapp'));
        $data['is_partition'] = '1';
        $data['num'] = '1';
        $data['limit'] = '1';
        $data['status'] = '1';
        $data['cate_name'] = 'all';
        $data['add_time'] = time();
        // 剩余瓜分金额
        $total_money = $now_detail['money']-$hadpull_money;
        foreach ($partition_list as $key => $value) {
            if ($now_detail['type']==0) {
                $discount_money = round($now_detail['money']/$user_count,2);
            }else{
                if (($key+1+$hadpull_count)==$user_count) {
                    $discount_money = $total_money;
                }else{
                    $discount_money = $this->randFloat(0,$total_money);
                    $discount_money = round($discount_money,2);
                    $total_money = $total_money - $discount_money;
                }
            }
            $data['discount'] = getFormatNumber($discount_money);
            if (!$is_all) {
                $return['discount_money'] = getFormatNumber($discount_money);
            }
            $coupon_id = $this->add($data);
            if ($coupon_id) {
                $where = array();
                $where['coupon_id'] = $coupon_id;
                if ($this->where($where)->setInc('had_pull')) {
                    D('System_coupon')->where($where)->setField('last_time',$_SERVER['REQUEST_TIME']);
                    $coupon_data = array();
                    $coupon_data['coupon_id'] = $coupon_id;
                    $coupon_data['num'] = 1;
                    $coupon_data['receive_time'] =$_SERVER['REQUEST_TIME'];
                    $coupon_data['status'] = 0;
                    $coupon_data['uid']  = $value['uid'];
                    if(M('System_coupon_hadpull')->add($coupon_data)){
                        $update_data = array(
                            'status' => 1,
                            'money' => getFormatNumber($discount_money),
                            'partition_time' => time(),
                        );
                        M('System_partition_coupon_hadpull')->where(array('id'=> $value['id']))->save($update_data);

                        if($now_user = M('User')->where(array('uid'=>$value['uid']))->find()){
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            $model->sendTempMsg('TM00251', array('href' => C('config.site_url').'/wap.php?c=My&a=card_list&coupon_type=system', 'wecha_id' => $now_user['openid'], 'first' => "您成功领取了优惠券" , 'toName' => $now_user['nickname'], 'gift' => $data['name'],'time'=>date("Y年m月d日 H:i"), 'remark' => '有效期'.date("Y-m-d",$data['start_time']).' 至 '.date("Y-m-d",$data['end_time'])));
                        }
                    }
                   
                }
            }
        }
        return $return;
    }

    public function randFloat($min=0, $max=1){
        return $min + mt_rand()/mt_getrandmax() * ($max-$min);
    }

    //随机派发
    public function rand_send_coupon_get($param){
        $condition['rand_send'] = 1;
        $condition['status'] = 1;
		$condition['city_id'] = array(array('eq',$param['city_id']),array('eq',0), 'or');
		
        $condition['_string'] = "{$param['time']} >=rand_send_start_time AND {$param['time']}<=rand_send_end_time";
		
		//限制类别随机领券
		if($param['cate_name'] && $param['cate_name'] != 'all'){
			$condition['_string'].= " AND (cate_name='".$param['cate_name']."' OR cate_name='all')";
		}
		
        $coupon_list = $this->get_coupon_list($condition);
        //shuffle($coupon_list);
        if(empty($coupon_list)) return null;

        foreach ($coupon_list as $v) {
            //限制随机派送仅一次
            if(M('Coupon_rand_send_hadpull')->where(array('uid'=>$param['uid'],'coupon_id'=>$v['coupon_id']))->find()){
                continue;
            }
            $result = $this->send_coupon_by_id($v['coupon_id'],$param['uid'],0);
            if(!$result['error']){
                $date['coupon_id'] = $v['coupon_id'];
                $date['uid'] = $param['uid'];
                $date['add_time'] = $_SERVER['REQUEST_TIME'];
                $date['num'] = 1;
                M('Coupon_rand_send_hadpull')->add($date);
                $success_coupon[] = $v['coupon_id'];
            }
        }
        $coupon_url = C('config.site_url').'/wap.php?g=Wap&c=My&a=card_list&coupon_type=system';
        if(!empty($success_coupon)){
            //$start_time = date('m月d日' ,$coupon_list[$success_coupon[0]]['start_time']);
            //$end_time = date('m月d日' ,$coupon_list[$success_coupon[0]]['end_time']);
            $start = $coupon_list[$success_coupon[0]]['start_time'] > time() ? $coupon_list[$success_coupon[0]]['start_time'] : time();
            $yxq = intval(($coupon_list[$success_coupon[0]]['end_time']-$start)/86400);
            if($coupon_list[$success_coupon[0]]['valid_time'] < $yxq){//谁近用谁
                $yxq = $coupon_list[$success_coupon[0]]['valid_time'];
            }
            $days = L_("有效期X1天",array("X1" => $yxq)); 
            $more_coupon = count($success_coupon)>1?'<div class="mengceng2"></div>':'';
            $coupon_list[$success_coupon[0]]['order_money'] = floatval($coupon_list[$success_coupon[0]]['order_money']);
            $coupon_list[$success_coupon[0]]['discount'] = floatval($coupon_list[$success_coupon[0]]['discount']);
            $tmp['coupon'] = array();
            $tmp['coupon_count'] = count($success_coupon);
            if($param['from']=='app'){
                foreach ($success_coupon as $v) {
                    $start = $coupon_list[$v]['start_time'] > time() ? $coupon_list[$v]['start_time'] : time();
                    $_yxq = intval(($coupon_list[$v]['end_time']-$start)/86400);
                    if($coupon_list[$v]['valid_time'] < $_yxq){//谁近用谁
                        $_yxq = $coupon_list[$v]['valid_time'];
                    }
                    $return['days'] = L_("有效期X1天",array("X1" => $_yxq));
                    $return['order_money'] = floatval($coupon_list[$v]['order_money']);
                    $return['discount'] = floatval($coupon_list[$v]['discount']);
                    $return['name'] = $coupon_list[$v]['name'];
                    $return['coupon_id'] = $coupon_list[$v]['coupon_id'];
                    if($coupon_list[$v]['is_discount']){
                        $return['title_money'] = L_("打X1折",$coupon_list[$v]['discount_value']);
                        $return['title_des'] = L_("满X1打X2折",['X1'=>$coupon_list[$v]['order_money'],'X2'=>$coupon_list[$v]['discount_value']]);
                    }
                    else{
                        $return['title_money'] = C('config.Currency_symbol').$coupon_list[$v]['discount'];  
                        $return['title_des'] = L_("满X1减X2",['X1'=>$coupon_list[$v]['order_money'],'X2'=>$coupon_list[$v]['discount']]);
                    }
                    $tmp['coupon'][] = $return;
                }
                return $tmp;
            }

            $coupon_count = count($success_coupon);

            if($coupon_list[$success_coupon[0]]['is_discount']){
                $coupon_title = "<span>{$coupon_list[$success_coupon[0]]['discount_value']}</span><b>折</b>";
                $coupon_desc = "满{$coupon_list[$success_coupon[0]]['order_money']}打{$coupon_list[$success_coupon[0]]['discount_value']}折";
            }else{
                $coupon_title = "<span>".C('config.Currency_symbol')."</span><b>{$coupon_list[$success_coupon[0]]['discount']}</b>";
                $coupon_desc = "满{$coupon_list[$success_coupon[0]]['order_money']}减{$coupon_list[$success_coupon[0]]['discount']}";

            }
            $coupon_html = <<<Eof
        <style>
            #mongolia_layer{position:fixed;bottom:0;top:0;left:0;right:0;background-color:#000;opacity:.5;z-index:1000;width:100%}
            #Coupon{width:290px;z-index:1500;background:url(../static/images/coupon/share_coupon2.png) center no-repeat;height:260px;background-size:cover;position:fixed;left:50%;margin-left:-145px;top:50%;margin-top:-130px}
            #Coupon .delate_money {
                position: absolute;
                left: 89%;
                top: 3%;
                width: 20px;
                height: 20px;
                font-weight: normal;
                color: #fff;
                border: 1px solid #fff;
                border-radius: 50%;
                padding: 0px 0px;
                z-index: 99;
            }

 #Coupon .mengceng{
	display: block;
    width: 256px;
    height: 75px;
    background: url(../static/images/coupon/share_coupon3.png) center no-repeat;
    background-size: cover;
    position: absolute;
    top: 50%;
    left: 50%;
    margin-left: -124px;
    margin-top: -37.5px;
}
 #Coupon .delate_money i{
 	font-style: normal;
    font-size: 20px;
    position: absolute;
    top: -6px;
    left: 3px;
    font-weight: normal;
 }
#Coupon .mengceng2{
	display: block;
    width: 256px;
    height: 75px;
    background: url(../static/images/coupon/share_coupon3.png) center no-repeat;
    background-size: cover;
    position: absolute;
    top: 50%;
    left: 50%;
    margin-left: -128px;
    margin-top: -45.5px;
    box-shadow: 0px 4px 4px #c7b2b2;
}
            #Coupon .title{text-align:center;font-size:18px;color:#fff;font-weight:900;width:100%;position:absolute;top:18px}
            #Coupon .desc{margin:0;padding:0;margin-top:53px;color:#fff;font-size:15px;text-align:center;width:100%;position:absolute;font-weight:600}
          #Coupon .left_text{
	position: absolute;
    top: 12%;
    left: 2%;
}
#Coupon .left_text dt{
	text-align:center;
}
#Coupon .left_text dt span{
	color: #f00;
    font-size: 1.2em;

}
#Coupon .left_text dt b{
	color: #f00;
    font-size: 1.7em;
}
#Coupon .left_text dd {
    text-align: center;
    font-size: 0.7em;
    color: #f00;
    margin-left: 15px;
}
#Coupon .right_text{
	position: absolute;
	top:13%;
	left:40%;
}
#Coupon .right_text dt{
	font-weight: bold;
    font-size: 1.4em;
}
 #Coupon .right_text dd {
    color: #f00;
    text-align: left;
    font-size: 12px;
}
#Coupon p{
	position:absolute;
	top:67%;
	left:25%;
}
           #Coupon .btn {
            position: absolute;
            top: 79%;
            left: 6%;
            text-align: center;
            width: 88%;
            height: 40px;
            border-radius: 30px;
            border: 0;
            color: #410509;
            background-color: #ffda31;
            font-weight: bold;
            font-family: "SimSun";
            font-size: 15px;
             }
              </style>
        <div id="mongolia_layer"></div>
		    <div id="Coupon">
			<span id="coupon_close" class="delate_money "><i>×</i></span>
			<span class="title">优惠劵来袭</span>
			<span class="desc">平台赠送您{$coupon_count}张优惠劵</span>
			<div class="mengceng">
				{$more_coupon}
                <dl class="left_text">
                    <dt>{$coupon_title}</dt>
                    <dd>{$coupon_desc}</dd>
                </dl>
                <dl class="right_text">
                    <dt>{$coupon_list[$success_coupon[0]]['name']}</dt>
                    <dd>{$days}</dd>
                </dl>
			</div>
			<p>优惠劵已存入您的账户</p>
			<button class="btn redirect_coupon" >立即查看</button>
		</div>
		<style>
		  #Coupon .right_text dt{
			  font-size:15px
		  }
		</style>
        <script>
            $('#coupon_close').click(function(){
                $('#Coupon').hide();
                $('#mongolia_layer').hide();
            });
            $('.redirect_coupon').click(function(){
                window.location.href ='$coupon_url'
            })
        </script>
Eof;
            //echo $coupon_hmtl;
            return $coupon_html;
        }
    }

    //根据商家id获取可领取的优惠券
    public function get_coupon_list_by_merid_diypage()
    {
        $where['end_time'] = array('gt', time());
        $where['start_time'] = array('lt', time());
        $where['status'] = 1;
        $where['allow_im_get'] = 0;
        $where['is_partition'] = 0;
        $where['is_hide'] = 0;

        if ($_POST['keyword']) {
            $where['name'] = array('like', '%' . $_POST['keyword'] . '%');
        }

        $count = $this->where($where)->count();
        import('@.ORG.diypage');
        $Page = new Page($count, 6);

        $res = $this
            ->where($where)
            ->order('status ASC,allow_new DESC,discount DESC,add_time DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->getField('coupon_id,name,platform,img,had_pull,num,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,wx_cardid,wx_ticket_addtime,cardsign');

        return array('coupon_list' => $res, 'page_bar' => $Page->show(), 'coupon_count' => $count);
    }

    public function getCanUseCoupon($now_order, $coupon_list, $business_type, $platform, $coupon_type = 'system', $order_detail_goods=[]){
        // 订单对应优惠券业务类别
        $is_system_full_discount_order = false;
        switch ($business_type) {
            case 'group':
                $table = 'group';
                if($now_order['order_id']){
                    $order_cate = PD(ucfirst($table).'_order')->get_order_cate($now_order['order_id']);
                }else{
                    $order_cate = PD(ucfirst($table).'_order')->get_order_cate($now_order['temp_order_id'],true);
                }
                break;
            case 'meal':
            case 'food':
            case 'foodPad':
            case 'foodshop':
            case 'takeout':
                $table = 'meal';
                $order_cate = PD(ucfirst($table).'_order')->get_order_cate($now_order['order_id'],$now_order['store_id']);
                break;
            case 'appoint':
            case 'balance-appoint':
                $table = 'appoint';
                $order_cate = PD(ucfirst($table).'_order')->get_order_cate($now_order['order_id']);
                break;
            case 'shop':
                $table = 'shop';
                if(empty($now_order['order_id'])){
                    $order_cate = PD(ucfirst($table) . '_order')->get_order_cate_more(['store_id'=>$now_order['store_id']],true);
                }else{
                    $order_cate = PD(ucfirst($table) . '_order')->get_order_cate_more($now_order['order_id']);
                }

                foreach($order_cate as $s){
                    $cate_arr[] = $s['cat_id'];
                }

                // 满赠优惠
                $is_system_full_discount_order = false;
                $discount_detail = unserialize($now_order['discount_detail']);
                $is_system_full_discount_order = D('Shop_order')->is_system_full_discount_order($discount_detail);

                //商城和外卖的订单， 筛选商家优惠券不能用can_discount_money，因为增加了优惠券指定商品功能
                if($now_order['goods']){
                    $order_detail_goods = [];
                    foreach ($now_order['goods'] as $v) {
                        if (isset($order_detail_goods[$v['goods_id']])) {
                            $order_detail_goods[$v['goods_id']] += $v['discount_price'] * $v['num'];
                        } else {
                            $order_detail_goods[$v['goods_id']] = $v['discount_price'] * $v['num'];
                        }
                    }
                }else if($now_order['order_content']){
                    $order_detail_goods = [];
                    foreach ($now_order['order_content'] as $v) {
                        if (isset($order_detail_goods[$v['goods_id']])) {
                            $order_detail_goods[$v['goods_id']] += $v['discount_price'] * $v['num'];
                        } else {
                            $order_detail_goods[$v['goods_id']] = $v['discount_price'] * $v['num'];
                        }
                    }
                }else if(!$order_detail_goods){
                    $order_detail_goods = array_column(M("Shop_order_detail")->where(['order_id' => $now_order['order_id']])->field("goods_id,price*num as allprice")->select(), "allprice", 'goods_id');
                }

                $no_total_money = true;
                break;
            case 'mall':
                $table = 'mall';
                $order_cate =array('mall');

                //商城和外卖的订单， 筛选商家优惠券不能用can_discount_money，因为增加了优惠券指定商品功能
                $order_detail_goods = array_column(M("Shop_order_detail")->where(['order_id' => $now_order['order_id']])->field("goods_id,price*num as allprice")->select(), "allprice", 'goods_id');
                break;
            case 'store':
                $table = 'store';
                $order_cate =array('store');
                break;
            case 'cash':
                $table = 'cash';
                $order_cate =array('cash');
                break;
            case 'village_group':
                $table = 'village_group';
                $order_cate =array('village_group');
                break;
        }


        if (empty($business_type) && in_array($now_order['type'], ['scenic', 'stadium', 'course'])) {
            $business_type = 'lifetools';
            $now_order['total_money'] = $now_order['total_price'];
            $table = $now_order['type'];
            $order_cate = array('scenic', 'stadium', 'course');

            //判断是否是体育秒杀，优惠是否同享
            if(in_array($now_order['type'], ['stadium', 'course'])){
                $sql = "SELECT `la`.`start_time`,`la`.`end_time`,`act`.*,`sku`.`act_stock_num`,`sku`.`act_price`,`l`.`tools_id`,`sku`.`ticket_id`,`sku`.`day_stock_num` FROM `pigcms_life_tools_sports_seconds_kill_ticket_detail` `s` INNER JOIN `pigcms_life_tools_sports_seconds_kill` `la` ON `la`.`id`=`s`.`activity_id` INNER JOIN `pigcms_life_tools_sports_seconds_kill_detail` `act` ON `act`.`id`=`la`.`act_id` INNER JOIN `pigcms_life_tools_sports_seconds_kill_ticket_sku` `sku` ON `sku`.`ticket_id`=`s`.`ticket_id` INNER JOIN `pigcms_life_tools_ticket` `lt` ON `lt`.`ticket_id`=`s`.`ticket_id` INNER JOIN `pigcms_life_tools` `l` ON `l`.`tools_id`=`lt`.`tools_id` WHERE  `lt`.`tools_id` = ".$now_order['tools_id']."  AND `lt`.`status` = 1  AND `lt`.`is_del` = 0  AND `sku`.`ticket_id` = ".$now_order['ticket_id']."  AND `la`.`start_time` < ".time()."  AND `la`.`end_time` > ".time()."  AND `la`.`type` = 'limited'  AND `la`.`is_del` = 0 ORDER BY `act`.`time_type` ASC,`sku`.`id` DESC LIMIT 1";
                $mode = new Model();
                $ret = $mode->query($sql);
                $ret = $ret ? $ret[0] : [];
                $times = date('H:i:s', time());
                $time = explode(':', $times);
                $sec = $time[0] * 3600 + $time[1] * 60 + $time[2] * 1;//当前秒数
                if($ret && (
                    ($ret['time_type'] == 1 && time() > $ret['start_time'] && time() <= $ret['end_time']) ||
                    ($ret['time_type'] != 1 && $ret['cycle_type'] == 1 && $sec > $ret['cycle_start_time'] && $sec < $ret['cycle_end_time']) ||
                    ($ret['time_type'] != 1 && $ret['cycle_type'] == 2 && in_array(date('w'), explode(',', $ret['cycle_date'])) && $ret['cycle_date'] && $sec < $ret['cycle_end_time'] && $sec >= $ret['cycle_start_time']) ||
                    ($ret['time_type'] != 1 && $ret['cycle_type'] != 1 && $ret['cycle_type'] != 2 && !empty($ret[3]) && in_array(explode('-', date('Y-m-d'))[2]*1, explode(",", $ret['cycle_date'])) && $sec < $ret['cycle_end_time'])
                    )){
                    $ret['limited_status'] = 1;
                }
            }
        }
        

        if ($business_type == 'deliver') {
            $table = 'deliver';
        }

        // 优惠券相关信息中文对照数组
        $cate_platform = $this->cate_platform();

        //可使用优惠券列表
        $can_use_coupon_list = array();

        //不可使用优惠券列表
        $un_use_coupon_list = array();
        foreach($coupon_list as $k=>&$v){
            //从V20调用判断优惠券是否可用的兼容处理字段问题
            if (strtolower(MODULE_NAME . '/' . ACTION_NAME . '/' . GROUP_NAME) == 'service/invoke/index') {
                $v['platform_s'] = $v['platform'];
                $v['cate_name_s'] = $v['cate_name'];

                $v['platform'] = unserialize($v['platform']);
                $v['platform'] = array_map(function ($r) use ($cate_platform) {
                    return $cate_platform['platform'][$r] ?? $r;
                }, $v['platform']);
                $v['cate_name'] = $cate_platform['category'][$v['cate_name']] ?? $v['cate_name'];

                $v['cate_id'] = is_array($v['cate_id'])?$v['cate_id']:unserialize($v['cate_id']);
            }
            
            if($v['cate_name']=='all'){
                $v['cate_name']=L_('全通类优惠券');
            }
            if($v['is_discount']){
                $v['coupon_des'] = ($v['order_money']>0?L_('满X1',floatval($v['order_money']).C('config.Currency_txt')):'').L_('打X1折',floatval($v['discount_value']));
            }else{
                $v['coupon_des'] = ($v['order_money']>0?L_('满X1',floatval($v['order_money']).C('config.Currency_txt')):'').L_('减X1',floatval($v['discount']).C('config.Currency_txt'));
            }
            $tmp_coupon_list['discount_value'] = floatval($v['discount_value']);
            $tmp_coupon_list['is_discount'] = $v['is_discount'];
            $tmp_coupon_list['coupon_des'] = $v['coupon_des'];
            $tmp_coupon_list['name'] = $v['name'];
            $tmp_coupon_list['discount'] = $v['discount'];
            $tmp_coupon_list['order_money'] = $v['order_money'];

            if ($v['is_membership_card']) {
                $tmp_coupon_list['had_id'] = $v['had_id'];
                $tmp_coupon_list['is_membership_card'] = true;
            } else {
                $tmp_coupon_list['had_id'] = $v['id'];
                $tmp_coupon_list['is_membership_card'] = false;
            }
            if ($business_type == 'deliver') {
                $tmp_coupon_list['had_id'] = $v['coupon_id'];
            }

            if($coupon_type=='mer'){
                $tmp_coupon_list['img'] = $this->config['site_url'].$v['img'];
            }

            $tmp_coupon_list['platform'] = $v['platform'];
            $tmp_coupon_list['des'] = $v['des'];
            $tmp_coupon_list['cate_name'] = $v['cate_name'];
            $tmp_coupon_list['time'] = L_('有效期至X1',date('y.m.d',$v['end_time']));
            $tmp_coupon_list['end_time'] = $v['end_time'];
            $tmp_coupon_list['coupon_id'] = $v['coupon_id'];

            /********** 限时优惠券不可使用原因 start ********/
            // 此优惠券是否可以使用
            $tmp_coupon_list['can_use'] = 1;
            $tmp_coupon_list['can_use_info'] = array();
            // 优惠券指定商品
            if($v['point_goodids']){//如果设置了指定商品， 则走指定商品的逻辑
                $point_goodids = unserialize($v['point_goodids']);
                if($point_goodids[$business_type]){
                    $order_money = 0;
                    foreach ($order_detail_goods as $_gk => $_g) {
                        if(in_array($_gk, $point_goodids[$business_type])){
                            $order_money += $_g;
                        }
                    }
                    if($order_money == 0){
                        $tmp_coupon_list['can_use'] = 0; 
                        $tmp_coupon_list['can_use_info'][] = L_('指定商品可用');
                    }
                    elseif($v['order_money'] > $order_money){
                        $tmp_coupon_list['can_use'] = 0; 
                        $tmp_coupon_list['can_use_info'][] = L_('指定商品现价不满X1',floatval($v['order_money']).C('config.Currency_txt')); 
                    }
                    $no_order_money = true;
                }
                elseif(empty($point_goodids[$business_type])){
                    $tmp_coupon_list['can_use'] = 0; 
                    $tmp_coupon_list['can_use_info'][] = L_('指定商品可用');
                }
            }

            //平台优惠券和满赠不共享
            if($is_system_full_discount_order && $v['is_share_with_discount'] == 0 && $v['mer_id'] == 0){
                $tmp_coupon_list['can_use'] = 0;
                $tmp_coupon_list['can_use_info'][] = L_('与平台满减优惠不可共享');
            }
            // 订单金额不足
            if($coupon_type == 'mer'){
                if(!isset($now_order['can_discount_money'])){
                    $now_order['can_discount_money'] = $now_order['total_money'];
                }
                if ($now_order['can_discount_money'] < $v['order_money'] && !$no_order_money) {
                    $tmp_coupon_list['can_use'] = 0;
                    $tmp_coupon_list['can_use_info'][] = L_('可用商品现价不满X1', floatval($v['order_money']) . C('config.Currency_txt'));
                }
            }else{
                if ($business_type == 'deliver') {
                    if ($now_order['order_money'] < $v['order_money']) {
                        $tmp_coupon_list['can_use'] = 0;
                        $tmp_coupon_list['can_use_info'][] = L_('可用商品现价不满X1',floatval($v['order_money']).C('config.Currency_txt'));
                    }
                } else if ($now_order['total_money']-$now_order['freight_charge']<$v['order_money'] && !$no_order_money) {
                    $tmp_coupon_list['can_use'] = 0;
                    $tmp_coupon_list['can_use_info'][] = L_('可用商品现价不满X1',floatval($v['order_money']).C('config.Currency_txt'));
                }
            }


            // 平台优惠券 自提配送
            if ($coupon_type=='system' && $business_type == 'shop' ) {
                if($now_order['is_pick_in_store']==2 && $v['deliver_type']==2){//自提不能使用配送优惠券
                    $tmp_coupon_list['can_use'] = 0; 
                    $tmp_coupon_list['can_use_info'][] = L_('仅限配送可用'); 
                }elseif($now_order['is_pick_in_store']!=2 && $v['deliver_type']==1){//配送不能使用自提优惠券                 
                    $tmp_coupon_list['can_use'] = 0; 
                    $tmp_coupon_list['can_use_info'][] = L_('仅限自提可用');  
                }
            }

            // 平台优惠券 城市限制
            if ($coupon_type=='system') {
                if($now_order['store_id']){
                    $store_city_id = M('Merchant_store')->where(['store_id'=>$now_order['store_id']])->getField('city_id');
                    if ($v['city_id'] && $v['city_id'] != $store_city_id) {
                        $city_info = D('Area')->get_area_by_areaId($v['city_id'],false);
                        $tmp_coupon_list['can_use'] = 0;
                        $tmp_coupon_list['can_use_info'][] = L_('仅用于【X1】城市',$city_info['area_name']);  
                    }
                }else{
                    $user_city_id = M('User')->where(['uid'=>$now_order['uid']])->getField('city_id');
                    if ($v['city_id'] && $v['city_id'] != $user_city_id) {
                        $city_info = D('Area')->get_area_by_areaId($v['city_id'],false);
                        $tmp_coupon_list['can_use'] = 0;
                        $tmp_coupon_list['can_use_info'][] = L_('仅用于【X1】城市',$city_info['area_name']);  
                    }
                }
            }
            // 限使用平台：移动网页、app
            $flag = false;
            $v['platform_s']= unserialize($v['platform_s']);
            foreach($v['platform_s'] as &$vp){
                if($vp==$platform) {
                    $flag = true;
                }
                $vp=$cate_platform['platform'][$vp];
            }
            if(!$flag){
                $tmp_coupon_list['can_use'] = 0;
                $tmp_coupon_list['can_use_info'][] = L_('限使用平台').'：'.implode('、',$v['platform_s']);  
            }
            // 限业务：团购、外卖
            if ($v['cate_name_s'] == 'new_user') {
                if (!D('User')->check_new($now_order['uid'], 'all')) {
                    $tmp_coupon_list['can_use'] = 0;
                    $tmp_coupon_list['can_use_info'][] = L_('限业务：') . $v['cate_name'];
                }
            } else if ($v['cate_name_s'] != 'all' && $v['cate_name_s'] != $table) {
                //特殊判断：店内收银类型优惠券支持优惠买单使用
                if (!($table == 'store' && $v['cate_name_s'] == 'cash')) {
                    $tmp_coupon_list['can_use'] = 0;
                    $tmp_coupon_list['can_use_info'][] = L_('限业务：') . $v['cate_name'];
                }
            }
           
            // 限品类：酒店、美食、电影、旅游
            if(!empty($v['cate_id'])) {
                if ($business_type == 'shop') {
                    if(!in_array($v['cate_id']['cat_id'],$cate_arr)){
                        $tmp_coupon_list['can_use'] = 0;
                        $tmp_coupon_list['can_use_info'][] = L_('限品类：').$v['cate_id']['cat_name']; 
                    }
                }else{
                    if($v['cate_id']['cat_id'] != $order_cate['cat_id']){
                        $tmp_coupon_list['can_use'] = 0;
                        $tmp_coupon_list['can_use_info'][] = L_('限品类：').$v['cate_id']['cat_name']; 
                    }
                }
            }

            // 未达使用时间
            if (time()<$v['start_time']) {
                $tmp_coupon_list['can_use'] = 0; 
                $tmp_coupon_list['can_use_info'][] = L_('未达使用时间').date('Y-m-d H:i:s',$v['start_time']); 
            }

            // 未领取商家会员卡
            if ($coupon_type=='mer') {
                $check_card = 1;
                // 限指定店铺
                if ($v['store_id']) {
                    $store_ids = explode(',', $v['store_id']);
                    if ($business_type != 'lifetools' && !in_array($now_order['store_id'], $store_ids)) {
                        $tmp_coupon_list['can_use'] = 0;
                        $tmp_coupon_list['can_use_info'][] = L_('仅用于指定店铺'); 
                        $check_card = 0;
                    }
                }
                if(!empty($v['mer_id']) && $now_order['mer_id'] != $v['mer_id']){
                    $tmp_coupon_list['can_use'] = 0;
                    $tmp_coupon_list['can_use_info'][] = L_('仅用于指定商家');
                }
                if ($check_card && !in_array($business_type, ['group', 'lifetools'])) {
                    if ($card = D('Card_userlist')->where(array('mer_id' => $now_order['mer_id'],'uid'=>$now_order['uid'], 'status' => 1 ))->find()) {
                        if ($v['use_with_card']==0 /*&& $card['discount'] >0 && $card['discount'] <10*/) {
                            $tmp_coupon_list['can_use'] = 0;
                            $tmp_coupon_list['can_use_info'][] = L_('会员卡优惠与优惠券不同享'); 
                        }
                    }else{
//                        $tmp_coupon_list['can_use'] = 0;
//                        $tmp_coupon_list['can_use_info'][] = L_('商家未启用会员卡，无法使用'); 
                    }
                }
                
            }

            //外卖&&商城业务判断指定店铺
            if ($coupon_type == 'system' && ($business_type == 'shop'|| $business_type == 'mall'|| $business_type == 'store' || $business_type == 'cash') && $v['use_type'] == 1 && !M('System_coupon_store')->where(['store_id' => $now_order['store_id'], 'coupon_id' => $v['coupon_id']])->find()) {
                $tmp_coupon_list['can_use'] = 0;
                $tmp_coupon_list['can_use_info'][] = L_('指定店铺可用');
            }

            //当运费是0的时候，不可使用减运费的优惠券
            if ($v['discount_type'] == 1 && $now_order['freight_charge'] == 0) {
                $tmp_coupon_list['can_use'] = 0;
                $tmp_coupon_list['can_use_info'][] = L_('当前券为减配送费券且配送费为0，故无须使用该券');
            }

            //体育秒杀不同享优惠时
            if (!empty($ret) && $ret['limited_status'] == 1 && $ret['is_discount_share'] == 2) {
                //优惠不可同享
                $tmp_coupon_list['can_use'] = 0;
                $tmp_coupon_list['can_use_info'][] = L_('当前体育秒杀活动与优惠券不同享');
            }


            /********** 限时优惠券不可使用原因 end ********/
            if ($v['end_time']>time()) {
                if ($tmp_coupon_list['can_use']) {
                    $can_use_coupon_list[] = $tmp_coupon_list;
                }else{
                    $un_use_coupon_list[] = $tmp_coupon_list;
                }
            }
            unset($coupon_list[$k]['cate_id']);
        }

        $can_use_total = !empty($can_use_coupon_list) ? count($can_use_coupon_list) : '0';
        return ['can_use_coupon_list' => $can_use_coupon_list,'can_use_total'=>$can_use_total,'un_use_coupon_list'=>$un_use_coupon_list];
    }
}
