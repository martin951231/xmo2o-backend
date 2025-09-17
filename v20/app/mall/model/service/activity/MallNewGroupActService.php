<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallActivity as MallActivityModel;
use app\mall\model\db\MallActivity;
use app\mall\model\db\MallNewGroupAct;
use app\mall\model\db\MallNewGroupSku;
use app\mall\model\db\MallNewGroupTeam;
use app\mall\model\db\MallNewGroupTeamUser;
use app\mall\model\db\MallOrderDetail;
use app\mall\model\db\MallRobotList;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallActivityDetail;
use app\mall\model\db\MallSixAdverGoods;
use app\mall\model\service\MallGoodsService;
use think\facade\Db;

class MallNewGroupActService
{
   public function getList($uid,$status,$page,$limit,$addr){
       return (new MallNewGroupAct())->getList($uid,$status,$page,$limit,$addr);
   }

   //专题页推荐的拼团活动
    public function getRecGroupList($uid,$status,$page,$limit,$addr){
        return (new MallNewGroupAct())->getRecGroupList($uid,$status,$page,$limit,$addr);
    }

    /**
     * @param $act_id
     * @param $tid
     * @param $uid
     * 判断用户是否有参团
     * @author mrdeng
     */
   public function getUserActStatus($act_id,$tid,$uid){
       $return=(new MallNewGroupTeamUser())->getUserActStatus($act_id,$tid,$uid);
       if(empty($return)){
           return 0;
       }else{
           return 1;//有参团
       }
   }

    //拼团连接详情
   public function group_detail($uid,$orderid,$groupid,$teamid,$uid1){
       $return=(new MallNewGroupAct())->group_detail($uid,$orderid,$groupid,$teamid,$uid1);
       return $return;
   }

    //获取拼主列表
   public function getUserList($groupid,$uid,$orderid){
       $return=(new MallNewGroupTeamUser())->getUserList($groupid,$uid,$orderid);
       return $return;
   }

   //获取订单详情信息
    public function getOrderDetailOne($orderid){
        return (new MallOrderDetail())->getOne($orderid);
    }

    //获取拼团团队信息
    public function getMallGroupTeam($teamid){
        return (new MallNewGroupTeam())->getOne($teamid);
    }

    //获取拼团团队信息
    public function getActGroup($where){
        return (new MallActivity())->getOne($where);
    }
    //获取拼团活动信息
    public function getBase($actid){
        return (new MallNewGroupAct())->getBase($actid);
    }

    //建立拼团团队
    public function addGroup($data){
        return (new MallNewGroupTeam())->insertGroup($data);
    }

    //加入拼团用户表
    public function addTeamUser($data){
        return (new MallNewGroupTeamUser())->addTeamUser($data);
    }

    /**
     * 获取团购列表
     * User: chenxiang
     * Date: 2020/11/2 20:55
     * @param $store_id
     * @param $param
     * @return mixed
     */
    public function getGroupList($store_id, $param) {
       if(empty($store_id)) {
           throw new \think\Exception('店铺id为空');
       }

        $page = isset($param['page']) ? $param['page'] : 1;
        $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 10;//每页限制
        $name = isset($param['name']) ? trim($param['name'], '') : '';
        $status = isset($param['status']) ? $param['status']:3;//活动状态 0未开始 1进行中 2已失效 3 全部
        $startTime = !empty($param['start_time']) ? strtotime($param['start_time']. " 00:00:00") : '';//开始时间
        $endTime = !empty($param['end_time']) ? strtotime($param['end_time']. " 23:59:59") : '';//结束时间

        if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
            throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
        }
        //搜索条件
        $where = [];
        $where[] = ['a.store_id', '=', $store_id];
        if($name) {
            $where[] = ['a.name', 'like', '%'.$name.'%'];
        }

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
            $whereOr[] = ['a.store_id', '=', $store_id];
            $whereOr[] = ['a.is_del', '=', 0];
            $whereOr[] = ['a.type', '=', 'group'];
        }else{
            if (in_array($status, [0, 1, 2])) {//0未开始 1进行中 2已失效 3全部活动
                 $where[] = ['a.status', '=', $status];
            }
        }

        $where[] = ['a.is_del', '=', 0];
        $where[] = ['a.type', '=', 'group'];
        $prefix = config('database.connections.mysql.prefix');
        $mallActivityModel = new MallActivityModel();
        $fields = 'a.id, a.store_id, a.act_id, a.start_time, a.end_time, a.status, g.name as goods_name, g.image as goods_image, gr.goods_id';
        $list = $mallActivityModel->alias('a')
            ->join([$prefix . 'mall_new_group_act' => 'gr'], 'a.act_id=gr.id', 'LEFT')
            ->join([$prefix . 'mall_goods' => 'g'], 'g.goods_id=gr.goods_id', 'LEFT')
            ->field($fields)
            ->where($where);
        if(!empty($whereOr) && $status==2){
            $list = $list->where(function ($query) use ($where) {
                $query->where($where);
            })->whereOr(function ($query) use ($whereOr) {
                $query->where($whereOr);
            });
        }
        $count=$list->count();
        $list=$list->order('a.id DESC')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        if(!empty($list)){
            foreach ($list as &$value) {
                $value['goods_image']=replace_file_domain($value['goods_image']);
                if ($value['start_time'] < time() && $value['end_time'] < time()) {
                    $value['status'] = 2;
                }
                if ($value['start_time'] < time() && $value['end_time'] > time() && $value['status'] != 2) {
                    $value['status'] = 1;
                }
                $value['start_time'] = date('Y-m-d H:i:s', $value['start_time']);
                $value['end_time'] = date('Y-m-d H:i:s', $value['end_time']);
                switch($value['status']) { //活动状态 0未开始 1进行中 2已失效
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

                $condition['store_id'] = $value['store_id'];
                $condition['act_id'] = $value['act_id'];


                $condition_where = [];
                $condition_where[] = ['o.store_id', '=', $store_id];
                $condition_where[] = ['goods_activity_type', '=', 'group'];
                $condition_where[] = ['goods_activity_id', '=', $value['act_id']];
                $condition_where[] = ['o.status', '>=', 10];
                $condition_where[] = ['o.status', '<=', 49];
                $total = (new MallOrder())->getList($fields = 'o.uid,o.money_real,o.money_goods,o.money_freight', $condition_where);
                $real_income = array_sum(array_column($total, 'money_real'));
                $money_freight = array_sum(array_column($total, 'money_freight'));
//                $total = array_column($total, NULL, 'uid');
                $value['pay_num'] = count($total);//支付单数
                $value['real_income'] = get_format_number($real_income-$money_freight);//实收金额
                $value['complete_num'] = count($total);//成团人数（支付人数）
                $value['qrcode'] = $this->createGoodsQrcode($value['goods_id']);
            }
        }else{
            $list=[];
        }

        $res['list'] = $list;
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
     * 商家后台添加 拼团活动
     * User: chenxiang
     * Date: 2020/9/18 16:24
     * @param $data
     */
    public function addGroupAct($data, $type = '') {

        $common_data = [];
        $common_data['mer_id'] = $data['mer_id'];
        $common_data['store_id'] = $data['store_id'];
        $common_data['start_time'] = strtotime($data['start_time']);
        $common_data['end_time'] = strtotime($data['end_time']);
        $common_data['type'] = 'group';
        $common_data['sort'] = 10; //优先级10最大,其次是20,30,40..
//        $common_data['desc'] = '';
        $common_data['ext'] = json_encode($data['goods_info']);

        if (strtotime($data['start_time']) > time()) {
            $common_data['status'] = 0;
        } elseif (strtotime($data['start_time']) < time() && strtotime($data['end_time']) > time()) {
            $common_data['status'] = 1;
        } elseif (strtotime($data['start_time']) < time() && strtotime($data['end_time']) < time()) {
            $common_data['status'] = 2;
        }

        $arr_goods_info = $data['goods_info']; //商品相关信息
        $common_data['name'] = '拼团活动-'.$arr_goods_info['name'];
        $data['goods_id'] = $arr_goods_info['goods_id'];

        $add_group = [];
        $add_group['goods_id'] = $arr_goods_info['goods_id'];
        $add_group['affect_time'] = intval($data['affect_time']); //拼团有效时间 单位s
        $add_group['complete_num'] = intval($data['complete_num']); //成团人数
        $add_group['limit_num'] = intval($data['limit_num']);//	每人限购数量
        $add_group['is_open_group'] = $data['is_open_group']; //是否开启模拟参团0关闭 1开启
        $add_group['machine_into_time'] = $data['machine_into_time'];//机器人介入成团时间  单位s
        $add_group['simulate_group_num'] = $data['simulate_group_num']; //机器人参与人数大于等于几人的团；若为空，机器人则参与所有未拼成的团

        $add_group['team_discount_price'] = number_format($data['team_discount_price'],2);
        $add_group['is_discount_share'] = $data['is_discount_share']; //是否优惠同享 1开启  2关闭
        if($add_group['is_discount_share'] != 2) {
            $add_group['discount_card'] = $data['discount_card']; //优惠同享类型 商家会员卡
            $add_group['discount_coupon'] = $data['discount_coupon']; //优惠同享类型 商家优惠券
        }

        if(empty($data['id'])) { //添加
            $common_data['create_time'] = $_SERVER['REQUEST_TIME'];
            //启动事务
            Db::startTrans();
            try{
                $check = $this->_check_activity_goods($data);
                if($check['status'] == 0) {
                    $act_id = (new MallNewGroupAct())->add($add_group);

                    if($act_id) {
                        $common_data['act_id'] = $act_id;
                        $activity_id = (new MallActivityModel())->insertGetId($common_data);
                        //更新拼团活动相关信息
                        $this->update_group_active($arr_goods_info['goods_id'], $activity_id, $act_id, $arr_goods_info);

                        //提交事务
                        Db::commit();
                        $res = ['status' => 0, 'msg' => '添加成功'];
                    }
                } else {
                    //回滚事务
                    Db::rollback();
                    $res = ['status' => 1, 'msg' => '添加失败!'.$check['msg']];
                }
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
                $res = ['status' => 0, 'msg' => '添加失败'];
            }
        } else { //编辑
            if(empty($data['id'])) {
                throw new \think\Exception('活动ID不存在');
            }

            //启动事务
            Db::startTrans();
            try{
                $activity_id = $data['id']; //mall_activity表id
                $activity_info = (new MallActivityModel())->field('id,act_id,start_time,end_time')->where(['id' => $activity_id, 'type' => 'group'])->find();

                $old_goods = (new MallActivityDetailService())->getGoodsInAct(['activity_id' => $activity_id], 'goods_id');

                $check = [];
                $check['status'] = 0;
                //编辑活动时间或商品发生变化
                if(!empty($old_goods)){
                    if (($old_goods[0]['goods_id'] != $data['goods_info']['goods_id']) || ($common_data['start_time'] != $activity_info['start_time']) || ($common_data['end_time'] != $activity_info['end_time'])) {
                        $check = $this->_check_activity_goods($data);
                    }
                }
                if($check['status'] == 0) {
                    (new MallNewGroupAct())->updateGroup($add_group, ['id' => $activity_info['act_id']]);
                    (new MallNewGroupSku())->where(['act_id' => $activity_info['act_id']])->delete();
                    (new MallActivityModel())->where(['id'=>$activity_id])->data($common_data)->update();
                    (new MallActivityDetailService())->delActiveDatailAll(['activity_id' => $activity_id]);

                    //更新拼团活动相关信息
                    $this->update_group_active($arr_goods_info['goods_id'],$activity_id,$activity_info['act_id'],$arr_goods_info);
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
        //正在参加周期购的活动的商品
        $mallGoodsService = new MallGoodsService();
        $spu_periodic = array_column($mallGoodsService->getPeriodicList($data['mer_id']), NULL, 'goods_id');

        //团购活动商品信息
        $group_goods_id = $data['goods_info']['goods_id'];

        if (!empty($spu_periodic) && isset($spu_periodic[$group_goods_id])) {
            $msg = '商品：' . $data['goods_info']['name'] . '正在参与周期购活动，请重新添加活动商品';
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
        $curr_time = $_SERVER['REQUEST_TIME'];
        $spu_where = [
            ['a.end_time', '>', $curr_time],
            ['a.status', '<>', 2],
            ['a.is_del', '=', 0],
            ['a.id', '<>', $data['id']],
            ['a.mer_id','=',$data['mer_id']],
            ['a.store_id','=',$data['store_id']],
            ['a.type', 'in', array_keys($types)],
            ['d.goods_id', 'in', [$group_goods_id]]
        ];
        $spu_field = 'd.goods_id,a.id,a.act_id,a.type,a.name as act_name,a.start_time,a.end_time';
        //所有正在参加活动的商品（不含周期购）
        $spu = (new MallActivityDetailService())->getGoodsInAct($spu_where, $spu_field);

        $start_time = strtotime($data['start_time'] . " 00:00:00");
        $end_time = strtotime($data['end_time'] . " 23:59:59");
        $res = ['status' => 0];
        if($spu) {
            foreach ($spu as $value) {
                $flag = is_time_cross($start_time, $end_time, $value['start_time'], $value['end_time']);
                if ($flag) {
                    $start_time = date('Y-m-d', $value['start_time']);
                    $end_time = date('Y-m-d', $value['end_time']);
                    $msg = '商品：' . $data['goods_info']['name'] . '正在参与' . $start_time . '~' . $end_time . $types[$value['type']] . '活动，请重新添加活动商品';
                    $res = ['status' => 1, 'msg' => $msg];
                    break;
                }
            }
        }

        return $res;
    }


    /**
     * 根据id 获取活动数据
     * User: chenxiang
     * Date: 2020/11/2 10:44
     * @param $id
     * @return array
     */
    public function getInfoById($id) {
        if (empty($id)) {
            throw new \think\Exception('活动id为空');
        }

        $condition = [];
        $condition['m.type'] = 'group';
        $condition['m.is_del'] = 0;
        $condition['m.id'] = $id;

        $fields = 'g.*,m.name,m.act_type,m.desc,m.start_time,m.end_time,m.mer_id,m.store_id,m.status';

        $group_info = (new MallNewGroupAct())->getInfo($condition, $fields);

        if (empty($group_info)) {
            throw new \think\Exception('活动商品数据不存在...', 1003);
        }

        $group_info['start_time'] = date("Y-m-d H:i:s", $group_info['start_time']);
        $group_info['end_time'] = date("Y-m-d H:i:s", $group_info['end_time']);

        $group_act_sku = (new MallNewGroupSku())->where(['act_id'=>$group_info['id']])->select()->toArray();
        if (empty($group_act_sku)) {
            throw new \think\Exception('活动商品规格信息不存在...', 1003);
        }
        //组装 sku_id  活动库存 拼团价格
        $add_arr = [];
        foreach ($group_act_sku as $kk => $item) {
            $add_arr[$item['sku_id']]['act_stock_num'] = $item['act_stock_num'];
            $add_arr[$item['sku_id']]['act_price'] = $item['act_price'];
        }

        $param = [];

        $param['goods_id'] = [$group_act_sku[0]['goods_id']]; //需要写成数组形式
        //$param['sku_id'] = $group_act_sku['sku_id'];
        $param['type'] = 'group';
        $param['mer_id'] = $group_info['mer_id'];
        $param['store_id'] = $group_info['store_id'];
        $param['page'] = 1;
        $param['pageSize'] = 100;

        $goods_info = (new MallGoodsService())->getMallGoodsSelect($param);

        if(!empty($goods_info['list'])) {
            $sku_arr = $goods_info['list'][0]['sku_info'];
        } else {
            $sku_arr = [];
        }

        if(count($sku_arr) > 0) {
            if(is_object($goods_info['list'])){
                $goods_info['list'] = $goods_info['list']->toArray();
            }
            foreach($sku_arr as $key => $value) {
                if(!empty($add_arr[$value['sku_id']])) {
                    $goods_info['list'][0]['sku_info'][$key]['act_stock_num'] = $add_arr[$value['sku_id']]['act_stock_num'];
                    $goods_info['list'][0]['sku_info'][$key]['act_price'] = $add_arr[$value['sku_id']]['act_price'];
                } else {
                    unset($goods_info['list'][0]['sku_info'][$key]);
                }
            }
        }

        $group_info['goods_info'] = $goods_info['list'];
        return $group_info;

    }

    /**
     * 修改活动状态
     * User: chenxiang
     * Date: 2020/10/13 19:02
     * @param $data
     * @param $id
     * @return MallActivityModel
     */
    public function changeState($data, $id) {
        $where = [];
        $where['id'] = $id;
        $where['type'] = 'group';
        $field = 'd.goods_id';
        $detailIds = isset((new MallActivityDetailService())->getGoodsInAct(['d.activity_id' => $id],$field)[0])?(new MallActivityDetailService())->getGoodsInAct(['d.activity_id' => $id],$field)[0]:[];
        //如果该活动被装修，失效时删除对应的装修关联商品
        (new MallSixAdverGoods())->delSome([['goods_id','in',$detailIds]]);
        (new MallActivityDetailService())->delActiveDatailAll(['activity_id' => $id]);
        return (new MallActivityModel())->update($data, $where);
    }

    /**
     * 软删除 活动
     * User: chenxiang
     * Date: 2020/10/13 19:04
     * @param $data
     * @param $id
     * @return mixed
     */
    public function del($data, $id) {
        $where = [];
        $where['id'] = $id;
        $where['type'] = 'group';
        return (new MallActivityModel())->update($data, $where);
    }


    /**
     * 获取模拟机器人列表
     * User: chenxiang
     * Date: 2020/9/19 11:56
     * @param bool $field
     * @param array $where
     * @param string $sort
     * @param int $page
     * @param int $pageSize
     * @return mixed
     */
    public function getRobotList($field = true, $where = [], $sort = '', $page = 1, $pageSize = 20) {
        $count = (new MallRobotList())->where($where)->count();

        if ($count < 1) {
            $list = [];
        } else {
            $list = (new MallRobotList())->field($field)->where($where)->page($page,$pageSize)->order($sort)->select()->toArray();
            foreach ($list as $key => $value) {
                $list[$key]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
				$list[$key]['avatar'] = replace_file_domain($value['avatar']);
            }
        }

        $res['list'] = $list;
        $res['total'] = $count;
        $res['page'] = $page;
        return $res;
    }

    /**
     * 添加 / 编辑 模拟机器人
     * User: chenxiang
     * Date: 2020/9/19 9:57
     * @param $data
     * @param string $type  add 添加   edit编辑
     * @return MallRobotList|bool|int|string
     */
    public function addRobot($data, $type = '') {
        switch ($type) {
            case 'add':
                $res = (new MallRobotList())->insert($data);
                break;
            case 'edit':
                $res = (new MallRobotList())->where('id', $data['id'])->update($data);
                break;
            default:
                $res = false;
        }
        return $res;

    }

    /**
     * 软删除 机器人
     * User: chenxiang
     * Date: 2020/9/19 10:05
     * @param $data
     * @param $id
     * @return MallRobotList
     */
    public function delRobot($data, $id) {
        return (new MallRobotList())->where('id', $id)->delete();
    }

    /**
     * 更新拼团活动相关信息
     * User: chenxiang
     * Date: 2020/11/3 14:09
     * @param $goods_id
     * @param int $activity_id
     */
    private function update_group_active($goods_id, $activity_id = 0, $act_id = 0, $goods_info)
    {
        $activity_detail = [];
        $activity_detail['activity_id'] = $activity_id;
        $activity_detail['goods_id'] = $goods_id;

        (new MallActivityDetail())->insert($activity_detail);

        $data = [];
        $data['act_id'] = $act_id;
        $data['goods_id'] = $goods_id;

        if(!empty($goods_info['sku_info'])) { //多规格
            foreach($goods_info['sku_info'] as $k => $v) {
                $data['sku_id'] = intval($v['sku_id']);
                $data['act_stock_num'] = intval($v['act_stock_num']);
                $data['act_price'] = get_format_number($v['act_price'], 2);

                if(!empty($data)) {
                    (new MallNewGroupSku())->insert($data);
                }
            }
        } else { //单规格 或者 无规格

            $data['act_stock_num'] = intval($goods_info['act_stock_num']);
            $data['act_price'] = get_format_number($goods_info['act_price'], 2);

            if(!empty($data)) {
                (new MallNewGroupSku())->insert($data);
            }
        }

        return true;
    }
}