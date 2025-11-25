<?php
namespace app\employee\model\service;

use app\common\model\db\MerchantStore;
use app\common\model\db\User;
use app\common\model\service\weixin\TemplateNewsService;
use app\employee\model\db\EmployeeCard;
use app\employee\model\db\EmployeeCardLable;
use app\employee\model\db\EmployeeCardLog;
use app\employee\model\db\EmployeeCardOrder;
use app\employee\model\db\EmployeeCardUser;
use PhpOffice\PhpSpreadsheet\IOFactory;
use app\common\model\service\export\ExportService as BaseExportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\facade\Db;
use think\Exception;
use think\Model;

class EmployeeCardUserService
{

    public $employeeCardUserModel = null;
    public function __construct()
    {
        $this->employeeCardUserModel = new EmployeeCardUser();
    }
    /**
     * 会员卡用户列表
     */
    public function getUserCardList($params)
    {
        $where=[['g.mer_id','=',$params['mer_id']]];
        if($params['type']==0 && !empty($params['content'])){
            array_push($where,['u.phone','=',$params['content']]);
        }elseif($params['type']==1 && !empty($params['content'])){
            array_push($where,['g.name','like','%'.$params['content'].'%']);
        }elseif($params['type']==2 && !empty($params['content'])){
            array_push($where,['g.card_number','=',$params['content']]);
        }elseif($params['type']==3 && !empty($params['content'])){
            array_push($where,['g.department','like',"%{$params['content']}%"]);
        }elseif($params['type']==4 && !empty($params['content'])){
            array_push($where,['g.identity','like',"%{$params['content']}%"]);
        }elseif($params['type']==5 && !empty($params['content'])){
            $sql1 = "(select ecl.id from pigcms_employee_card_lable as ecl where ecl.name like '%" . $params['content'] . "%' AND ecl.mer_id = " . $params['mer_id'] . " AND ecl.is_del = 0)";
            $sql  = "concat(',',g.lable_ids,',') regexp concat(',(',replace(" . $sql1 . ",',','|'),'),')";
            $where[] = ['', 'exp', Db::raw($sql)];
        }
        $list = (new EmployeeCardUser())->getUserCardList($where,'g.*,u.phone','g.user_id desc');
        if (!empty($list['list'])) {
            foreach ($list['list'] as $k => $v) {
                $lableNmaes = (new EmployeeCardLable())->where([['id', 'in', $v['lable_ids']],['mer_id', '=', $params['mer_id']]])->column('name');
                $list['list'][$k]['lables'] = implode(', ', $lableNmaes);
            }
        }
        $list['card_id'] = (new EmployeeCard())->getVal(['mer_id'=>$params['mer_id']],'card_id');
        $list['all_card_money'] = (new EmployeeCardUser())->getUserCardListSum($where, 'g.card_money');
        $list['all_card_score'] = (new EmployeeCardUser())->getUserCardListSum($where, 'g.card_score');
        return $list;
    }
    /**
     * 消费记录
     */
    public function orderList($params)
    {
        $where=[];
        if(!empty($params['card_id']) && !empty($params['user_id'])){
            $where=[['g.card_id','=',$params['card_id']],['g.user_id','=',$params['user_id']]];
            if(isset($params['mer_id']) && !empty($params['mer_id'])){
                array_push($where,['g.mer_id','=',$params['mer_id']]);
            }
        }

        if(!empty($params['verify_type'])){
            switch($params['verify_type']){
                case 1: //消费券核销
                    array_push($where,['g.type','=', 'coupon']);
                    break;
                case 2: //自动核销
                    array_push($where,['g.type','=', 'overdue']);
                    break;
                case 3: //余额消费
                    array_push($where,['g.type','=', 'money']);
                    break;
                case 4: //积分消费
                    array_push($where,['g.type','=', 'score']);
                    break;
            }
        }else if(isset($params['type']) && !empty($params['type'])){
            array_push($where,['g.type','=',$params['type']]);
        }

        $whereOr = [];
        if(!empty($params['change_type'])){
            switch($params['change_type']){
                case 1: //增加
                    array_push($where,['g.change_type','=', 'increase']);
                    break;
                case 2: //减少
                    $whereOr[] = ['g.change_type', '=', 'success'];
                    $whereOr[] = ['g.change_type', '=', 'decrease'];
                    break;
                
            }
        }

        if(isset($params['start_time']) && !empty($params['start_time']) && !empty($params['end_time'])){
            array_push($where,['g.add_time','>=',strtotime($params['start_time']." 00:00:00")]);
            array_push($where,['g.add_time','<',strtotime($params['end_time']." 23:59:59")]);
        }

        if (!empty($params['s_time']) && !empty($params['e_time'])) {
            array_push($where, ['g.add_time', '>=', strtotime($params['s_time'])]);
            array_push($where, ['g.add_time', '<', strtotime($params['e_time']) + 60]);
        }

        $list=(new EmployeeCardLog())->orderList($where,'g.*,cou.name as coupon_name','pigcms_id desc',$params['page'],$params['pageSize'], $whereOr);
        if(!empty($list['list'])){
            foreach ($list['list'] as &$value){
                if(empty($value['add_time'])){
                    $value['add_time']=0;
                }else{
                    $value['add_time']=date('Y.m.d H:i:s',$value['add_time']);
                }
                switch($value['type']){
                    case 'coupon':
                        $value['verify_type'] = 1;
                        $value['description'] = $value['coupon_name'] ?? $value['description'];
                        break;
                    case 'overdue':
                        $value['verify_type'] = 2;
                        break;
                    case 'money':
                        $value['verify_type'] = 3;
                        break;
                    case 'score':
                        $value['verify_type'] = 4;
                        break;
                }
            }
        }
        return $list;
    }
    /**
     * 员工卡编辑
     */
    public function editCardUser($param)
    {
        $where = [['g.mer_id','=',$param['mer_id']],['g.user_id','=',$param['user_id']]];
        $ret['user'] = (new EmployeeCardUser())->editCardUser($where,'g.*,u.phone');
        if (!empty($ret['user']['lable_ids'])) {
            $ret['user']['lable_ids'] = explode(',', $ret['user']['lable_ids']);
            foreach ($ret['user']['lable_ids'] as $k => $v) {
                $ret['user']['lable_ids'][$k] = intval($v);
            }
        } else {
            unset($ret['user']['lable_ids']);
        }
        return $ret;
    }

    /**
     * 员工卡删除
     */
    public function delCardUser($params)
    {
        if(!is_array($params['user_ids'])){
            throw new \think\Exception("参数有误！");
        }
        $where = [];
        $where[] = ['user_id', 'in', $params['user_ids']];
        $where[] = ['mer_id', '=', $params['mer_id']];
        if ((new EmployeeCardUser())->where($where)->sum('card_money') > 0) {
            throw new \think\Exception("所选数据存在余额大于0的员工卡，请检查！");
        }
        $ret = (new EmployeeCardUser())->delData($where);
        return true;
    }

    /**
     * 员工卡开启
     */
    public function openUserCard($params)
    {
        if(!is_array($params['user_ids'])){
            throw new \think\Exception("参数有误！");
        }
        if (empty((new EmployeeCardUser())->getOne([['user_id', 'in', $params['user_ids']], ['status', '=', 0]]))) {
            throw new \think\Exception("选中数据中没有未开启的员工卡");
        }
        $where = [
            ['user_id', 'in', $params['user_ids']],
            ['status', '=', 0],
            ['mer_id', '=', $params['mer_id']]
        ];
        (new EmployeeCardUser())->updateThis($where, ['status' => 1, 'last_time' => time()]);
        return true;
    }

    /**
     * 员工卡关闭
     */
    public function closeUserCard($params)
    {
        if(!is_array($params['user_ids'])){
            throw new \think\Exception("参数有误！");
        }
        if (empty((new EmployeeCardUser())->getOne([['user_id', 'in', $params['user_ids']], ['status', '=', 1]]))) {
            throw new \think\Exception("选中数据中没有已开启的员工卡");
        }
        $where = [
            ['user_id', 'in', $params['user_ids']],
            ['status', '=', 1],
            ['mer_id', '=', $params['mer_id']]
        ];
        (new EmployeeCardUser())->updateThis($where, ['status' => 0, 'last_time' => time()]);
        return true;
    }

    /**
     * 员工卡保存
     */
    public function saveCardUser($param)
    {
        if (!empty($param['lable_ids'])) {
            $param['lable_ids'] = implode(',', $param['lable_ids']);
        } else {
            $param['lable_ids'] = '';
        }
        if(empty($param['user_id'])){//添加
            $card_number=(new EmployeeCardUser())->getOne(['mer_id'=>$param['mer_id'],'card_number'=>$param['card_number']]);
            if(!empty($card_number)){
                $ret['error']=1;
                $ret['msg']='此卡号已经存在，请添加其他卡号';
                return $ret;
            }
            unset($param['user_id']);
            $param['add_time']=time();
            if(empty($param['uid'])){
                $data['phone']=$param['phone'];
                $data['nickname']=$param['name'];
                $data['status']=1;
                $param['uid']=(new User())->add($data);
            }
            unset($param['phone']);
            if(empty($param['uid'])){
                $ret['error']=1;
                $ret['msg']='获取会员信息失败,请重新添加!';
                return $ret;
            }
            (new EmployeeCardUser())->add($param);
            $ret['error']=0;
            $ret['msg']='成功';
        }else{//编辑
            $where=[['user_id','=',$param['user_id']]];
            $param['last_time']=time();
            if(empty($param['uid'])){
                $data['phone']=$param['phone'];
                $data['nickname']=$param['name'];
                $data['status']=1;
                $param['uid']=(new User())->add($data);
            }
            unset($param['phone']);
            $card=(new EmployeeCardUser())->editCardUser($where,'g.*');
            if(!empty($card)){
                $model = new TemplateNewsService();
                $nowUser=(new User())->getUserMsg(['uid'=>$param['uid']]);
                $user = (new User())->where('uid', $card['uid'])->find();
                $time = time();
                $card_money['uid']=$param['uid'];
                $card_money['card_id']=$card['card_id'];
                $card_money['user_id']=$card['user_id'];
                $card_money['mer_id']=$card['mer_id'];
                $card_money['operate_type']="merchant";
                $card_money['add_time']=$time;
                $isChangeMoney=false;
                if($card['card_money']>$param['card_money']*1){
                    $isChangeMoney=true;
                    $card_money['num']=$card['card_money']-$param['card_money']*1;
                    $card_money['type']="money";
                    $card_money['change_type']="decrease";
                    $card_money['description']="商家后台操作：商家减少余额：".$card_money['num'];
                    $card_money['user_desc']="商家后台操作：商家减少余额：".$card_money['num'];
                    //添加订单
                    $orderData = [];
                    $orderData['card_id'] = $card['card_id'];
                    $orderData['user_id'] = $card['user_id'];
                    $orderData['mer_id'] = $card['mer_id'];
                    $orderData['order_name'] = '商家后台操作：减少余额:' . $card_money['num'];
                    $orderData['uid'] = $card['uid'];
                    $orderData['real_orderid'] = build_real_orderid($card['uid']);// 订单长id
                    $orderData['nickname'] = $user->nickname ?? '';
                    $orderData['phone'] = $user->phone ?? '';
                    $orderData['total_price'] = $card_money['num'];
                    $orderData['price'] = $card_money['num'];
                    $orderData['pay_type'] = 'merchant';
                    // $orderData['paid'] = 1;
                    // $orderData['pay_time'] = $time;
                    $orderData['order_status'] = 110;
                    $orderData['add_time'] = $time;
                }

                if($card['card_money']<$param['card_money']*1){
                    $isChangeMoney=true;
                    $card_money['num']=($card['card_money']-$param['card_money']*1)*-1;
                    $card_money['type']="money";
                    $card_money['change_type']="increase";
                    $card_money['description']="商家后台操作：商家增加余额：".$card_money['num'];
                    $card_money['user_desc']="商家后台操作：商家增加余额：".$card_money['num'];
                     //添加订单
                    $orderData = [];
                    $orderData['card_id'] = $card['card_id'];
                    $orderData['user_id'] = $card['user_id'];
                    $orderData['mer_id'] = $card['mer_id'];
                    $orderData['order_name'] = '商家后台操作：增加余额:' . $card_money['num'];
                    $orderData['uid'] = $card['uid'];
                    $orderData['real_orderid'] = build_real_orderid($card['uid']);// 订单长id
                    $orderData['nickname'] = $user->nickname ?? '';
                    $orderData['phone'] = $user->phone ?? '';
                    $orderData['total_price'] = $card_money['num'];
                    $orderData['price'] = $card_money['num'];
                    $orderData['pay_type'] = 'merchant';
                    $orderData['paid'] = 1;
                    $orderData['pay_time'] = $time;
                    $orderData['order_status'] = 100;
                    $orderData['add_time'] = $time;
                }
                if($isChangeMoney){
                    (new EmployeeCardLog())->add($card_money);
                    (new EmployeeCardOrder())->add($orderData);
                    if (!empty($nowUser) && $nowUser['openid']) {
                        $href = "#";
                        $model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $nowUser['openid'],
                            'first' =>  L_('提醒'), 'keyword1' => $card['card_id'],
                            'keyword2' => $card['user_id'],
                            'keyword3' => "merchant",
                            'keyword4' => date('Y-m-d H:i:s'),
                            'remark' => $card_money['user_desc']), $card['mer_id']);
                    }
                }

                $isChangeScore=false;
                if($card['card_score']>$param['card_score']*1){
                    $isChangeScore=true;
                    $card_money['num']=$card['card_score']-$param['card_score']*1;
                    $card_money['type']="score";
                    $card_money['change_type']="decrease";
                    $card_money['description']="商家后台操作：商家减少积分：".$card_money['num'];
                    $card_money['user_desc']="商家后台操作：商家减少积分：".$card_money['num'];
                }

                if($card['card_score']<$param['card_score']*1){
                    $isChangeScore=true;
                    $card_money['num']=($card['card_score']-$param['card_score']*1)*-1;
                    $card_money['type']="score";
                    $card_money['change_type']="increase";
                    $card_money['description']="商家后台操作：商家增加积分：".$card_money['num'];
                    $card_money['user_desc']="商家后台操作：商家增加积分：".$card_money['num'];
                }
                if($isChangeScore){
                    (new EmployeeCardLog())->add($card_money);
                    if (!empty($nowUser) && $nowUser['openid']) {
                        $href = "#";
                        $model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $nowUser['openid'],
                            'first' =>  L_('提醒'), 'keyword1' => $card['card_id'],
                            'keyword2' => $card['user_id'],
                            'keyword3' => "merchant",
                            'keyword4' => date('Y-m-d H:i:s'),
                            'remark' => $card_money['user_desc']), $card['mer_id']);
                    }
                }
            }
            $ret1=(new EmployeeCardUser())->updateThis($where,$param);
            if($ret1!==false){
                $ret['error']=0;
                $ret['msg']='成功';
            }else{
                $ret['error']=1;
                $ret['msg']='保存失败';
            }
        }
        return $ret;
    }

    /**
     * 优惠券删除
     */
    public function delData($param)
    {
        $where = [['user_id', '=', $param['user_id']]];
        $ret = (new EmployeeCardUser())->delData($where);
        return $ret;
    }

    /**
     * 增加员工卡会员的余额
     * @param int $userId 会员卡id
     * @param string $money 增加金额
     * @param string $desc 描述
     * @param string $userDesc 用户展示的描述
     * @param string $operateType 操作类型
     * @param int $operateType 店员id
     * @param string $operateName 操作人名称
     * @return bool
     */
    public function addMoney($userId, $money, $desc, $userDesc = '', $operateType='user', $operateId = 0, $operateName = '') {

        if ($money <= 0) {            
            throw new \think\Exception(L_("充值金额有误"), 1003);
        }
            
        $conditionUser['user_id'] = $userId;
        $cardUser = $this->getOne($conditionUser);

        
        if ($this->employeeCardUserModel->setInc($conditionUser, 'card_money', $money)) {
            // 添加日志
            $logData = [
                'card_id' => $cardUser['card_id'],
                'user_id' => $cardUser['user_id'],
                'mer_id' => $cardUser['mer_id'],
                'uid' => $cardUser['uid'],
                'num' => $money,
                'type' => 'money',
                'change_type' => 'increase',
                'operate_id' => $operateId,
                'operate_type' => $operateType,
                'operate_name' => $operateName,
                'description' => $desc,
                'user_desc' => $userDesc,
            ];
            (new EmployeeCardLogService())->add($logData);
        } else {
            throw new \think\Exception(L_("余额充值失败！请联系管理员协助解决。"), 1003);
        }

        return true;
    }

       /**
     * 增加员工卡会员的积分
     * @param int $userId 会员卡id
     * @param string $score 增加积分
     * @param string $desc 描述
     * @param string $userDesc 用户展示的描述
     * @param string $operateType 操作类型
     * @param int $operateType 店员id
     * @param string $operateName 操作人名称
     * @return bool
     */
    public function addScore($userId, $score, $desc, $userDesc = '', $operateType='user', $operateId = 0, $operateName = '') 
    {
 
        $cardUser = $this->employeeCardUserModel->where('user_id', $userId)->find();

        if($score < 0 && $cardUser->card_score < $score * -1){
            throw new \think\Exception('用户积分不足，操作失败！');
        }

        $cardUser->card_score += $score;

        if($cardUser->save()){
            // 添加日志
            $logData = [
                'card_id' => $cardUser['card_id'],
                'user_id' => $cardUser['user_id'],
                'mer_id' => $cardUser['mer_id'],
                'uid' => $cardUser['uid'],
                'num' => $score < 0 ? $score * -1 : $score,
                'type' => 'score',
                'change_type' => $score >= 0 ? 'increase' : 'decrease',
                'operate_id' => $operateId,
                'operate_type' => $operateType,
                'operate_name' => $operateName,
                'description' => $desc,
                'user_desc' => $userDesc,
            ];
            (new EmployeeCardLogService())->add($logData);
        } else {
            throw new \think\Exception(L_("充值失败！请联系管理员协助解决。"), 1003);
        }

        return true;
    }

    /**
     *获取一条条数据
     * @param array $where
     * @return array
     */
    public function getOne($where){
        $result = $this->employeeCardUserModel->getOne($where);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     * 用户信息
     */
    public function findUser($param)
    {
        $where=[['phone','=',$param['phone']]];
        $user=(new User())->getUser('uid,nickname',$where);
        if(!empty($user)){
            $user=$user->toArray();
            $excit=(new EmployeeCardUser())->getOne(['card_id'=>$param['card_id'],'uid'=>$user['uid']]);
            if(empty($excit)){
                $data['status']=1;
                $data['msg']='';
                $data['data']=$user;
            }else{
                $data['status']=0;
                $data['msg']='此手机号已经添加，请添加其他手机号';
                $data['data']=[];
            }
        }else{
            $data['status']=2;
            $data['data']=[];
            $data['msg']="没有该用户";
        }
        return $data;
    }

    /**
     * 导入抄表
     * @author lijie
     * @date_time 2021/10/22
     * @param $file
     * @param $charge_name
     * @param $village_id
     * @param $uid
     * @return array|string
     * @throws \think\Exception
     */
    public function upload($file,$merId,$card_id)
    {
        $savepath = $file;
        $filed = [
            'A' => 'card_number',
            'B' => 'name',
            'C' => 'identity',
            'D' => 'department',
            'E' => 'phone',
            'F' => 'lable_ids',
            'G' => 'card_score',
            'H' => 'card_money'
        ];
        $suffix = ucfirst(strtolower(substr(strstr($savepath , '.'), 1)));
        if(!in_array($suffix, ['Xlsx', 'Xls'])){
            throw new \think\Exception('文件格式不正确，请上传Xlsx或Xls格式文件！');
        }
        Db::startTrans();
        try{
            $data = $this->readFile($_SERVER['DOCUMENT_ROOT'] . $savepath, $filed, $suffix);
            $data = array_values($data);
            if(!empty($data)){
               foreach ($data as $k=>$value){
                   if(empty($value['name']) || empty($value['identity']) || empty($value['department']) || empty($value['phone'])){
                       continue;
                   }
                   $update['identity']=$value['identity']=is_object($value['identity'])?$value['identity']->__toString():$value['identity'];
                   $value['card_number']=is_object($value['card_number'])?$value['card_number']->__toString():$value['card_number'];
                   $update['name']=$value['name']=is_object($value['name'])?$value['name']->__toString():$value['name'];
                   $update['department']=$value['department']=is_object($value['department'])?$value['department']->__toString():$value['department'];
                   $value['phone']=is_object($value['phone'])?$value['phone']->__toString():$value['phone'];
                   $value['lable_ids']=is_object($value['lable_ids'])?$value['lable_ids']->__toString():$value['lable_ids'];
                   $value['card_score']=is_object($value['card_score'])?$value['card_score']->__toString():$value['card_score'];
                   $value['card_money']=is_object($value['card_money'])?$value['card_money']->__toString():$value['card_money'];
                   $value['phone']=$this->trimall($value['phone']);
                   $user=(new User())->getUserMsg('uid,phone',['phone'=>$value['phone']]);
                   if(empty($value['card_number'])){
                       $value['card_number']='';
                       $msg=[];
                   }else{
                       $msg=(new EmployeeCardUser())->getOne(['card_id'=>$card_id,'card_number'=>$value['card_number']],'card_id,card_score,card_money,uid,lable_ids');
                   }
                   if(!empty($msg)){
                       $msg=$msg->toArray();
                       if(empty($msg['lable_ids']) && !empty($value['lable_ids'])){
                           $update['lable_ids']=$value['lable_ids'];
                       }else{
                           /*if($msg['lable_ids']!=$value['lable_ids']){*/
                               $array_a=explode(",",$msg['lable_ids']);
                               $array_b=explode(",",$value['lable_ids']);
                               $new_arr=array_diff($array_b,$array_a);//取差集
                               $new_arr=array_values($new_arr);
                               $str=implode(',',$new_arr);
                               fdump_sql(['card_number'=>$value['card_number'],'str'=>$str,'new_arr'=>$new_arr],"log9988771111111111111111");
                               if(isset($new_arr[0]) && !empty($new_arr[0]) && $str!=','){
                                   $update['lable_ids']=$msg['lable_ids'].','.$str;
                               }
                          /* }*/
                       }
                       if(!empty($user) && $msg['uid']==$user['uid']){
                           if($value['card_score']>0){
                               (new EmployeeCardUser())->setInc(['card_id'=>$card_id,'card_number'=>$value['card_number']],'card_score',$value['card_score']);
                           }else{
                               if($msg['card_score']<$value['card_score']*-1){
                                   (new EmployeeCardUser())->setDec(['card_id'=>$card_id,'card_number'=>$value['card_number']],'card_score',$msg['card_score']);
                               }else{
                                   (new EmployeeCardUser())->setInc(['card_id'=>$card_id,'card_number'=>$value['card_number']],'card_score',$value['card_score']);
                               }
                           }

                           if($value['card_money']>0){
                               (new EmployeeCardUser())->setInc(['card_id'=>$card_id,'card_number'=>$value['card_number']],'card_money',get_number_format($value['card_money']));
                           }else{
                               if($msg['card_money']<$value['card_money']*-1){
                                   (new EmployeeCardUser())->setDec(['card_id'=>$card_id,'card_number'=>$value['card_number']],'card_money',get_number_format($msg['card_money']));
                               }elseif($value['card_money']*1!=0){
                                   (new EmployeeCardUser())->setInc(['card_id' => $card_id, 'card_number' => $value['card_number']], 'card_money', get_number_format($value['card_money']));
                               }
                           }
                           (new EmployeeCardUser())->updateThis(['card_id'=>$card_id,'card_number'=>$value['card_number']],$update);
                           fdump_sql(['sql'=>(new EmployeeCardUser())->getLastSql(),'msg'=>$msg,'value'=>$value],"log9988771111111111111111");
                           continue;
                       }else{
                           throw new \Exception($value['card_number']."卡号重复但手机号不同，请确认正确再添加", 1003);
                       }
                   }elseif(empty($msg) && !empty($user)){
                       $msg_user=(new EmployeeCardUser())->getOne(['card_id'=>$card_id,'uid'=>$user['uid']],'card_id,card_score,card_money,uid');
                       if(!empty($msg_user)){
                           throw new \Exception($value['card_number']."卡号绑定的手机号和另外一张卡手机号重复，请确认正确再添加", 1003);
                       }else{
                           $params['uid']=$user['uid'];
                           $params['mer_id'] =$merId;
                           $params['card_id'] = $card_id;
                           $params['name'] = $value['name'];
                           $params['card_number'] = $value['card_number'];
                           $params['identity'] = $value['identity'];
                           $params['department'] = $value['department'];
                           $params['lable_ids'] = $value['lable_ids'];
                           $params['card_money'] = $value['card_money']>0?$value['card_money']:0;
                           $params['card_score'] = $value['card_score']>0?$value['card_score']:0;
                           $params['status'] = 1;
                           $params['add_time'] = time();
                           if(empty($params['uid'])){
                               throw new \Exception("此手机号：".$value['phone']."的用户信息获取失败，请重新导入", 1003);
                           }
                           (new EmployeeCardUser())->add($params);
                       }
                   }else{
                       $data1['phone']=$value['phone'];
                       $data1['nickname']=$value['name'];
                       $data1['status']=1;
                       $params['uid']=(new User())->add($data1);
                       $params['mer_id'] =$merId;
                       $params['card_id'] = $card_id;
                       $params['name'] = $value['name'];
                       $params['card_number'] = $value['card_number'];
                       $params['identity'] = $value['identity'];
                       $params['department'] = $value['department'];
                       $params['lable_ids'] = $value['lable_ids'];
                       $params['card_money'] = $value['card_money']>0?$value['card_money']:0;
                       $params['card_score'] = $value['card_score']>0?$value['card_score']:0;
                       $params['status'] = 1;
                       $params['add_time'] = time();
                       if(empty($params['uid'])){
                           //continue;
                           throw new \Exception("此手机号：".$value['phone']."的用户信息新增失败，请重新导入", 1003);
                       }
                       (new EmployeeCardUser())->add($params);
                   }

               }
            }
            Db::commit();
            return true;
        }catch (\Exception $e) {
            Db::rollback();
            throw new \Exception($e->getMessage(), 1003);
        }
    }
    
    //删除空格
    public function trimall($str)
    {
        $oldchar = array(" ", "　", "\t", "\n", "\r");
        $newchar = array("", "", "", "", "");
        $str = str_replace($oldchar,$newchar,$str);
        $str =  preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/","",$str);
        return $str;
    }

    /**
     * 读取文件
     * @author
     * @date_time: 2021/5/21 13:21
     */
    public function readFile($file, $filed, $readerType)
    {
        $uploadfile = $file;
        $reader = IOFactory::createReader($readerType); //设置以Excel5格式(Excel97-2003工作簿)
        $PHPExcel = $reader->load($uploadfile); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestDataRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $data = [];
        for ($row = 2; $row <= $highestRow; $row++) //行号从1开始
        {
            for ($column = 'A'; $column <= "H"; $column++) //列数是以A列开始
            {
                    $data[$row][$filed[$column]] = $sheet->getCell($column . $row)->getValue();
            }
        }
        return $data;
    }

    /**
     * 员工卡身份标签列表
     * @param $param array
     * @return array
     */
    public function employLableList($param) {
        $limit = 0;
        if ($param['page'] > 0) {
            $limit = [
                'page' => $param['page'] ?? 1,
                'list_rows' => $param['pageSize'] ?? 10
            ];
        }
        $where = [
            ['is_del', '=', 0]
        ];
        if (!empty($param['mer_id'])) {
            $where[] = ['mer_id', '=', $param['mer_id']];
        }
        $result = (new EmployeeCardLable())->getLableList($where, $limit);
        foreach ($result['data'] as $key => $item) {
            if(!$item['bind_store_id']){
                $result['data'][$key]['bind_store_id'] = [];
            }
        }
        return $result;
    }

    /**
     * 添加或编辑员工卡身份标签
     * @param $param array
     */
    public function employLableAddOrEdit($param) {
        $arr = [
            'mer_id' => $param['mer_id'],
            'name'   => $param['name']
        ];
        if (!empty($param['id'])) {
            (new EmployeeCardLable())->updateThis(['id' => $param['id']], $arr);
        } else {
            $arr['add_time'] = time();
            (new EmployeeCardLable())->insert($arr);
        }
        return true;
    }

    /**
     * 获取标签列表无分页 
     */
    public function getLabelList($params) {
        $condition = [];
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['is_del', '=', 0];
        return (new EmployeeCardLable())->field(['id', 'name'])->where($condition)->select();
    }

    /**
     * 获取列表
     * @return \json
     */
    public function getlableAll()
    {
        $lableArr = (new EmployeeCardLable())->getLableAll();
        $lableFormat = [];
        foreach($lableArr as $value){
            if(isset($lableFormat[$value['mer_id']])){
                $lableFormat[$value['mer_id']]['lables'][] =  [
                    'lable_id' => $value['id'],
                    'name' => $value['name'],
                ];
            }else{
                $lableFormat[$value['mer_id']]['name'] = $value['merchant_name'];
                $lableFormat[$value['mer_id']]['mer_id'] = $value['mer_id'];
                $lableFormat[$value['mer_id']]['lables'][] = [
                    'lable_id' => $value['id'],
                    'name' => $value['name'],
                ];
            }
        }
        return array_values($lableFormat);
       
    }

    /**
     * 添加导出计划任务
     * @param $param
     * @param array $systemUser
     * @param array $merchantUser
     * @return array
     * @throws \think\Exception
     */
    public function exportUserCardList($param, $systemUser = [], $merchantUser = [])
    {
        $title = '员工卡列表';
        $param['etype'] = $param['type'];
        $param['type'] = 'pc';
        $param['service_path'] = '\app\employee\model\service\EmployeeCardUserService';
        $param['service_name'] = 'userCardExportPhpSpreadsheet';
        $param['rand_number']  = time();
        $param['system_user']['area_id']  = $systemUser ? $systemUser['area_id'] : 0;
        $param['merchant_user']['mer_id'] = $merchantUser ? $merchantUser['mer_id'] : 0;
        $result = (new BaseExportService())->addExport($title, $param, 'xlsx');
        return $result;
    }

    /**
     * 导出(Spreadsheet方法)
     * @param $param
     */
    public function userCardExportPhpSpreadsheet($param)
    {
        $param['type'] = $param['etype'];
        $orderList   = $this->getUserCardList($param)['list'];
        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->getActiveSheet();
        //设置单元格内容
        $worksheet->setCellValueByColumnAndRow(1, 1, '会员名称');
        $worksheet->setCellValueByColumnAndRow(2, 1, '会员卡号');
        $worksheet->setCellValueByColumnAndRow(3, 1, '会员标签');
        $worksheet->setCellValueByColumnAndRow(4, 1, '会员身份');
        $worksheet->setCellValueByColumnAndRow(5, 1, '会员部门');
        $worksheet->setCellValueByColumnAndRow(6, 1, '会员手机号');
        $worksheet->setCellValueByColumnAndRow(7, 1, '会员卡余额');
        $worksheet->setCellValueByColumnAndRow(8, 1, '会员卡积分');
        $worksheet->setCellValueByColumnAndRow(9, 1, '会员卡状态');
        //设置单元格样式
        $worksheet->getStyle('A1:I1')->getFont()->setName('黑体')->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('A:I')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A1:I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('EEEEEE');
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(26);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(42);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(30);
        $len = count($orderList);
        $j   = 0;
        $row = 0;
        $i   = 0;
        foreach ($orderList as $key => $val) {
            if ($i < $len) {
                $j = $i + 2; //从表格第2行开始
                $worksheet->setCellValueByColumnAndRow(1, $j, $orderList[$key]['name']);
                $worksheet->setCellValueByColumnAndRow(2, $j, $orderList[$key]['card_number']);
                $worksheet->setCellValueByColumnAndRow(3, $j, $orderList[$key]['lables']);
                $worksheet->setCellValueByColumnAndRow(4, $j, $orderList[$key]['identity']);
                $worksheet->setCellValueByColumnAndRow(5, $j, $orderList[$key]['department']);
                $worksheet->setCellValueByColumnAndRow(6, $j, $orderList[$key]['phone']);
                $worksheet->setCellValueByColumnAndRow(7, $j, $orderList[$key]['card_money']);
                $worksheet->setCellValueByColumnAndRow(8, $j, $orderList[$key]['card_score']);
                $worksheet->setCellValueByColumnAndRow(9, $j, $orderList[$key]['status'] == 1 ? '开启' : '关闭');
                $i++;
            }
            $row++;
        }
        $styleArrayBody = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_JUSTIFY
            ],
        ];
        $total_rows = $row + 1;
        //添加所有边框/居中
        $worksheet->getStyle('A1:I' . $total_rows)->applyFromArray($styleArrayBody);
        //下载
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xlsx';
        (new BaseExportService())->phpSpreadsheet($filename, $spreadsheet);

    }


    /**
     * 获取店铺列表
     */
    public function getStoreList($mer_id)
    {
        $condition = [];
        $condition[] = ['mer_id', '=', $mer_id];
        $condition[] = ['status', '=', 1];
        return (new MerchantStore())->where($condition)->column('name', 'store_id');
    }


    /**
     * 绑定标签
     */
    public function lableBindStore($params)
    {
        $condition = [];
        $condition[] = ['id', '=', $params['lable_id']];
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $lable = (new EmployeeCardLable)->where($condition)->find();
        if(!$lable){
            throw new \think\Exception('标签不存在！');
        }

        $lable->bind_store_id = $params['store_ids'];
        return $lable->save();

    }

}