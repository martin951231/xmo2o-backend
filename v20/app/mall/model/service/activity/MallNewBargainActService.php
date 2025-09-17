<?php
/**
 * 商城营销活动 -- 砍价
 */

namespace app\mall\model\service\activity;

use app\mall\model\db\MallActivity;
use app\mall\model\db\MallActivityDetail;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallNewBargainAct;
use app\mall\model\db\MallNewBargainAct as MallNewBargainActModel;
use app\mall\model\db\MallActivity as MallActivityModel;
use app\mall\model\db\MallNewBargainSku;
use app\mall\model\db\MallNewBargainTeam;
use app\mall\model\db\MallNewBargainTeamUser;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallPrepareOrder;
use app\mall\model\db\MallSixAdverGoods;
use app\mall\model\db\User;
use app\mall\model\service\MallGoodsService;
use app\mall\model\service\SendTemplateMsgService;
use think\Exception;
use think\facade\Db;

class MallNewBargainActService
{
    public $mallNewBargainActModel = null;
    public $mallActivityModel = null;

    public function __construct()
    {
        $this->mallNewBargainActModel = new MallNewBargainActModel();
        $this->mallActivityModel = new MallActivityModel();
    }

    /**
     * @param $goods_id
     * @author mrdeng
     */
    public function getBargainDetail($goods_id, $activity_id)
    {
        $condition[] = ['m.goods_id', '=', $goods_id];
        if (!empty($activity_id)) {
            $condition[] = ['s.activity_id', '=', $activity_id];
        }
    }

    /**
     * 砍价列表
     * User: chenxiang
     * Date: 2020/10/14 9:28
     * @param $store_id
     * @param $param
     * @return mixed
     */
    public function getBargainList($store_id, $param)
    {
        if (empty($store_id)) {
            throw new \think\Exception('店铺id为空');
        }

        $page = isset($param['page']) ? $param['page'] : 1;
        $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 10;//每页限制
        $name = isset($param['name']) ? trim($param['name'], '') : '';
        $status = isset($param['status']) ? $param['status'] : 3;//活动状态 0未开始 1进行中 2已失效 3全部
        $startTime = !empty($param['start_time']) ? strtotime($param['start_time'] . " 00:00:00") : '';//开始时间
        $endTime = !empty($param['end_time']) ? strtotime($param['end_time'] . " 23:59:59") : '';//结束时间

        if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
            throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
        }

        // 搜索条件
        $where = [];
        $where[] = ['a.store_id', '=', $store_id];
        if ($name) {
            $where[] = ['a.name', 'like', '%' . $name . '%'];
        }

        /*if (!empty($startTime) && !empty($endTime)) {
            $where[] = ['a.start_time', 'between', [$startTime, $endTime]];
            $where[] = ['a.end_time', 'between', [$startTime, $endTime]];
        } elseif ($startTime) {
            $where[] = ['a.start_time', '>=', $startTime];
        } elseif ($endTime) {
            $where[] = ['a.end_time', '<=', $endTime];
        }*/

        if($startTime && $endTime){
            $where[] = ['a.start_time', '<', $startTime];
            $where[] = ['a.end_time', '>', $endTime];
        }elseif($startTime){
            $where[] = ['a.start_time', '>=', $startTime];
        }elseif($endTime){
            $where[] = ['a.end_time', '<=', $endTime];
        }

        $whereOr=[];
        if(intval($status)==0 && empty($startTime) && empty($endTime)){
            $where[] = ['a.start_time', '>', time()];
        }elseif (intval($status)==1 && empty($startTime) && empty($endTime)){
            $where[] = ['a.start_time', '<=', time()];
            $where[] = ['a.end_time', '>', time()];
            $where[] = ['a.status', 'not in', [0,2]];
        }elseif (intval($status)==2 && empty($startTime) && empty($endTime)){
            $where[] = ['a.end_time', '<', time()];
            $whereOr[] = ['a.status', '=', 2];
            $whereOr[] = ['a.is_del', '=', 0];
            $whereOr[] = ['a.type', '=', 'bargain'];
            $whereOr[] = ['a.store_id', '=', $store_id];
        }else{
            if (in_array($status, [0, 1, 2])) {//0未开始 1进行中 2已失效 3全部活动
                $where[] = ['a.status', '=', $status];
            }
        }

        $where[] = ['a.is_del', '=', 0];
        $where[] = ['a.type', '=', 'bargain'];

        $prefix = config('database.connections.mysql.prefix');
        $mallActivityModel = new MallActivityModel();
        $fields = 'a.id, a.store_id, a.act_id, a.start_time, a.end_time, a.status, g.name as goods_name, g.image as goods_image, b.goods_id';
        $lists = $mallActivityModel->alias('a')
            ->join([$prefix . 'mall_new_bargain_act' => 'b'], 'a.act_id=b.id', 'LEFT')
            ->join([$prefix . 'mall_goods' => 'g'], 'g.goods_id=b.goods_id', 'LEFT')
            ->field($fields)
            ->where($where);
        if(!empty($whereOr) && $status==2){
            $lists = $lists->where(function ($query) use ($where) {
                $query->where($where);
            })->whereOr(function ($query) use ($whereOr) {
                $query->where($whereOr);
            });
        }
        $count=$lists->count();
        $lists=$lists->order('a.id DESC')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        if(empty($lists)){
            $lists=[];
        }else{
            foreach ($lists as $key => &$value) {
                if ($value['start_time'] < time() && $value['end_time'] < time()) {
                    $value['status'] = 2;
                }
                if ($value['start_time'] < time() && $value['end_time'] > time() && $value['status'] != 2) {
                    $value['status'] = 1;
                }
                $value['start_time'] = date('Y-m-d H:i:s', $value['start_time']);
                $value['end_time'] = date('Y-m-d H:i:s', $value['end_time']);
                $value['goods_image'] = replace_file_domain($value['goods_image']);
                switch ($value['status']) { //活动状态 0未开始 1进行中 2已失效
                    case 0 :
                        $value['status_des'] = '未开始';
                        break;
                    case 1 :
                        $value['status_des'] = '进行中';
                        break;
                    case 2 :
                        $value['status_des'] = '已失效';
                        break;
                }

                $condition = [];
                $condition['store_id'] = $value['store_id'];
                $condition['act_id'] = $value['act_id'];

                $result_num = (new MallNewBargainTeam())->field('count(id) as bar_start_num, sum(bar_num) as bar_total_num, count(order_id) as pay_order_total, order_id')->where($condition)->select()->toArray();

                $value['bar_start_num'] = $result_num[0]['bar_start_num']; //发起砍价人数

                $value['bar_total_num'] = $result_num[0]['bar_total_num'] ?? 0; // 帮砍人数
//                $value['pay_order_total'] = $result_num['pay_order_total']; //支付订单数

                $condition_where = [];
                $condition_where[] = ['o.store_id', '=', $store_id];
                $condition_where[] = ['goods_activity_type', '=', 'bargain'];
                $condition_where[] = ['goods_activity_id', '=', $value['act_id']];
                $condition_where[] = ['o.status', '>=', 10];
                $condition_where[] = ['o.status', '<=', 49];
                $total = (new MallOrder())->getList($fields = 'o.uid,o.money_real', $condition_where);
                $real_income = array_sum(array_column($total, 'money_real'));
//                $total = array_column($total, NULL, 'uid');
                $value['pay_order_num'] = count($total);//支付单数
                $value['real_income'] = number_format($real_income, 2);//实收金额
//                $value['pay_order_people'] = count($total);//支付人数

                //生成二维码
                $value['qrcode'] = $this->createGoodsQrcode($value['goods_id']);
            }
        }
        $res['list'] = $lists;
        $res['pageSize'] = $pageSize;
        $res['count'] = $count;
        $res['page'] = $page;
        return $res;
    }

    /**
     * @param $goods_id
     * @return string
     * 获取商品二维码
     */
    public function createGoodsQrcode($goods_id)
    {
        require_once request()->server('DOCUMENT_ROOT') . "/v20/extend/phpqrcode/phpqrcode.php";
        if (empty($goods_id)) return '';
        $dir = '/runtime/qrcode/mall/' . $goods_id;
        $path = request()->server('DOCUMENT_ROOT') . $dir;
        $filename = md5($goods_id) . '.png';
        if (file_exists($path . '/' . $filename)) {
            return cfg('site_url') . $dir . '/' . $filename;
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $qrCon = cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $goods_id;
        $qrcode = new \QRcode();
        $qrcode->png($qrCon, $path . '/' . $filename, 'L', '9');
        return cfg('site_url') . $dir . '/' . $filename;
    }

    /**
     * @param $team_id
     * @param $user_id
     * @return \json|void
     * 计算砍价价格，插入砍价信息
     * @author mrdeng
     */
    public function getBargainPrice($team_id, $user_id, $sku_id)
    {
        if (empty($team_id) || empty($user_id) || empty($sku_id)) {
            return api_output_error(1003, "缺失必要参数");
        }
        $getTeamUserMsg[] = ['tid', '=', $team_id];
        $getTeamUserMsg[] = ['user_id', '=', $user_id];
        $user_msg = (new MallNewBargainTeamUser())->getOne($getTeamUserMsg);
        $team = (new MallNewBargainTeam())->getBargainPrice($team_id, $user_id);//获取队伍信息
        $arr_get['act_id']=$team['act_id'];
        $arr_get['user_id']=$team['user_id'];
        $arr_get['sku_id']=$sku_id;
        $bar_goods=(new MallNewBargainActService())->getBargainActSkuMsg($arr_get);//获取砍价活动及商品信息
        if($bar_goods['buy_limit']>0){
            $limit_nums=(new MallNewBargainTeam())->barginTeaNums($arr_get['user_id'],$arr_get['act_id'],$arr_get['sku_id']);
            if($bar_goods['buy_limit']<$limit_nums){
                $res['status'] = 0;
                $res['msg'] = '团队发起人参与砍价活动次数已上限';
                return $res;
            }
        }
        if(input('_debug')==1){
            dump([$team,$team['user_id'],$user_id]);exit();
        }
        if($bar_goods['help_bargain_times']==2&&$team['user_id']!=$user_id){
            $help_bargain_times = (new MallNewBargainTeamUser())->where([
                ['bar_time','between',[strtotime(date('Y-m-d')),strtotime(date('Y-m-d').' 23:59:59')]],
                ['user_id','=',$user_id],
                ['tid','=',$team_id],
            ])->count();
            if($help_bargain_times>0){
                $res['status'] = 0;
                $res['msg'] = '每人每天可帮砍1次';
                return $res;
            }
	        $user_msg = [];
        }
        if (empty($user_msg)) {
            $where_act = [['act_id', '=', $team['act_id']], ['type', '=', 'bargain'], ['start_time', '<', time()], ['end_time', '>=', time()]];
            $activity = (new MallActivity())->getOne($where_act, $field = 'id');
            if (empty($activity)) {
                $res['status'] = 0;
                $res['activity_status'] = 3;
                $res['msg'] = '该商品砍价活动已结束';
                return $res;
            }
            if (empty($team)) {
                $msg['msg'] = '没有砍价队伍信息！';
                $msg['status'] = 0;
                return $msg;
            }
            $condition[] = ['end_time', '>=', time()];
            $condition[] = ['id', '=', $team['id']];
            $res = (new MallNewBargainTeam())->getActivityExist2($condition);//判断活动是否进行
            if ($res) {
                $where_bar = [['g.id', '=', $team['act_id']], ['a.type', '=', 'bargain'], ['a.start_time', '<', time()], ['a.end_time', '>=', time()]];
                $bargain = (new MallNewBargainAct())->getBargainActSkuMsg($where_bar, $field = 'g.bar_first_per_min,g.bar_first_per_max,g.help_bargain_people_num,m.act_stock_num,m.act_price');
                if (empty($bargain)) {
                    $msg['msg'] = '活动数据异常！';
                    $msg['status'] = 0;
                    return $msg;
                }
                if(cfg('mall_bargain_times_per_day')>0){
                    $help_bargain_num = (new MallNewBargainTeamUser())->where([
                        ['bar_time','between',[strtotime(date('Y-m-d')),strtotime(date('Y-m-d').' 23:59:59')]],
                        ['user_id','=',$user_id],
                    ])->count();
                    if($help_bargain_num>=cfg('mall_bargain_times_per_day')&&$user_id!=$team['user_id']){
                        $msg['msg'] = '已超过每天可帮砍次数！';
                        $msg['status'] = 0;
                        return $msg;
                    }
                }
                $where1 = [['tid', '=', $team_id]];
                $goods_price = (new MallNewBargainTeam())->getActivityGoods($team_id, $sku_id);//成团砍价活动关联商品的价格
                //砍价插入且返回砍价数据
                $user = (new MallNewBargainTeamUser())->getTeamUserExist($where1);//是有砍价记录
                $user_num = count($user);//当前用砍价户数
                $arr['is_my_bar'] = 0;//默认不是自己砍价，弹窗样式区分
                if ($team['user_id'] == $user_id) {
                    $arr['is_my_bar'] = 1;//是自己砍的，自己是砍主，首刀
                }
                $arr['id'] = $goods_price['id'];
                $arr['goods_id'] = $goods_price['goods_id'];
                $arr['goods_name'] = $goods_price['goods_name'];
                $arr['goods_image'] = $goods_price['goods_image'] ? replace_file_domain($goods_price['goods_image']) : '';
                $arr['activity_status'] = 0;//继续砍价
                $where1_suces = [['status', 'in', [1, 2, 3]], ['act_id', '=', $team['act_id']]];
                $arr['bargain_success'] = (new MallNewBargainTeam())->getBargainNum($where1_suces);
                $user_msg = (new User())->getUserById($user_id);
                $arr['user_image'] = $user_msg['user_logo'];//用户头像
                $team_user['tid'] = $team['id'];
                $team_user['user_id'] = $user_id;
                $team_user['bar_time'] = time();
                $team_user['is_start'] = 0; //是否是发起者
                if (empty($user)) {//没有砍价用户信息，则是团主加入
                    $team_user['is_start'] = 1;
                }
                if ($res['status'] > 0 && $res['status'] < 4) {
                    $msg['msg'] = '已经砍完';
                    $msg['status'] = 0;
                    $msg['activity_status'] = 1;
                    return $msg;
                }
                if ($goods_price['money_total'] == $team['floor_price']) {
                    $msg['msg'] = '活动底价与商品原价一致，无须砍价';
                    $msg['status'] = 0;
                    return $msg;
                }
                /*else{*/
                if ($user_num == 0) {//首刀
                    $m = $bargain['bar_first_per_min'] / 100;
                    $n = $bargain['bar_first_per_max'] / 100;
                    $team_user['bar_per'] = $arr['bar_per'] = $per = $this->generateRand($m, $n);//砍掉比例
                    $team_user['bar_price'] = $arr['bargain_money'] = $bargain_price = get_format_number(($goods_price['money_total'] - $team['floor_price']) * $per);//砍掉价格
                    $arr['left_bargain'] = $left_bargain = get_format_number($goods_price['money_total'] - $team['floor_price'] - $bargain_price);//剩砍价格
                } else {//不是首刀
                    $sum_per = (new MallNewBargainTeamUser())->getSumBargainPer($where1, 'bar_per');//已经砍掉的百分比
                    $m = 0;
                    $n = (1 - $sum_per) / ($bargain['help_bargain_people_num'] - $team['bar_num']) * 2;
                    $team_user['bar_per'] = $arr['bar_per'] = $per = $this->generateRand($m, $n);//准备砍掉比例
                    $sum_bar_price = (new MallNewBargainTeamUser())->getSumBargainPer($where1, 'bar_price');//已经砍掉的金额
                    if (1 - $sum_per > 0 && ($bargain['help_bargain_people_num'] - $team['bar_num'] == 1)) {//最后一个砍
                        $team_user['bar_per'] = $arr['bar_per'] = 1 - $sum_per;
                        $team_user['bar_price'] = $arr['bargain_money'] = $arr['left_bargain'] = $left_bargain = $bargain_price = get_format_number($goods_price['money_total'] - $team['floor_price'] - $sum_bar_price);//砍掉价格与剩砍价格一样
                        $arr['activity_status'] = $activity_status = 1;//砍价成功
                        $arr['left_bargain'] = 0;
                        $team['success_time'] = time();
                        $team['status'] = 1;//已砍完
                        //砍价成功推送消息
                        (new SendTemplateMsgService())->sendWxappMessage(['type' => 'bargain_success', 'goods_name' => $arr['goods_name'], 'uid' => $user_id, 'bargain_price' => $team['floor_price'], 'end_time' => $team['end_time']]);
                    } else {
                        $real_bargain_price = get_format_number(($goods_price['money_total'] - $team['floor_price'] - $sum_bar_price) * $per,4);
                        $team_user['bar_price'] = $arr['bargain_money'] = $bargain_price = get_format_number(($goods_price['money_total'] - $team['floor_price'] - $sum_bar_price) * $per);//砍掉价格
                        fdump($team_user,"team_user888888888888",1);
                        if($bargain_price==0 && !($real_bargain_price > 0 && $real_bargain_price < 0.01)){//防止有砍成0的情况(金额小于0.01会误判为0，这里修改为金额小于0.005就当做0，但是不算作砍为0，不走此判断)
                            $msg['msg'] = '网络开小差啦,请再砍价一下';
                            $msg['status'] = 0;
                            return $msg;
                        }

                        $arr['left_bargain'] = $left_bargain = get_format_number($goods_price['money_total'] - $team['floor_price'] - $bargain_price - $sum_bar_price);//剩砍价格
                    }
                    $team['bar_num'] = $team['bar_num'] + 1;//新增帮助砍价的用户数
                }
                $arr['bar_total_price'] = $team['bar_total_price'] = get_format_number($team['bar_total_price'] + $team_user['bar_price']);//砍掉总额
                $condition1[] = ['id', '=', $team['id']];
                $result = (new MallNewBargainTeam())->saveData($condition1, $team);
                if ($result === false) {
                    $msg['msg'] = '更新队伍失败';
                    $msg['status'] = 0;
                    return $msg;
                }

                $result1 = (new MallNewBargainTeamUser())->addData($team_user);//插入队员表
                if ($result1) {
                    $arr['status'] = 1;
                    return $arr;
                } else {
                    $msg['msg'] = '加入队伍失败';
                    $msg['status'] = 0;
                    return $msg;
                }
            } else {
                $res['status'] = 0;
                $res['activity_status'] = 2;
                $res['msg'] = '该商品砍价有效时间已结束';
                return $res;
            }
        } else {
            $con = [['id', '=', $team_id], ['user_id', '=', $user_id]];
            $field = "*";
            $act_status = (new MallNewBargainTeam())->getInfoById($con, $field);
            if (!empty($act_status)) {
                $msg['msg'] = '不可以点击自己发起的砍价哦~';
            } else {
                $msg['msg'] = '您已帮好友砍价过，不可以再次帮砍';
            }
            $msg['status'] = 0;
            return $msg;
        }
    }

    /**
     * 获取m、n之间的随机数，保留2位小数
     * @param $m
     * @param $n
     * @return float
     * @date 2020/9/21
     * @author mrdeng
     */
    public function generateRand($m, $n)
    {
        $numMax = $n;
        $numMin = $m;
        /**
         * 生成$numMin和$numMax之间的随机浮点数，保留2位小数
         */
        $rand = $numMin + mt_rand() / mt_getrandmax() * ($numMax - $numMin);
        $rand=number_format($rand,3);
        if($rand*1==0){
            $this->generateRand($m, $n);
        }else{
            return $rand;
        }
    }

    /**
     * @param $uid 用户id
     * @param $status 活动状态 0代表全部
     * @param $page  页码
     * @param $addr
     * @return array
     * 我的砍价列表
     */

    public function getMyBargainList($uid, $status, $page, $limit, $addr)
    {
        //$data['periodic_date']=strtotime(date('Y-m-d H:i:s',strtotime('+'.$i.' day')));
        $where = $whereOr = array();
        $where1 = array();
        if ($uid != 0) {
            $where[] = ["s.user_id", '=', $uid];
        }
        if ($limit == 2) {
            $where[] = ['s.status', '=', 0];
            $where[] = ['s.end_time', '>=', time()];
        }
        $new_act_list = (new MallActivity())->where([['status','=',2]])->column('act_id');
        switch ($status) {
            case 0://全部
                break;
            case 1://1进行中
                //$where[]=['s.start_time','<',time()];
                $where[] = ['s.end_time', '>=', time()];
                $where[] = ['s.status', '=', 0];
                $where[] = ['ma.end_time', '>', time()];
                if($new_act_list){
                    $where[] = ['s.act_id', 'not in', $new_act_list];
                }
                break;
            case 2:// 2已完成
                if($new_act_list){
                    $where[] = ['', 'exp', Db::raw('s.status = 3 or (s.status in (1,2) and s.act_id not in ('.implode(',',$new_act_list).') and s.end_time > '.time().')')];
                    //$whereOr = $where;
                    //$whereOr[] = ['s.status', 'in', [1]];
                    //$whereOr[] = ['s.act_id', 'not in', $new_act_list];
                }else{
                    $where[] = ['s.status', 'in', [1, 2, 3]];
                }
                break;
            case 3://3已过期
                $beforetime = time() - 86400*7;     //7天前的时间戳=当前时间戳-7天时间戳
                /*$where1[]=['s.status','=',4];*/
                $where1[] = ['s.end_time', '<', time()];
                $where1[] = ['s.status', 'in', [0, 4]];
                /*$where[]=['s.status','=',4];*/
               /* $where[] = ['s.end_time', '<', time()];*/
                //$where[] = ['s.status', 'in', [0, 4]];
                if($new_act_list){
                    //$whereOr = 's.status in (0,4) and s.act_id in ('.implode(',',$new_act_list).')';
                    /*$whereOr = $where;
                    $whereOr[] = ['s.act_id','in',$new_act_list];
                    $whereOr[] = ['s.status','in',[0, 1, 4]];*/

                    $where[] = ['', 'exp', Db::raw('(s.status = 4 and s.end_time > '.$beforetime.' and s.end_time < '.time().') or (s.status in (0,1,2) and (s.act_id in ('.implode(',',$new_act_list).') or s.end_time < '.time().'))')];
                }else{
                    $where[] = ['s.end_time','between',[$beforetime,time()]];
                    $where[] = ['s.status','in',[0, 4]];
                }
                break;
        }

        $field = "s.order_id,s.bar_total_price,s.act_id,s.end_time,m.goods_id,s.floor_price,s.status,mg.image,mg.price,msku.sku_id,s.id as teamId,mgd.name,msku.act_stock_num,m.bargain_can_pay";
        $list = (new MallNewBargainTeam())->myBargainListPrice($where, $field, $page, $limit, $where1, $whereOr);//先查出关联的队伍活动信息
        if (!empty($list['list'])) {
            foreach ($list['list'] as $key => $val) {//找出队伍砍价信息关联砍价商品信息
                /*if ($status == 3) {
                    $days = (time() - $val['end_time']) / 86400;
                    if ($days > 7) {
                         unset($list['list'][$key]);
                        continue;
                    }
                }*/
                /*if($val['end_time']-time()<0){
                    unset($list['list'][$key]);
                    continue;//有效时间为负的不显示，去掉不正常数据
                }*/
                $where2 = [['bg.act_id', '=', $val['act_id']]];
                $nums = (new MallNewBargainTeamUser())->getSumBargainUser($where2);
                $end_time = $val['end_time'] - time();
                $res['left_time'] = $end_time > 0 ? $end_time : 0;
                $res['act_id'] = $val['act_id'];
                $res['goods_id'] = $val['goods_id'];
                $res['sku_id'] = $val['sku_id'];
                $res['already_bargain'] = get_format_number($val['bar_total_price']);
                $res['bargain_price'] = get_format_number($val['floor_price']);
                $res['goods_name'] = $val['name'];
                $res['goods_price'] = get_format_number($val['price']);
                $res['bargain_peoples'] = $nums;
                $res['teamId'] = $val['teamId'];
                $res['order_id'] = $val['order_id'];
                $res['can_go_pay'] = 0;
                $new_act = (new MallActivity())->where([['act_id','=',$val['act_id']]])->findOrEmpty()->toArray();
                if($val['status']!=3&&($val['end_time']<time()||$new_act['end_time']<time())){
                    $res['bargain_status'] = 4;//失效
                }elseif($new_act&&$new_act['status']==2&&!in_array($val['status'],[2, 3])){
                    $res['bargain_status'] = 5;//失效,且不可操作
                }elseif ($val['status'] == 0 && $res['left_time'] > 0) {
                    if($val['bargain_can_pay']==1){
                        $res['can_go_pay'] = 1;
                    }
                    $res['bargain_status'] = 0;//进行中
                }elseif ($val['status'] == 0 && $res['left_time'] > 0) {
                    if($val['bargain_can_pay']==1){
                        $res['can_go_pay'] = 1;
                    }
                    $res['bargain_status'] = 0;//进行中
                } elseif ($val['status'] == 1) {
                    $res['bargain_status'] = 1;//已砍完,未下单
                } elseif ($val['status'] == 2) {
                    $res['bargain_status'] = 2;//已下单
                } elseif ($val['status'] == 3) {
                    $res['bargain_status'] = 3;//已支付
                } else {
                    if($new_act&&$new_act['is_del']==1){
                        $res['bargain_status'] = 5;
                    }else{
                        $res['bargain_status'] = 4;//失效
                    }
                }
                $res['stock'] = $val['act_stock_num'];
                $gd = (new MallGoods())->getOne($val['goods_id']);//获取商品信息
                if (empty($gd)) {
                    unset($list['list'][$key]);
                }else{
                    $res['left_bargain'] = get_format_number($gd['price'] - $val['floor_price'] - $val['bar_total_price'])<0?0:get_format_number($gd['price'] - $val['floor_price'] - $val['bar_total_price']);
                    $res['goods_image'] = $gd['image'] ? replace_file_domain($gd['image']) : '';
                    $list['list'][$key] = $res;
                }
            }
        }
        $list['list'] = array_values($list['list']);
        return $list;
    }

    public function getList($uid, $status, $page, $limit, $addr, $is_share, $goods_id, $act_id)
    {
        return (new MallNewBargainActModel())->getList($uid, $status, $page, $limit, $addr, $is_share, $goods_id, $act_id);

    }

    /**
     * @param $team_id
     * @return mixed
     * 发起砍价团队详情
     */
    public function getMyBargainDetail($team_id, $sku_id, $act_id, $uid)
    {
        $result = (new MallNewBargainTeam())->getMyBargainDetail($team_id, $act_id);
        if (empty($result)) {
            return false;
        }
        $status = $result['team_end'] - time();//再判断团队活动时间
        $activity_status = 0;//砍价完成标志，默认未完成

        if ($status < 0) {
            $result['activity_status'] = 4;//队伍过期
        }

        if ($result['activity_status'] >= 1 && $result['activity_status'] < 4) {
            $activity_status = 1;//砍价完成标志，完成
        }

        //记录是否在团队活动时间内
        if ($result['activity_end'] - time() <= 0) {//先判断活动的时间有效性
            $result['activity_status'] = 5;//主活动结束
        }

        //成功砍价人数
        $result['bargain_success'] = (new MallNewBargainTeam())->getBargainNumSucess();
        $result['bargain_member_list'] = (new MallNewBargainTeam())->getBargainSuccessList();//成功砍价队伍
        //备注，需要计划任务，砍价成功，5天没有下单就失效
        $result['scroll_status'] = 1;
        $sku_str = (new MallGoods())->getGoodsNameAndSku(0, $sku_id);
        $result['sku_str'] = "";//显示的规格名称信息
        $getTeamUserMsg[] = ['tid', '=', $team_id];
        $getTeamUserMsg[] = ['user_id', '=', $uid];
        $user_msg = (new MallNewBargainTeamUser())->getOne($getTeamUserMsg);
        $bar_act = (new MallNewBargainAct())->where(['id'=>$act_id])->findOrEmpty()->toArray();
        if($user_msg&&$bar_act&&$bar_act['help_bargain_times']==2){
            $help_bargain_times = (new MallNewBargainTeamUser())->where([
                ['bar_time','between',[strtotime(date('Y-m-d')),strtotime(date('Y-m-d').' 23:59:59')]],
                ['user_id','=',$uid],
                ['tid','=',$team_id],
            ])->count();
            if($help_bargain_times<=0&&$result['user_id']!=$uid){
                $user_msg = [];
            }
        }
        if (empty($user_msg) && $result['activity_status'] == 0 && $status > 0) {
            $result['bargain_status'] = 0;//队伍没有砍完，有效时间内。自己没有砍价 ：帮砍
        } elseif (empty($user_msg) && $result['activity_status'] > 0 && $result['activity_status'] <= 5) {
            //自己没有砍价，砍价已经完成：我也去砍价
            $result['bargain_status'] = 5;//我也去砍价
        } else {
            if ($result['activity_status'] == 0 && $user_msg['is_start'] == 1 && $status > 0) {
                $result['bargain_status'] = 1;//自己是发起者：砍价中，且自己已经砍过一刀，未完成，按钮状态是：找人砍价
            } elseif ($result['activity_status'] == 1 && $user_msg['is_start'] == 1) {
                //已经砍完，未支付，自己是发起者，按钮状态显示：去下单
                $result['bargain_status'] = 2;//去下单
            } elseif (($result['activity_status'] == 2 || $result['activity_status'] == 3) && $user_msg['is_start'] == 1) {
                //自己是发起者：砍价完成,已下单或者已经支付过且队伍没失效的砍价：查看详情
                $result['bargain_status'] = 3;//查看详情
            } elseif (($result['activity_status'] == 4 || ($result['activity_status'] == 5 && $status < 0 && $activity_status == 0)) && $user_msg['is_start'] == 1) {//队伍时效或者主活动超时，未砍成。队伍超时
                $result['bargain_status'] = 4;//活动或者队伍失效：查看更多砍价
            } elseif ($result['activity_status'] == 0 && $status < 0 && $user_msg['is_start'] == 1) {
                //队伍超时
                $result['bargain_status'] = 4;//查看更多砍价
            } elseif ($user_msg['is_start'] == 0) {
                //自己不是发起者,且已经帮砍，我也去砍价
                $result['bargain_status'] = 5;//我也去砍价
            }
        }
        if (!empty($sku_str)) {
            $result['sku_str'] = $sku_str['sku_str'];
        }
        if (count($result['bargain_member_list']) < 20) {
            $result['scroll_status'] = 0;
        }
        return $result;
    }


    /**
     * 砍价配置 添加 和 修改
     * User: chenxiang
     * Date: 2020/10/12 18:12
     * @param $data
     * @param string $type
     * @return bool
     */
    public function addBargainAct($data, $type = '')
    {
        $common_data = [];
        $common_data['mer_id'] = $data['mer_id'];
        $common_data['store_id'] = $data['store_id'];
        $common_data['start_time'] = strtotime($data['start_time']);
        $common_data['end_time'] = strtotime($data['end_time']) + 59; //结束时间 格式 Y-m-d H:i
        $common_data['type'] = 'bargain';
        $common_data['sort'] = $data['sort'];//优先级10最大,其次是20,30,40..
        $common_data['ext'] = json_encode($data['goods_info']);

        if (strtotime($data['start_time']) > time()) {
            $common_data['status'] = 0;
        } elseif (strtotime($data['start_time']) < time() && strtotime($data['end_time']) > time()) {
            $common_data['status'] = 1;
        } elseif (strtotime($data['start_time']) < time() && strtotime($data['end_time']) < time()) {
            $common_data['status'] = 2;
        }

        $arr_goods_info = $data['goods_info']; //商品相关信息
        $common_data['name'] = '砍价活动-' . $arr_goods_info['name'];
        $data['goods_id'] = $arr_goods_info['goods_id'];

        $add_data = [];
        $add_data['goods_id'] = $data['goods_id'];
        $add_data['affect_time'] = intval($data['affect_time']) * 3600; //砍价有效时间 单位s
        //首刀砍价最小比例 范围【1-90正整数】
        if ($data['bar_first_per_min'] < 1) {
            $bar_first_per_min = 1;
        } elseif ($data['bar_first_per_min'] > 90) {
            $bar_first_per_min = 90;
        } else {
            $bar_first_per_min = $data['bar_first_per_min'];
        }
        $add_data['bar_first_per_min'] = intval($bar_first_per_min);

        //首刀砍价最大比例 范围【1-90正整数】
        if ($data['bar_first_per_max'] < 1) {
            $bar_first_per_max = 1;
        } elseif ($data['bar_first_per_max'] > 90) {
            $bar_first_per_max = 90;
        } else {
            $bar_first_per_max = $data['bar_first_per_max'];
        }
        $add_data['bar_first_per_max'] = intval($bar_first_per_max);
        if ($add_data['bar_first_per_min'] > $add_data['bar_first_per_max']) {
            throw new \think\Exception('首刀比例最小值不能大于首刀比例最大值');
        }

        $add_data['help_bargain_people_num'] = intval($data['help_bargain_people_num']); //帮砍到低价的所需人数
        $add_data['buy_limit'] = empty($data['buy_limit']) ? 0 : $data['buy_limit'];//每人每件商品限购数量  默认0代表不限制件数
        $add_data['is_discount_share'] = $data['is_discount_share']; //是否优惠同享 1开启  2关闭
        if ($add_data['is_discount_share'] != 2) {
            $add_data['discount_card'] = $data['discount_card']; //优惠同享类型 商家会员卡
            $add_data['discount_coupon'] = $data['discount_coupon']; //优惠同享类型 商家优惠券
        }

        $add_data['bargain_can_pay'] = $data['bargain_can_pay']?:0;
        $add_data['help_bargain_times'] = $data['help_bargain_times']?:1;

        if (empty($data['id'])) { //添加
            $common_data['create_time'] = $_SERVER['REQUEST_TIME'];
            //启动事务
            Db::startTrans();
            try {
                $check = $this->_check_activity_goods($data);
                if ($check['status'] == 0) {
                    $new_bargain_act_id = $this->mallNewBargainActModel->add($add_data); // mall_new_bargain_act表id
                    if ($new_bargain_act_id) {
                        $common_data['act_id'] = $new_bargain_act_id;

                        $activity_id = $this->mallActivityModel->insertGetId($common_data);
                        if ($new_bargain_act_id && $activity_id) {
                            //更新砍价活动相关信息
                            $this->update_bargain_active($arr_goods_info['goods_id'], $activity_id, $new_bargain_act_id, $arr_goods_info);
                            //提交事务
                            Db::commit();
                            $res = ['status' => 0, 'msg' => '添加成功'];
                        } else {
                            //回滚事务
                            Db::rollback();
                            $res = ['status' => 1, 'msg' => '添加失败'];
                        }
                    }
                } else {
                    $res = ['status' => 1, 'msg' => $check['msg']];
                }
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
            }
        } else { //编辑
            if (empty($data['id'])) {
                throw new \think\Exception('活动ID不存在');
            }

            //启动事务
            Db::startTrans();
            try {
                $activity_id = $data['id']; //mall_activity表id

                $activity_info = $this->mallActivityModel->field('id, act_id, start_time, end_time')->where(['id' => $activity_id, 'type' => 'bargain'])->find();
                $old_goods = (new MallActivityDetailService())->getGoodsInAct(['activity_id' => $activity_id], 'goods_id');

                $check = [];
                $check['status'] = 0;
                if (!empty($old_goods)) {
                    //编辑活动时间或商品发生变化
                    if (($old_goods[0]['goods_id'] != $data['goods_info']['goods_id']) || ($common_data['start_time'] != $activity_info['start_time']) || ($common_data['end_time'] != $activity_info['end_time'])) {
                        //检测商品是否合法
                        $check = $this->_check_activity_goods($data);
                    }
                }
                if ($check['status'] == 0) {
                    $this->mallNewBargainActModel->updateBargain($add_data, ['id' => $activity_info['act_id']]);

                    (new MallNewBargainSku())->where(['act_id' => $activity_info['act_id']])->delete();
                    $this->mallActivityModel->where(['id' => $activity_id])->data($common_data)->update();

                    (new MallActivityDetailService())->delActiveDatailAll(['activity_id' => $activity_id]);
                    //更新砍价活动相关信息
                    $this->update_bargain_active($arr_goods_info['goods_id'], $activity_id, $activity_info['act_id'], $arr_goods_info);
                } else {
                    return $check;
                }
                //提交事务
                Db::commit();
                $res = ['status' => 0, 'msg' => '编辑成功'];
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
                $res = ['status' => 1, 'msg' => $e->getMessage()];
            }
        }
        return $res;
    }

    //校验活动商品是否合法
    private function _check_activity_goods($data)
    {
        //正在参加周期购活动的商品
        $mallGoodsService = new MallGoodsService();
        $spu_periodic = array_column($mallGoodsService->getPeriodicList($data['mer_id']), NULL, 'goods_id');

        //砍价活动商品信息
        $goods_sku = $data['goods_info'];

        $periodic_goods_id = $goods_sku['goods_id'];

        if (!empty($spu_periodic) && isset($spu_periodic[$periodic_goods_id])) {
            $msg = '商品' . $goods_sku['name'] . '正在参与周期购活动， 请重新添加活动商品';
            $res = ['status' => 1, 'msg' => $msg];
            return $res;
        }
        $types = [
            'group' => '拼团',
            'limited' => '秒杀',
            'bargain' => '砍价',
            'prepare' => '预售',
            'reached' => 'N元N价'
        ];

        $curr_time = time();
        $spu_where = [
            ['a.end_time', '>', $curr_time],
            ['a.status', '<>', 2],
            ['a.is_del', '=', 0],
            ['a.type', 'in', array_keys($types)],
            ['a.id', '<>', $data['id']],
            ['a.mer_id', '=', $data['mer_id']],
            ['a.store_id', '=', $data['store_id']],
            ['d.goods_id', 'in', [$periodic_goods_id]]
        ];

        $spu_field = 'd.goods_id,a.id,a.act_id,a.type,a.name as act_name,a.start_time,a.end_time';
        //所有正在参加活动的商品（不含周期购）
        $spu = (new MallActivityDetailService())->getGoodsInAct($spu_where, $spu_field);
        $start_time = strtotime($data['start_time']);
        $end_time = strtotime($data['end_time']) + 59; //砍价时间 Y-m-d H:i
        $res = ['status' => 0];
        foreach ($spu as $value) {
            $flag = is_time_cross($start_time, $end_time, $value['start_time'], $value['end_time']);
            if ($flag) {
                $start_time = date('Y-m-d', $value['start_time']);
                $end_time = date('Y-m-d', $value['end_time']);
                $msg = '商品：' . $goods_sku['name'] . '正在参与' . $start_time . '~' . $end_time . $types[$value['type']] . '活动，请重新添加活动商品';
                $res = ['status' => 1, 'msg' => $msg];
                break;
            }

        }

        return $res;


    }

    /**
     * 更新砍价活动相关信息
     * User: chenxiang
     * Date: 2020/11/7 17:18
     * @param $goods_id
     * @param int $activity_id
     * @param $goods_info
     */
    private function update_bargain_active($goods_id, $activity_id = 0, $new_bargain_act_id, $goods_info)
    {
        $activity_detail = [];
        $activity_detail['activity_id'] = $activity_id;
        $activity_detail['goods_id'] = $goods_id;

        (new MallActivityDetail())->insert($activity_detail);

        $data = [];
        $data['act_id'] = $new_bargain_act_id;
        $data['goods_id'] = $goods_id;

        if (!empty($goods_info['sku_info'])) { //多规格
            foreach ($goods_info['sku_info'] as $k => $v) {
                $data['sku_id'] = intval($v['sku_id']);
                $data['act_stock_num'] = intval($v['act_stock_num']);
                $data['act_price'] = number_format($v['act_price'], 2);

                //售卖数量
                if (!empty($data)) {
                    (new MallNewBargainSku())->insert($data);
                }
            }
        } else { //无sku_id
            $data['act_stock_num'] = intval($goods_info['act_stock_num']);
            $data['act_price'] = number_format($goods_info['act_price'], 2);
            //售卖数量
            if (!empty($data)) {
                (new MallNewBargainSku())->insert($data);
            }
        }
        return true;
    }

    /**
     * 获取活动数据
     * User: chenxiang
     * Date: 2020/11/9 9:23
     * @param $id
     * @return mixed
     */
    public function getInfoById($id)
    {
        if (empty($id)) {
            throw new \think\Exception('活动id为空');
        }

        $condition = [];
        $condition['m.type'] = 'bargain';
        $condition['m.is_del'] = 0;
        $condition['m.id'] = $id;

        $fields = 'b.*,m.name,m.act_type,m.desc,m.start_time,m.end_time,m.mer_id,m.store_id,m.status';
        $info = (new MallNewBargainAct())->getInfo($condition, $fields);

        //判断活动是否已经开始
        $info['is_began'] = time() >= $info['start_time'] ? 1 : 0;
        
        $info['start_time'] = date("Y-m-d H:i:s", $info['start_time']);
        $info['end_time'] = date("Y-m-d H:i:s", $info['end_time']);
        $info['affect_time'] = intval($info['affect_time'] / 3600);
        
        $bargain_act_sku = (new MallNewBargainSku())->where(['act_id' => $info['id']])->select()->toArray();
        if (empty($bargain_act_sku)) {
            throw new \think\Exception('活动商品规格信息不存在...', 1003);
        }
        //组装sku_id 活动库存 和 活动价格
        $add_arr = [];
        foreach ($bargain_act_sku as $kk => $item) {
            $add_arr[$item['sku_id']]['act_stock_num'] = $item['act_stock_num'];
            $add_arr[$item['sku_id']]['act_price'] = $item['act_price'];
        }

        $param = [];
        $param['goods_id'] = [$bargain_act_sku[0]['goods_id']]; //需要写成数组形式
        //$param['sku_id'] = $bargain_act_sku[0]['sku_id'];
        $param['type'] = 'bargain';
        $param['mer_id'] = $info['mer_id'];
        $param['store_id'] = $info['store_id'];
        $param['page'] = 1;
        $param['pageSize'] = 100;

        $goods_info = (new MallGoodsService())->getMallGoodsSelect($param);

        if(!empty($goods_info['list'])) {
            $goods_info['list'] = !is_array($goods_info['list'])?$goods_info['list']->toArray():$goods_info['list'];
            $sku_arr = $goods_info['list'][0]['sku_info'];
        } else {
            $sku_arr = [];
        }
        //$sku_arr = $goods_info['list'][0]['sku_info'];
        if(count($sku_arr) > 0) {
            foreach($sku_arr as $key => $value) {
                if(!empty($add_arr[$value['sku_id']])) {
                    $goods_info['list'][0]['sku_info'][$key]['act_stock_num'] = $add_arr[$value['sku_id']]['act_stock_num'];
                    $goods_info['list'][0]['sku_info'][$key]['act_price'] = $add_arr[$value['sku_id']]['act_price'];
                } else {
                    unset($goods_info['list'][0]['sku_info'][$key]);
                }
            }
        }
        /*if (count($sku_arr) > 0) { //有sku
            foreach ($sku_arr as $key => &$value) {
                if (!empty($add_arr[$value['sku_id']])) {
                    $goods_info['list'][0]['sku_info'][$key]['act_stock_num'] = $add_arr[$value['sku_id']]['act_stock_num'];
                    $goods_info['list'][0]['sku_info'][$key]['act_price'] = $add_arr[$value['sku_id']]['act_price'];
                } else {
                    unset($goods_info['list'][0]['sku_info'][$key]);
                }
            }
        } else { //无sku
            foreach ($sku_arr as $key => $value) {
                $goods_info['list'][0]['act_stock_num'] = $add_arr[$value['sku_id']]['act_stock_num'];
                $goods_info['list'][0]['act_price'] = $add_arr[$value['sku_id']]['act_price'];
            }
        }*/
        $info['goods_info'] = $goods_info['list'];
        return $info;
    }

    /**
     * @param $arr
     * @return mixed
     * 获取砍价活动及商品信息
     */
    public function getBargainActSkuMsg($arr)
    {
        $where[] = ['g.id', '=', $arr['act_id']];
        $where[] = ['m.sku_id', '=', $arr['sku_id']];
        $where[] = ['a.start_time', '<', time()];
        $where[] = ['a.end_time', '>=', time()];
        $where[] = ['a.type', '=', 'bargain'];
        $fields = 'g.affect_time,m.act_price,g.help_bargain_people_num,g.buy_limit,g.help_bargain_times,m.act_stock_num';
        $msg = (new MallNewBargainAct())->getBargainActSkuMsg($where, $fields);
        return $msg;
    }

    /**
     * 修改活动状态
     * User: chenxiang
     * Date: 2020/10/12 18:46
     * @param $data
     * @param $id
     * @return MallActivityModel
     */
    public function changeState($data, $id)
    {
        $where = [];
        $where['id'] = $id;
        $where['type'] = 'bargain';
        $field = 'd.goods_id';
        $detailIds = isset((new MallActivityDetailService())->getGoodsInAct(['d.activity_id' => $id], $field)[0]) ? (new MallActivityDetailService())->getGoodsInAct(['d.activity_id' => $id], $field)[0] : [];
        //如果该活动被装修，失效时删除对应的装修关联商品
        (new MallSixAdverGoods())->delSome([['goods_id', 'in', $detailIds]]);
        (new MallActivityDetailService())->delActiveDatailAll(['activity_id' => $id]);
        return $this->mallActivityModel->update($data, $where);
    }

    /**
     * 软删除 活动
     * User: chenxiang
     * Date: 2020/10/12 18:53
     * @param $data
     * @param $id
     * @return MallActivityModel
     */
    public function del($data, $id)
    {
        $where = [];
        $where['id'] = $id;
        $where['type'] = 'bargain';

        return $this->mallActivityModel->update($data, $where);
    }

}