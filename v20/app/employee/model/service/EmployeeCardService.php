<?php


namespace app\employee\model\service;

use app\common\model\db\CardUserlist;
use app\common\model\db\Merchant;
use app\common\model\db\UserLevel;
use app\common\model\service\plan\PlanService;
use app\employee\model\db\EmployeeCard;
use app\employee\model\db\EmployeeCardUser;
use app\employee\model\db\EmployeeCardCoupon;
use app\employee\model\db\EmployeeCardCouponSend;
use app\employee\model\db\EmployeeCardLog;
use app\employee\model\db\EmployeeCardStore;
use app\employee\model\db\EmployeeCardClearScore;
use app\employee\model\db\EmployeeCardPayStore;
use app\employee\model\db\User;
use app\life_tools\model\db\LifeToolsCardOrder;
use app\life_tools\model\db\LifeToolsCardOrderRecord;
use app\merchant\model\db\MerchantStore;
use think\facade\Db;

class EmployeeCardService
{

    public $employeeCardUserModel =  null;
    public $employeeCardModel =  null;
    public $employeeCardCouponSendModel =  null;
    public $employeeCardLogModel =  null;
    public $userModel = null;
    public $employeeCardCouponModel = null;
    public $LifeToolsCardOrder = null;
    public $LifeToolsCardOrderRecord = null;
    public $employeeCardClearScoreModel = null;

    public function __construct()
    {
        $this->employeeCardUserModel = new EmployeeCardUser();
        $this->employeeCardModel = new EmployeeCard();
        $this->employeeCardCouponSendModel = new EmployeeCardCouponSend();
        $this->employeeCardLogModel = new EmployeeCardLog();
        $this->merchantModel = new Merchant();
        $this->userModel = new User();
        $this->employeeCardCouponModel = new EmployeeCardCoupon();
        $this->LifeToolsCardOrder = new LifeToolsCardOrder();
        $this->LifeToolsCardOrderRecord = new LifeToolsCardOrderRecord();
        $this->employeeCardClearScoreModel = new EmployeeCardClearScore();
    }
    /**
     * 员工卡列表
     */
    public function getCardList($param)
    {
        $where=[];
        if(!empty($param['name'])){
            $where=[['name','like','%'.$param['name'].'%']];
        }
        if(!empty($param['mer_id'])){
            array_push($where,['mer_id','=',$param['mer_id']]);
        }
        $list['list']=(new EmployeeCard())->getCardList($where);
        $list['total']=(new EmployeeCard())->getCount($where);
        return $list;
    }

     /**
     * 员工卡列表接口
     */
    public function cardList($params)
    {
        if(empty($params['uid'])){
            throw new \think\Exception('请先登录！');
        }
        $condition = [];
        $condition[] = ['uid', '=', $params['uid']];
        $condition[] = ['status', '=', 1];
        
        $with = [];

        $with['card'] = function($query){
            $query->where('status', 1)->append(['bg_image_text'])->bind(['name', 'bg_image'=>'bg_image_text', 'bg_color']);
        };

        $with['merchant'] = function($query){
            $query->field(['mer_id', 'logo'])->withAttr('logo', function($value, $data){
                return replace_file_domain($value);
            })->bind(['logo']);
        };

        $list = $this->employeeCardUserModel
            ->field(['card_id', 'card_money', 'card_score','mer_id'])
            ->with($with)
            ->where($condition)
            ->select();
        if ($list) {
            $list = $list->toArray();
        } else {
            $list = [];
        }

        foreach($list as $key => $val)
        {
            if(empty($val['name'])){
                unset($list[$key]);
            }
        }

        //次卡
        $condition = [];
        $condition[] = ['o.uid', '=', $params['uid']];
        $condition[] = ['o.order_status', 'not in', [10, 51, 60]];
        $condition[] = ['o.is_show', '=', 1];
        //$condition[] = ['o.out_time', 'exp', Db::raw('>' .time() . ' or out_time = 0')];  //未使用/已过期也显示
        $card = $this->LifeToolsCardOrder->getAllList($condition);
        if ($card) {
            $arr = [];
            foreach ($card as $k => $v) {
                $arr[$k]['card_type'] = 'lifetoolscard'; //次卡
                $arr[$k]['card_id']   = $v['card_id'];
                $arr[$k]['order_id']  = $v['order_id'];
                $arr[$k]['title']     = $v['title'];
                $arr[$k]['image']     = replace_file_domain($v['image']);
                $arr[$k]['countdown'] = 0;
                $arr[$k]['is_over'] = 0; //1-已过期
                if($v['out_time']){
                    if(($v['out_time']-time())>0){
                        $arr[$k]['countdown'] = ceil(($v['out_time'] - time()) / 86400); //剩余时间/天,进一取整
                    }else{
                        $arr[$k]['is_over'] = 1;
                    }
                }
                //$arr[$k]['countdown'] = $v['out_time'] == 0 ? 0 : ceil(($v['out_time'] - time()) / 86400); //剩余时间/天,进一取整
                $arr[$k]['today_num'] = $v['day_num'] == -1 ? '不限' : $v['day_num'] - $this->LifeToolsCardOrderRecord->getCount([['order_id', '=', $v['order_id']], ['add_time', '>=', strtotime(date('Y-m-d'))]]) ?? 0; //今日剩余次数
                $arr[$k]['all_num']   = $v['num'] - $this->LifeToolsCardOrderRecord->getCount([['order_id', '=', $v['order_id']]]) ?? 0; //总剩余次数
            }
            $list = array_merge($list, $arr);
        }

        $site_url = cfg('site_url');
        //平台会员卡
        $user = $this->userModel->where('uid', $params['uid'])->find();
        if(cfg('level_onoff') == 1 && $user->level > 0){
            $levelData = (new UserLevel())->where('level', $user->level)->find();
            if($levelData && ($levelData->validity == 0 || ($user->level_time + $levelData->validity*86400) > time() )){
          
                $arr = [];
                $arr['card_type'] = 'platform_card'; //平台会员卡
                $arr['card_id'] = 0; 
                $arr['url'] = $site_url . '/wap.php?g=Wap&c=My&a=levelUpdate';
                $arr['order_id'] = 0;
                $arr['title'] = '平台会员卡';
                $arr['image'] = $site_url . '/static/images/card/new_merchant_card.png';
                $arr['countdown'] = $levelData->validity == 0 ? '永久有效' :  '到期时间：' . date('Y-m-d', $user->level_time + $levelData->validity * 86400);//'剩余 '. ceil((($user->level_time + $levelData->validity * 86400) - time()) / 86400) . '天到期';
                $arr['today_num'] = '不限';
                $arr['all_num'] = '不限';
                $arr['level'] = $levelData->lname;
                $arr['nickname'] = $user->nickname;
                $arr['avatar'] = replace_file_domain($user->avatar);
                $list[] = $arr; 
            }
        }

        //商家会员卡
        $condition = [];
        $condition[] = ['cl.uid', '=', $params['uid']];
        $condition[] = ['m.status', '=', 1];
        $condition[] = ['cl.status', '=', 1];
        $condition[] = ['c.status', '=', 1];
        $field = 'c.card_id,c.bg,c.diybg,c.numbercolor,m.name,c.discount,cl.id as cardid,cl.wx_card_code,cl.card_money,cl.card_money_give,'
        .'m.pic_info,m.mer_id,m.logo,c.status as card_status ,cl.status as usercard_status';
        $merCardList = (new CardUserlist())->getCardList($condition, $field);
        if($merCardList){
            foreach($merCardList as $key => $val){
                $arr = [];
                $arr['card_type'] = 'merchant_card'; //商家会员卡
                $arr['card_id'] = 0; 
                $arr['url'] = $site_url . '/wap.php?g=Wap&c=My_card&a=merchant_card&mer_id=' . $val['mer_id'];
                $arr['order_id'] = 0;
                $arr['title'] = $val['name'];
 
                if(!empty($val['diybg'])){
                    $arr['image'] = $val['diybg'];
                }else{
                    $arr['image'] = $val['bg'];
                }
                $arr['image'] =  replace_file_domain($arr['image']);
                $arr['discount'] = $val['discount']; //折扣
                $arr['card_id'] = $val['cardid'];
                $arr['money'] = $val['card_money_give'];
                $arr['logo'] = replace_file_domain($val['logo']);
                $arr['nickname'] = $user->nickname;
                $arr['avatar'] = replace_file_domain($user->avatar);
                $list[] = $arr; 
            }
        }

        $list = array_values($list);
        
        return $list;
    }

    /**
     * 员工卡详情接口
     */
    public function cardDetail($params)
    {
        if(empty($params['uid'])){
            throw new \think\Exception('请先登录！');
        }
        if(empty($params['card_id'])){
            throw new \think\Exception('card_id不能为空！');
        }
       
        $condition = [];
        $condition[] = ['uid', '=', $params['uid']];
        $condition[] = ['card_id', '=', $params['card_id']];
        
        $with = [];
        $with['card'] = function($query){
            $query->where('status', 1)->append(['bg_image_text'])->bind(['name', 'bg_image'=>'bg_image_text','bg_color', 'next_clear_time']);
        }; 
        $with['merchant'] = function($query){
            $query->field(['mer_id', 'logo', 'name'])->withAttr('logo', function($value, $data){
                return replace_file_domain($value);
            })->bind(['logo', 'mer_name'=>'name']);
        };
        $data = $this->employeeCardUserModel
                ->field(['user_id', 'card_id', 'card_money', 'card_score', 'mer_id', 'agree_user_agreement'])
                ->with($with) 
                ->where($condition)
                ->find();
        if(empty($data)){
            throw new \think\Exception('未查到信息！');
        }
         
        $data->coupon_num =$this->employeeCardCouponSendModel->getCouponMunNew($params);

        $alert_status = 0;
        $alert_msg = "";
        if($this->employeeCardModel->isNotice($data->card_id)){
            $alert_status = 1;
            $alert_msg = "您累计的{$data->card_score}元消费积分，即将于 ".date('Y年m月d日H时i分',$data->next_clear_time)." 到期清零，请尽快到{$data->mer_name}各店消费。";
        }
        $data->alert_status = $alert_status;
        $data->alert_msg = $alert_msg;
        return $data;
    }

    /**
     * 卡优惠券列表
     */
    public function getCouponList($param)
    {
        $where=[['card_id','=',$param['card_id']]];
        $list['list']=(new EmployeeCardCoupon())->getSome($where,true,'pigcms_id desc')->toArray();
        $list['total']=(new EmployeeCardCoupon())->getCount($where);
        return $list;
    }
    /**
     * 员工卡编辑
     */
    public function editCard($param)
    {
        $where=[['mer_id','=',$param['mer_id']]];
        $employeeCardModel = new EmployeeCard();
        $ret['card']=$detail=$employeeCardModel->editCard($where);
        $ret['coupon_list']=[];
        $where1=[['mer_id','=',$param['mer_id']],['status','=',1]];
        $ret['store']=(new MerchantStore())->getSome($where1,'store_id,name');
        $list=(new EmployeeCardStore())->getSome($where);
        $pay_store_select = (new EmployeeCardPayStore())->where($where)->column('store_id');
        $store_select=[];
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $store_select[]=$v['store_id'];
            }
        }
        $ret['store_select']=$store_select;
        $ret['pay_store_select']=$pay_store_select;
        if(!empty($detail)){
            $ret['card']['bg_image']=replace_file_domain($detail['bg_image']);
        }

        //可以使用积分余额支付的商家
        $pay_merchants = $employeeCardModel->getMerCardList([['e.status', '=', 1]], ['e.mer_id', 'm.name']);
        foreach($pay_merchants as $key => $val){
            if($val['mer_id'] == $param['mer_id']){
                unset($pay_merchants[$key]);
            }
        }
        $pay_merchants = array_values($pay_merchants);
        $pay_merchant_select = [];
        if(isset($ret['card']['pay_merchants']) && $ret['card']['pay_merchants']){
            $pay_merchant_select = explode(',', $ret['card']['pay_merchants']);
            if($pay_merchant_select){
                foreach($pay_merchant_select as $key => $val){
                    $pay_merchant_select[$key] = intval($val);
                }
            }
        }
        $ret['pay_merchants'] = $pay_merchants;
        $ret['pay_merchant_select'] = $pay_merchant_select;
        return $ret;
    }

    /**
     * 优惠券编辑
     */
    public function editCoupon($param)
    {
        $where=[['pigcms_id','=',$param['pigcms_id']]];
        $detail=(new EmployeeCardCoupon())->editCoupon($where);
        if($detail['label_ids'] == ''){
            $detail['label_ids'] = [];
        }else{
            $detail['label_ids'] = explode(',', $detail['label_ids']);
            foreach($detail['label_ids'] as &$val){
                $val = intval($val);
            }
        }

        if($detail['send_rule'] == ''){
            $detail['send_rule'] = [];
        }else{
            $detail['send_rule'] = explode(',', $detail['send_rule']);
            
            if($detail['send_by'] == 1){
                foreach($detail['send_rule'] as &$val){
                    $val = intval($val);
                } 
            }
            
        }
        if($detail['other_date'] == ''){
            $detail['other_date'] = (object)[];
        }else{
            $detail['other_date'] = json_decode($detail['other_date']);
        }

        return $detail;
    }
    /**
     * 员工卡保存
     */
    public function saveCard($param)
    {
        //清积分时间
        $next_clear_time = $this->employeeCardClearScoreModel->calcNextClearScoreTime($param);
        $param['next_clear_time'] = $next_clear_time;

        //允许积分余额支付的商家
        if($param['pay_merchants'] && count($param['pay_merchants'])){
            $param['pay_merchants'] = implode(',', $param['pay_merchants']);
        }else{
            $param['pay_merchants'] = '';
        }

        $store=$param['store'];
        $pay_store = $param['pay_store'];
        unset($param['pay_store']);
        if(empty($param['card_id'])){//添加
            $card_id=$param['card_id'];
            unset($param['card_id']);
            $param['add_time']=time();
            unset($param['store']);
            $ret=(new EmployeeCard())->add($param);
            $card_id = $ret;
            if(!empty($store)){
                foreach ($store as $k=>$v){
                    $data['card_id']=$card_id;
                    $data['store_id']=$v;
                    $data['mer_id']=$param['mer_id'];
                    (new EmployeeCardStore())->add($data);
                }
            }
        }else{//编辑
            $card_id = $param['card_id'];
            $where=[['card_id','=',$param['card_id']]];
            $param['last_time']=time();
            unset($param['store']);
            $ret=(new EmployeeCard())->updateThis($where,$param);
            if($ret!==false){
                $ret=$param['card_id'];
                (new EmployeeCardStore())->delData(['mer_id'=>$param['mer_id']]);
                if(!empty($store)){
                    foreach ($store as $k=>$v){
                         $data['card_id']=$param['card_id'];
                         $data['store_id']=$v;
                         $data['mer_id']=$param['mer_id'];
                        (new EmployeeCardStore())->add($data);
                    }
                }
            }else{
                $ret=false;
            }
        }

        $employeeCardPayStoreModel = new EmployeeCardPayStore();
        $employeeCardPayStoreModel->delData(['mer_id'=>$param['mer_id'], 'card_id'=>$card_id]);
        $insertAllData = [];
        foreach($pay_store as $store_id){
            $saveData = [];
            $saveData['card_id'] = $card_id;
            $saveData['mer_id'] = $param['mer_id'];
            $saveData['store_id'] = $store_id;
            $insertAllData[] = $saveData;
        }
        $employeeCardPayStoreModel->insertAll($insertAllData);

        return $ret;
    }

    /**
     * 优惠券保存
     */
    public function saveCoupon($param)
    {
        if($param['coupon_price'] < $param['money']){
            throw new \think\Exception('优惠券金额必须大于核销时扣除的余额！');
        }
        if(count($param['label_ids']) > 0){
            $param['label_ids'] = implode(',', $param['label_ids']);
        }else{
            $param['label_ids'] = '';
        }
        if(empty($param['overdue_time'])){
            throw new \think\Exception('请选择消费券过期转积分时间！');
        }
        if($param['overdue_time'] < $param['end_time']){
            throw new \think\Exception('消费券过期转积分时间不能早于消费券结束时间！');
        } 

        //未修改不做处理
        if(empty($param['send_dates']) && empty($param['send_week']) && empty($param['clickDates'])){
            unset($param['send_rule'], $param['other_date']);
        }else{

            if($param['send_by'] == 2){
                $param['send_rule'] = !empty($param['send_dates']) ? $param['send_dates'] : $param['send_rule'];
            }
            if($param['send_by'] == 1){
                $param['send_rule'] = !empty($param['send_week']) ? $param['send_week'] : $param['send_rule'];
            }
            


            if(count($param['send_rule']) > 0 && $param['send_by'] != 0){
                $param['send_rule'] = implode(',', $param['send_rule']);
            }else{
                $param['send_rule'] = '';
            }

            $otherDate = $param['clickDates'] ?? $param['other_date'];
         
    
            if(!empty($otherDate)){
                $param['other_date'] = json_encode($otherDate);
            }else{
                $param['other_date'] = '';
            }

        }
        unset($param['send_dates'], $param['send_week'], $param['clickDates']);
 

        if(empty($param['pigcms_id'])){//添加
            unset($param['pigcms_id']);
            $param['add_time']=time();
            $ret=(new EmployeeCardCoupon())->add($param);
        }else{//编辑
            $where=[['pigcms_id','=',$param['pigcms_id']]];
            $param['last_time']=time();
            $ret=(new EmployeeCardCoupon())->updateThis($where,$param);
            if($ret!==false){
                $ret=true;
            }else{
                $ret=false;
            }
        }
        return $ret;
    }
    /**
     * 优惠券删除
     */
    public function delCoupon($param)
    {
        $where=[['pigcms_id','=',$param['pigcms_id']]];
        $ret=(new EmployeeCardCoupon())->delCard($where);
        return $ret;
    }

    /**
     *获取一条条数据
     * @param array $where 
     * @return array
     */
    public function getOne($where){
        $result = (new EmployeeCard)->getOne($where);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     * 消息列表
     */
    public function cardLog($params)
    {  
        if(empty($params['uid'])){
            throw new \think\Exception('请先登录!');
        }
        if(empty($params['card_id'])){
            throw new \think\Exception('card_id不能为空!');
        }
 
        $condition = []; 
        $condition[] = ['card_id', '=', $params['card_id']];
        $condition[] = ['status', '=', 1];

        $Card = $this->employeeCardModel->where($condition)->find();
        if(!$Card){
            throw new \think\Exception('员工卡不存在或已被禁用!');
        }
         
        $condition = []; 
        $condition[] = ['uid', '=', $params['uid']];
        $condition[] = ['card_id', '=', $params['card_id']];
        if(!empty($params['type'])){
            $condition[] = ['type', '=', $params['type']];
        }

        $data = [];
        $todayTime = strtotime(date('Y-m-d') . '00:00:00');
        $LogList = $this->employeeCardLogModel
            ->with(['merchant'])
            ->where($condition)
            ->order('add_time DESC')
            ->paginate($params['page_size'])
            ->each(function($item, $key) use(&$data, $todayTime){
                $temp = [];
                $temp['name'] = $item->merchant->name;
                $temp['message'] = $item->user_desc; 
                $temp['time'] = $item->add_time < $todayTime ? date('Y-m-d H:i', $item->add_time) : date('H:i', $item->add_time);
                
                if($item->type == 'money' || ($item->type == 'coupon' && $item->change_type == 'decrease') || ($item->type == 'score' && $item->change_type == 'decrease')){
                    $temp['image'] = cfg('site_url').'/v20/public/static/employee/consumption.png';
                }else{
                    $temp['image'] = cfg('site_url').'/v20/public/static/employee/overdue.png';
                } 
                $data[] = $temp; 
            })->toArray();
        $returnArr = [];
        $returnArr['current_page'] = $LogList['current_page'];
        $returnArr['last_page'] = $LogList['last_page'];
        $returnArr['per_page'] = $LogList['per_page'];
        $returnArr['total'] = $LogList['total'];
        $returnArr['data'] = $data; 
        return $returnArr;
    }

    /**
     * 会员权益
     */
    public function cardSpecial($params)
    {
        if(empty($params['uid'])){
            throw new \think\Exception('请先登录！');
        }
        if(empty($params['card_id'])){
            throw new \think\Exception('card_id不能为空!');
        }
        $condition = []; 
        $condition[] = ['card_id', '=', $params['card_id']];
        $condition[] = ['status', '=', 1];

        $Card = $this->employeeCardModel->field(['description'])->where($condition)->find();
        if(!$Card){
            throw new \think\Exception('员工卡不存在或已被禁用!');
        }
        return $Card;
    }

    /**
     * 个人信息
     */
    public function cardUserInfo($params)
    {
        if(empty($params['uid'])){
            throw new \think\Exception('请先登录！');
        }
        if(empty($params['card_id'])){
            throw new \think\Exception('card_id不能为空!');
        }

        $condition = [];
        $condition[] = ['uid', '=', $params['uid']];
        $condition[] = ['card_id', '=', $params['card_id']];
        
        $with = [];
        $with['card'] = function($query){
            $query->where('status', 1);
        }; 

        $with['user'] = function(\think\model\Relation $query){
            $query->field(['uid','avatar','sex','birthday','phone'])->where('status', 1);
        }; 
        $User = $this->employeeCardUserModel
                ->with($with) 
                ->where($condition)
                ->find();
        if(empty($User) || !$User->card || !$User->user){
            throw new \think\Exception('用户不存在或已被禁用！');
        }

        $data = [];
        $data['avatar'] = $User->user->avatar ? replace_file_domain($User->user->avatar) : cfg('site_url') . '/static/images/user_avatar.jpg';
        $data['name'] = $User->name;
        $data['department'] = $User->department;
        $data['sex'] = in_array($User->user->sex, [0, 1, 2]) ? $User->user->sex : 0;
        $sexMap = ['保密', '男性', '女性'];
        $data['sex_text'] = $sexMap[$User->user->sex] ?? '保密';
        $data['age'] = $this->getAgeByBirth($User->user->birthday);
        $data['phone'] = $User->user->phone;
        $data['card_num'] = $User->card_number;
        $data['position'] = $User->identity;

        return $data; 
    }


    function getAgeByBirth($birthday)
    {
        if(empty($birthday)){
            return 0;
        }

        if(substr_count($birthday, '-') !== 2){
            return 0;
        }

        list($birth_year, $birth_month, $birth_day) = explode('-', $birthday);

        if(empty($birth_year) || empty($birth_month) || empty($birth_day)){
            return 0;
        } 

        $current_year = date('Y',time()); 
        $current_month = date('m',time()); 
        $current_day = date('d',time());
      
        if($birth_year >= $current_year){ 
            return 0; 
        }
      
        $age = $current_year - $birth_year - 1; 

        if($current_month > $birth_month){  
            return $age+1; 
        }else if($current_month == $birth_month && $current_day >= $birth_day){ 
            return $age+1; 
        }else{    
            return $age; 
        } 
      }

      
    /**
     * 核销列表 （店员后台）
     */
    public function cardLogList($params)
    { 
        $condition = [];
        $condition[] = ['mer_id', '=', $params['mer_id']];
        if(!empty($params['staff_id']) && $params['staff']['type'] != 2){
            $condition[] = ['operate_id', '=', $params['staff_id']];
        }
        if(!empty($params['type']) && in_array($params['type'], ['coupon', 'score', 'money'])){
            $condition[] = ['type', '=', $params['type']];
            $condition[] = ['change_type', '=', 'success'];
        }
        if(!empty($params['start_date'])){
            $condition[] = ['add_time', '>=', strtotime($params['start_date'] . ' 00:00:00')];
        }
        if(!empty($params['end_date'])){
            $condition[] = ['add_time', '<=', strtotime($params['end_date'] . ' 23:59:59')];
        }
        if(!empty($params['keywords']) && in_array($params['search_by'], [1, 2, 3, 4, 5, 6])){
            switch ($params['search_by']) {
                case 1: //员工卡名称
                    $conditionCard = [];
                    $conditionCard[] = ['name', 'like', '%'. $params['keywords'] . '%'];
                    $card_ids = $this->employeeCardModel->where($conditionCard)->column('card_id');
                    $condition[] = ['card_id', 'in', $card_ids];
                    unset($conditionCard);
                    break;
                case 2: //员工姓名
                    $conditionCardUser = [];
                    $conditionCardUser[] = ['name', 'like', '%'. $params['keywords'] . '%'];
                    $user_ids = $this->employeeCardUserModel->where($conditionCardUser)->column('user_id');
                    $condition[] = ['user_id', 'in', $user_ids];
                    unset($conditionCardUser);
                    break;
                case 3: //员工电话
                    $conditionUser = [];
                    $conditionUser[] = ['phone', 'like', '%'. $params['keywords'] . '%'];
                    $uids = $this->userModel->where($conditionUser)->column('uid');
                    $condition[] = ['uid', 'in', $uids];
                    unset($conditionUser);
                    break;
                case 4: //消费券名称
                    $conditionCoupon = [];
                    $conditionCoupon[] = ['name', 'like', '%'. $params['keywords'] . '%'];
                    $coupon_ids = $this->employeeCardCouponModel->where($conditionCoupon)->column('pigcms_id');
                    $conditionCouponSend = [];
                    $conditionCouponSend[] = ['coupon_id', 'in', $coupon_ids];
                    $conditionCouponSend[] = ['mer_id', '=', $params['mer_id']];
                    $coupon_send_ids = $this->employeeCardCouponSendModel->where($conditionCouponSend)->column('pigcms_id');
                    $condition[] = ['coupon_id', 'in', $coupon_send_ids];
                    unset($conditionCoupon, $conditionCouponSend, $coupon_send_ids, $coupon_ids);
                    break;
                case 5: //会员身份
                    $conditionCardUser = [];
                    $conditionCardUser[] = ['identity', 'like', '%'. $params['keywords'] . '%'];
                    $user_ids = $this->employeeCardUserModel->where($conditionCardUser)->column('user_id');
                    $condition[] = ['user_id', 'in', $user_ids];
                    unset($conditionCardUser);
                    break;
                case 6: //会员部门
                    $conditionCardUser = [];
                    $conditionCardUser[] = ['department', 'like', '%'. $params['keywords'] . '%'];
                    $user_ids = $this->employeeCardUserModel->where($conditionCardUser)->column('user_id');
                    $condition[] = ['user_id', 'in', $user_ids];
                    unset($conditionCardUser);
                    break;
            }
        }


        $with = [];
        $with['card'] = function($query){
            $query->field(['card_id', 'name']);
        };
        $with['user'] = function($query){
            $query->field(['uid', 'nickname', 'phone']);
        };

        // $with['card_user'] = function($query){
        //     $query->field(['user_id', 'card_number', 'name', 'identity', 'department']);
        // };

        $with['coupon_send'] = function($query){
            $query->field(['pigcms_id', 'coupon_id'])
            ->with(['coupon' => function($query){
                $query->field(['pigcms_id', 'name'])->bind(['coupon_name'=>'name']);
            }])->bind(['coupon_name']); 
        };

        $with['staff'] = function($query){
            $query->field(['id', 'name']);
        };

        return $this->employeeCardLogModel
                    ->with($with)
                    ->where($condition)
                    ->order('add_time desc')
                    ->paginate($params['page_size'])
                    ->each(function($item, $key){
                        $card_user = (object)[];
                        $card_user->name = $item->user_name ?? '';
                        $card_user->identity = $item->identity ?? '';
                        $card_user->department = $item->department ?? '';
                        $card_user->card_number = $item->card_number ?? '';
                        $item->card_user = $card_user;
                    })
                    // ->visible(['pigcms_id','coupon_name', 'coupon_price', 'grant_price', 'num', 'remark', 'card_user'])
                    ->append(['create_time']);
                     
    }

    /**
     * 核销列表新版,(商家后台)
     */
    public function cardLogListNew($params)
    { 
        $condition = [];
        $condition[] = ['mer_id', '=', $params['mer_id']];
        if(!empty($params['staff_id'])){
            $condition[] = ['operate_id', '=', $params['staff_id']];
        }
        if(!empty($params['type']) && in_array($params['type'], ['coupon', 'score', 'overdue', 'money', 'to_score'])){
            if($params['type'] == 'coupon'){
                if(!empty($params['verify_type'])){
                    $verify_type_map = [
                        1   =>  'coupon',
                        2   =>  'overdue',
                        3   =>  'to_score'
                    ];
                    $condition[] = ['type', '=', $verify_type_map[$params['verify_type']] ?? 'coupon'];
                    $condition[] = ['change_type', '=', 'success'];
                    switch($params['verify_type']){
                        case 1://正常核销
                            $condition[] = ['log_type', '=', 1];
                            break;
                        case 2://自动转积分
                            $condition[] = ['log_type', '=', 6];
                            break;
                        case 3://手动转积分
                            $condition[] = ['log_type', '=', 7];
                            break;
                    }
                     
                    // $condition[] = ['type', '=', $verify_type_map[$params['verify_type']] ?? 'coupon'];
                }else{
                    $condition[] = ['type', 'in', ['coupon', 'overdue', 'to_score']];
                    $condition[] = ['change_type', '=', 'success'];
                }
            }else if($params['type'] == 'overdue'){
                $condition[] = ['type', '=', 'overdue'];
                $condition[] = ['change_type', '=', 'error'];
            }else if($params['type'] == 'score'){
                $condition[] = ['type', '=', $params['type']]; 
                $condition[] = ['change_type', '=', 'success'];
            }else if($params['type'] == 'money'){
                $condition[] = ['type', '=', $params['type']];
                $condition[] = ['change_type', '=', 'success'];
            }
        }
        if(!empty($params['start_date'])){
            $condition[] = ['add_time', '>=', strtotime($params['start_date'] . ' 00:00:00')];
        }
        if(!empty($params['end_date'])){
            $condition[] = ['add_time', '<=', strtotime($params['end_date'] . ' 23:59:59')];
        }
        if(!empty($params['keywords']) && in_array($params['search_by'], [1, 2, 3, 4, 5, 6])){
            switch ($params['search_by']) {
                case 1: //员工卡名称
                    $conditionCard = [];
                    $conditionCard[] = ['name', 'like', '%'. $params['keywords'] . '%'];
                    $card_ids = $this->employeeCardModel->where($conditionCard)->column('card_id');
                    $condition[] = ['card_id', 'in', $card_ids];
                    unset($conditionCard);
                    break;
                case 2: //员工姓名
                    $conditionCardUser = [];
                    $conditionCardUser[] = ['name', 'like', '%'. $params['keywords'] . '%'];
                    $user_ids = $this->employeeCardUserModel->where($conditionCardUser)->column('user_id');
                    $condition[] = ['user_id', 'in', $user_ids];
                    unset($conditionCardUser);
                    break;
                case 3: //员工电话
                    $conditionUser = [];
                    $conditionUser[] = ['phone', 'like', '%'. $params['keywords'] . '%'];
                    $uids = $this->userModel->where($conditionUser)->column('uid');
                    $condition[] = ['uid', 'in', $uids];
                    unset($conditionUser);
                    break;
                case 4: //消费券名称
                    $conditionCoupon = [];
                    $conditionCoupon[] = ['name', 'like', '%'. $params['keywords'] . '%'];
                    $coupon_ids = $this->employeeCardCouponModel->where($conditionCoupon)->column('pigcms_id');
                    $conditionCouponSend = [];
                    $conditionCouponSend[] = ['coupon_id', 'in', $coupon_ids];
                    $conditionCouponSend[] = ['mer_id', '=', $params['mer_id']];
                    $coupon_send_ids = $this->employeeCardCouponSendModel->where($conditionCouponSend)->column('pigcms_id');
                    $condition[] = ['coupon_id', 'in', $coupon_send_ids];
                    unset($conditionCoupon, $conditionCouponSend, $coupon_send_ids, $coupon_ids);
                    break;
                case 5: //会员身份
                    $conditionCardUser = [];
                    $conditionCardUser[] = ['identity', 'like', '%'. $params['keywords'] . '%'];
                    $user_ids = $this->employeeCardUserModel->where($conditionCardUser)->column('user_id');
                    $condition[] = ['user_id', 'in', $user_ids];
                    unset($conditionCardUser);
                    break;
                case 6: //会员部门
                    $conditionCardUser = [];
                    $conditionCardUser[] = ['department', 'like', '%'. $params['keywords'] . '%'];
                    $user_ids = $this->employeeCardUserModel->where($conditionCardUser)->column('user_id');
                    $condition[] = ['user_id', 'in', $user_ids];
                    unset($conditionCardUser);
                    break;
            }
        }


        $with = [];
        $with['card'] = function($query){
            $query->field(['card_id', 'name']);
        };
        $with['user'] = function($query){
            $query->field(['uid', 'nickname', 'phone']);
        };

        // $with['card_user'] = function($query){
        //     $query->field(['user_id', 'card_number', 'name', 'identity', 'department']);
        // };

        $with['coupon_send'] = function($query){
            $query->field(['pigcms_id', 'coupon_id'])
            ->with(['coupon' => function($query){
                $query->field(['pigcms_id', 'name'])->bind(['coupon_name'=>'name']);
            }])->bind(['coupon_name']); 
        };

        $with['store'] = function($query){
            $query->field(['store_id', 'name']);
        };

        $data = $this->employeeCardLogModel
                    ->with($with)
                    ->where($condition)
                    ->order('add_time desc');
                   
        if(!empty($params['request_type'])){
            $data =  $data->select()->append(['create_time']);
        }else{
            $data =  $data->paginate($params['page_size'])->append(['create_time']);
        }
        $data->each(function($item, $key){
            $card_user = (object)[];
            $card_user->name = $item->user_name ?? '';
            $card_user->identity = $item->identity ?? '';
            $card_user->department = $item->department ?? '';
            $card_user->card_number = $item->card_number ?? '';
            $item->card_user = $card_user;
        });
        return $data;
    }

    /**
     * 获取用户协议
     */
    public function getCardUserAgreement($params)
    {
        if(empty($params['uid'])){
            throw new \think\Exception('请先登录！');
        }

        if(empty($params['card_id'])){
            throw new \think\Exception('card_id不能为空！');
        }
         
        $condition = [];
        $condition[] = ['card_id', '=', $params['card_id']];
        $condition[] = ['uid', '=', $params['uid']];
        $condition[] = ['status', '=', 1];
        $cardUser = $this->employeeCardUserModel->where($condition)->find();
        if(!$cardUser){
            throw new \think\Exception('用户不存在或已被禁用！');
        }

        $condition = [];
        $condition[] = ['card_id', '=', $params['card_id']];
        $condition[] = ['status', '=', 1];
        $card = $this->employeeCardModel->where($condition)->find();
        if(!$card){
            throw new \think\Exception('员工卡不存在或已被停用！');
        }
        $returnArr = [];
        $returnArr['user_agreement'] = $card->user_agreement;
        $returnArr['description'] = $card->description;
        $returnArr['is_agree'] = $cardUser->agree_user_agreement;
        $returnArr['uid'] = $cardUser->uid;
        $returnArr['user_id'] = $cardUser->user_id;
        return $returnArr;
    }

    /**
     * 同意/不同意用户协议
     */
    public function agreeCardUserAgreement($params)
    {
        if(empty($params['uid'])){
            throw new \think\Exception('请先登录！');
        }

        if(empty($params['card_id'])){
            throw new \think\Exception('card_id不能为空！');
        }

        if(!in_array($params['type'], [1, 2])){
            throw new \think\Exception('type不正确！');
        } 

        $condition = [];
        $condition[] = ['card_id', '=', $params['card_id']];
        $condition[] = ['uid', '=', $params['uid']];
        $condition[] = ['status', '=', 1];
        $cardUser = $this->employeeCardUserModel->where($condition)->find();
        if(!$cardUser){
            throw new \think\Exception('用户不存在或已被禁用！');
        }
        $cardUser->agree_user_agreement = $params['type'];
        return $cardUser->save();
    }

    /**
     * 是否开启使用余额消费
     */
    public function isOpenUseMoney($params)
    {
        if(!in_array($params['status'], [0, 1])){
            throw new \think\Exception('status参数错误！');
        }
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
        $coupon = $this->employeeCardCouponModel->where($condition)->find();
        if(!$coupon){
            throw new \think\Exception('消费券不存在！');
        }
        if($coupon->mer_id != $params['mer_id']){
            throw new \think\Exception('无权操作！');
        }
        //开启时验证
        if($params['status'] == 1 && $coupon->is_default == 0){

            $condition = [];
            $condition[] = ['mer_id', '=', $params['mer_id']];
            $condition[] = ['is_default', '=', 1];
            $condition[] = ['pigcms_id', '<>', $coupon->pigcms_id];
             
            if($this->employeeCardCouponModel->isOverlap($coupon)){
                throw new \think\Exception('已存在时间重叠的消费券！');
            }
        }
        $coupon->is_default = $params['status'];
        return $coupon->save();
    } 

    /**
     * 获取发券日期
     */
    public function getSendCouponDateList($params)
    {
        $time = $params['time'] ?: strtotime(date('Ymd'));

        if(empty($params['pigcms_id'])){
            return $this->getDateList($time);
        } 
        
        $coupon = $this->employeeCardCouponModel->where('pigcms_id', $params['pigcms_id'])->find();
        if(!$coupon){
            throw new \think\Exception('消费券不存在！');
        }

        $otherData = [];

        //发券日期
        if($coupon->other_data != ''){
            $otherData = json_decode($coupon->other_data, true);
        }  
        
        $sendRule = [];
        if($coupon->send_rule != ''){
            $sendRule = explode(',', $coupon->send_rule);
        }  
        
        return $this->getCalcDateList($time, $coupon->send_by, $sendRule, $otherData);
       
    }

 
    public function getCalcDateList($time, $type, $sendRule = [], $otherData = [])
    {
        if($type != 2 && empty($time)){
            throw new \think\Exception('time不能为空！');
        }
        if($type != 0 && empty($sendRule)){
            return [];
        }
        if(empty($sendRule)){
            $sendRule = [];
        }
        $dateList = $this->getDateList($time);
        
        //每天发券
        if($type == 0){
            return $this->formatDateList($dateList, $otherData);
        }

        //按周发券
        if($type == 1){
            $newDataList = [];
            foreach($dateList as $val){
                if(in_array(date('w', $val), $sendRule)){
                    $newDataList[] = $val;
                }  
            }
            return $this->formatDateList($newDataList, $otherData);
        }

         //按时间段发券
         if($type == 2){
            $dateList = $this->getDateList($sendRule[0] ?? 0, $sendRule[1] ?? 0);
            return $this->formatDateList($dateList, $otherData);
        }
    }

    /**
     * 生成日期列表
     */
    private function getDateList($time, $endtime = null)
    {
        $dateList = []; 
        if(is_null($endtime)){
            $m = date('m', $time);
            $y = date('Y', $time); 
            for($i = -7;$i <= 41; $i ++){
                $dateList[] = mktime(0, 0, 0, $m, $i, $y);
            }
        }else{
            if(empty($time) || empty($endtime)){
                return [];
            }
            $nowTime = strtotime($time);
            $end_time = strtotime($endtime);
 
            while($end_time >= $nowTime){
                $dateList[] = $nowTime;
                $nowTime += 86400;
            }
        }
       
        return $dateList;
    }

 
    private function formatDateList($dateList, $otherData = [])
    {
        if(count($otherData) == 0){
            return $dateList;
        }
        $notSendDate = [];
        foreach($otherData as $key => $val){
            if($val == 1){
                $dateList[] = $key;
            }else{
                $notSendDate[] = $key;
            }
        }
        foreach($dateList as &$val){
            if(in_array($val, $notSendDate)){
                unset($val);
            }
        }
        return $dateList;
    }


   /**
     * 获取积分清零列表
     */
    public function getClearScoreList($params)
    {
        $condition = [];
        $condition[] = ['mer_id', '=', $params['mer_id']];

        if(!empty($params['start_date']) && !empty($params['end_date'])){
            $condition[] = ['add_time', 'between', [strtotime($params['start_date'] . ' 00:00:00'), strtotime($params['end_date'] . ' 23:59:59')]];
        }


        $list = $this->employeeCardClearScoreModel
            ->with(['user'=>function($query){
                $query->field(['uid', 'phone']);
            }])->where($condition)
            ->withAttr('create_time', function($value, $data){
                return date('Y-m-d H:i', $data['add_time']);
            })
            ->order('add_time DESC')
            ->paginate($params['page_size'])
            ->append(['create_time'])
            ->toArray();
        $list['total_score'] = $this->employeeCardClearScoreModel->where($condition)->sum('clear_score');
        return $list;
    }


    /**
     * 充值金额选择
     */
    public function getRechargeSelectList()
    {
        $data = [
            ['money'    =>  10, 'title'  =>  '10', 'desc'     => '10'],
            ['money'    =>  15, 'title'  =>  '15', 'desc'     => '15'],
            ['money'    =>  20, 'title'  =>  '20', 'desc'    => '20'],
            ['money'    =>  50, 'title'  =>  '50', 'desc'    => '50'],
            ['money'    =>  100, 'title'  =>  '100', 'desc'    => '100'],
            ['money'    =>  200, 'title'  =>  '200', 'desc'    => '200'],
            ['money'    =>  500, 'title'  =>  '500', 'desc'    => '500']
        ];
        return $data;
    }


}