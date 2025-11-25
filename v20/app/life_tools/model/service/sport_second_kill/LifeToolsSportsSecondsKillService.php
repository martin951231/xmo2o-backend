<?php

/**
 * 体育限时秒杀活动
 */
namespace app\life_tools\model\service\sport_second_kill;

use app\life_tools\model\db\LifeToolsSportsSecondsKill;
use app\life_tools\model\db\LifeToolsSportsSecondsKillTicketDetail;
use app\life_tools\model\db\LifeToolsSportsSecondsKillDetail;
use app\life_tools\model\db\LifeToolsSportsSecondsKillTicketSku;
use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsTicketSku;
use app\life_tools\model\service\LifeToolsTicketService;
use app\mall\model\db\MallLimitedSku;
use think\facade\Db;
use think\Model;

class LifeToolsSportsSecondsKillService
{
    public $prefix;

    public function __construct()
    {
        $this->prefix = config('database.connections.mysql.prefix');
        $this->LifeToolsSportsSecondsKillDetailModel = new LifeToolsSportsSecondsKillDetail();
        $this->LifeToolsSportsSecondsKillModel = new LifeToolsSportsSecondsKill();
    }

    //限时优惠列表
    public function activityList($cate_id, $page, $uid = 0,$pageSize=10,$group="k.goods_id",$source='',$goods_id=0)
    {
        //print_r($cate_id);exit;
        return $this->LifeToolsSportsSecondsKillDetailModel->getLimitedList($cate_id, $page, $uid,$pageSize,$group,$source,$goods_id);
    }

    /**
     * 商家后台-限时优惠列表
     * Date: 2022/02/17 
     * @param array $param
     * @return mixed
     */
    public function getSecondsKillList($param)
    {
        if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
            throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
        }

        // 商家id
        $merId = $param['mer_id'];

        $name = !empty($param['name']) ? trim($param['name'], '') : '';
        $status = isset($param['status']) ? $param['status'] : '';//活动状态 0未开始 1进行中 2已失效
        $startTime = !empty($param['start_time']) ? strtotime($param['start_time'] . " 00:00:00") : '';//开始时间
        $endTime = !empty($param['end_time']) ? strtotime($param['end_time'] . " 23:59:59") : '';//结束时间
        if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
            throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
        }
        // 搜索条件
        $where = [];
        $where[] = ['a.mer_id', '=', $merId];
        $where[] = ['a.type', '=', 'limited'];
        $where[] = ['a.is_del', '=', 0];
        if ($name) {
            $where[] = ['a.name', 'like', '%' . $name . '%'];
        }

        //活动状态   //0未开始 1进行中 2已失效 3全部活动
        if($startTime && $endTime){
            $where[] = ['a.start_time', '>=', $startTime];
            $where[] = ['a.end_time', '<', $endTime];
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
            $whereOr[] = ['a.mer_id', '=', $merId];
            $where[] = ['a.end_time', '<', time()];
            $whereOr[] = ['a.status', '=', 2];
            $whereOr[] = ['a.is_del', '=', 0];
            $whereOr[] = ['a.type', '=', 'limited'];
        }else{
            if (in_array($status, [0, 1, 2])) {//0未开始 1进行中 2已失效 3全部活动
                $where[] = ['a.status', '=', $status];
            }
        }

        $fields = 's.id,s.time_type,s.cycle_start_time,s.cycle_end_time,a.`name`,a.start_time,a.end_time,a.`status`,a.desc,a.act_id,d.ticket_id';
        $list = $this->LifeToolsSportsSecondsKillDetailModel->alias('s')
            ->join([$this->prefix . 'life_tools_sports_seconds_kill' => 'a'], 's.id = a.act_id', 'LEFT')
            ->join([$this->prefix . 'life_tools_sports_seconds_kill_ticket_detail' => 'd'], 'd.activity_id = a.id', 'LEFT')
            ->field($fields)
            ->where($where);
        if(!empty($whereOr) && $status==2){
            $list = $list->where(function ($query) use ($where) {
                $query->where($where);
            })->whereOr(function ($query) use ($whereOr) {
                $query->where($whereOr);
            });
        }
        $list=$list->group('s.id')->order('a.sort ASC,a.id DESC')
            ->select()
            ->toArray();

        if(!empty($list)){
            foreach ($list as &$value) {
                $act_start_time=$value['start_time'];
                $act_end_time=$value['end_time'];
                if ($value['start_time'] < time() && $value['end_time'] < time()) {
                    $value['status'] = 2;
                }
                if ($value['start_time'] <= time() && $value['end_time'] > time() && $value['status'] != 2) {
                    $value['status'] = 1;
                }
                if ($value['time_type'] == 1) {
                    $value['start_time'] = date('Y-m-d H:i', $value['start_time']);
                    $value['end_time'] = date('Y-m-d H:i', $value['end_time']);
                } else {
                    $stime = strtotime(date('Y-m-d 00:00:00', $value['start_time'])) + $value['cycle_start_time'];
                    $etime = strtotime(date('Y-m-d 00:00:00', $value['end_time'])) + $value['cycle_end_time'];
                    $value['start_time'] = date('Y-m-d H:i', $stime);
                    $value['end_time'] = date('Y-m-d H:i', $etime);
                }


                $condition_where = [];
                $condition_where[] = ['o.mer_id', '=', $merId];
                $condition_where[] = ['o.ticket_id', '=', $value['ticket_id']];
                $condition_where[] = ['o.order_status', '>=', 20];
                $condition_where[] = ['o.order_status', '<=', 49];
                $condition_where[] = ['o.add_time', '>=', $act_start_time];
                $condition_where[] = ['o.add_time', '<', $act_end_time];
                $total = (new LifeToolsOrder())->getList($condition_where,[],$fields = 'o.uid,o.price')['data'];
                $real_income = array_sum(array_column($total, 'price'));
                $value['pay_order_num'] = count($total);//支付单数
                $value['real_income'] = number_format($real_income, 2);//实收金额
                $total = array_column($total, NULL, 'uid');
                $value['pay_order_people'] = count($total);//支付人数
            }
        }else{
            $list=[];
        }
        $res['list'] = $list;
        $res['count'] = count($list);
        return $res;
    }


    /**
     * 商家后台--限时优惠  添加 / 编辑
     * User: chenxiang
     * Date: 2020/10/26 14:17
     * @param $data
     * @param string $type
     * @return bool
     */
    public function saveSecondsKill($data, $type = '')
    {
        $id = $data['id'] ?? 0; // 活动id

        //商品活动信息
        $common_data = [];
        $common_data['mer_id'] = $data['mer_id'];
        $common_data['name'] = $data['name'];
        $common_data['sort'] = $data['sort'];
        if ($data['time_type'] == 1) {
            $start_time = strtotime($data['start_time']);
            $end_time = strtotime($data['end_time']);
        } else {
            $start_time = strtotime($data['start_time']);
            $end_time = strtotime($data['end_time']);
        }
        $common_data['start_time'] = $start_time;
        $common_data['end_time'] = $end_time;
        $common_data['type'] = 'limited';

        if ($start_time > time()) {
            $common_data['status'] = 0;
        } elseif ($start_time < time() && $end_time > time()) {
            $common_data['status'] = 1;
        } elseif ($start_time < time() && $end_time < time()) {
            $common_data['status'] = 2;
        }

        $add_data = [];
        $add_data['time_type'] = intval($data['time_type']);  //秒杀类型   1.按固定时间 2.按周期
        $add_data['cycle_type'] = intval($data['cycle_type']);    //周期类型 1每日 2每周 3每月
        $add_data['cycle_date'] = $data['cycle_date']; //每日类型为空，每周类型表示周几（周日用0表示），每月类型表示几号\r\n周期所选日期（以英文逗号隔开的数字字符串）：每日类型为空，每周类型表示周几（周日用0表示），每月类型表示几号
        $add_data['cycle_start_time'] = $data['time_type'] == 1 ? 0 : time_to_second($data['cycle_start_time']);//周期-开始时间 单位s
        $add_data['cycle_end_time'] = $data['time_type'] == 1 ? 0 : time_to_second($data['cycle_end_time']);//周期-开始时间 单位s
        $add_data['buy_limit'] = intval($data['buy_limit']); //限购数量 0则表示不限购 范围0-999

        $add_data['is_discount_share'] = intval($data['is_discount_share']); //	是否优惠同享 1开启 2关闭
        $add_data['notice_type'] = intval($data['notice_type']);
        $add_data['notice_time'] = $data['notice_type'] == 1 ? 0 : intval($data['notice_time']);
        $add_data['stock_type'] = intval($data['stock_type']);
        $data['goods_sku'] = $data['goods_sku']; //商品相关信息 json
        if (empty($id)) { //添加
            $common_data['create_time'] = $_SERVER['REQUEST_TIME'];
            //启动事务
            Db::startTrans();
            try {
                $check = $this->_check_activity_goods($data);
                if ($check['status'] == 0) {
                    $insert_id = $this->LifeToolsSportsSecondsKillDetailModel->add($add_data);
                    $common_data['act_id'] = $insert_id;
                    $activity_id = $this->LifeToolsSportsSecondsKillModel->add($common_data);
                    if ($insert_id && $activity_id) {
                        $this->updateLimitedSku($data['goods_sku'], $insert_id, $activity_id);
                        //提交事务
                        Db::commit();
                        $res = ['status' => 0, 'msg' => '添加成功'];
                    } else {
                        //回滚事务
                        Db::rollback();
                        throw new \think\Exception(L_('添加失败'), 1003);
                    }
                } else {
                    Db::rollback();
                    throw new \think\Exception($check['msg'], 1003);
                }
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
                throw new \think\Exception(L_('添加失败'), 1003);
            }
        } else { //编辑
            if (empty($data['id'])) {
                throw new \think\Exception('活动ID不存在');
            }
            //启动事务
            Db::startTrans();
            try {
                $act_id = $data['id'];
                //获取活动信息
                $activity_info = (new LifeToolsSportsSecondsKill())->getActInfo(['act_id' => $act_id, 'type' => $data['type']], 'id,start_time,end_time');
                $old_start_time = date('Y-m-d', $activity_info[0]['start_time']);
                $old_end_time = date('Y-m-d', $activity_info[0]['end_time']);
                $activity_id = $activity_info[0]['id'];
                //编辑前的活动商品信息
                $old_activity_goods = (new LifeToolsSportsSecondsKillTicketSku())->getListBySkuId(['act_id' => $act_id]);
                $old_data = [];
                foreach ($old_activity_goods as $old) {
                    $old_key = $old['ticket_id'] . '-' . $old['id'];
                    $arr['is_first'] = $old['is_first'];
                    $arr['sort'] = $old['sort'];
                    $old_data[$old_key] = $arr;
                }
                $old_goods_id = array_column($old_activity_goods, 'ticket_id');
                $new_goods_id = array_column($data['goods_sku'], 'ticket_id');
                $diff = array_diff($old_goods_id, $new_goods_id);
                if (!empty($diff) || ($old_start_time !== $data['start_time']) || ($old_end_time !== $data['end_time'])) {
                    $check = $this->_check_activity_goods($data);
                    if ($check['status'] == 0) {
                        $this->LifeToolsSportsSecondsKillDetailModel->update($add_data, ['id' => $act_id]);
                        (new LifeToolsSportsSecondsKillTicketSku())->where(['act_id' => $act_id])->delete();
                        $this->LifeToolsSportsSecondsKillModel->where(['id' => $activity_id])->data($common_data)->update();
                        (new LifeToolsSportsSecondsKillTicketDetail())->delActiveDatailAll(['activity_id' => $activity_id]);
                        //处理商品sku
                        $this->updateLimitedSku($data['goods_sku'], $data['id'], $activity_id, $old_data);
                        //提交事务
                        Db::commit();
                        $res = ['status' => 0, 'msg' => '编辑成功'];
                    } else {
                        throw new \think\Exception($check['msg'], 1003);
                    }
                } else {
                    $this->LifeToolsSportsSecondsKillDetailModel->update($add_data, ['id' => $act_id]);
                    (new LifeToolsSportsSecondsKillTicketSku())->where(['act_id' => $act_id])->delete();
                    $this->LifeToolsSportsSecondsKillModel->where(['id' => $activity_id])->data($common_data)->update();
                    (new LifeToolsSportsSecondsKillTicketDetail())->delActiveDatailAll(['activity_id' => $activity_id]);
                    //处理商品sku
                    $this->updateLimitedSku($data['goods_sku'], $data['id'], $activity_id, $old_data);
                    //提交事务
                    Db::commit();
                    $res = ['status' => 0, 'msg' => '编辑成功'];
                }
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
                throw new \think\Exception('编辑失败', 1003);
            }
        }
        return $res;
    }

    //校验活动商品是否合法
    private function _check_activity_goods($data)
    {
        $goods_sku = array_column($data['goods_sku'], NULL, 'goods_id');
        $limited_goods_id = array_column($goods_sku, 'ticket_id');
        $res = ['status' => 0];
        $status = 1;
        if ($status == 1) {
            $types = [
                'limited' => '秒杀',
            ];

            $curr_time = time();
            $spu_where = [
                ['a.end_time', '>', $curr_time],
                ['a.status', '<>', 2],
                ['a.is_del', '=', 0],
                ['a.act_id', '<>', $data['id']],//秒杀传的是act_id
                ['a.mer_id', '=', $data['mer_id']],
                ['a.type', 'in', array_keys($types)],
                ['d.ticket_id', 'in', $limited_goods_id]
            ];

            $spu_field = 'd.ticket_id,a.id,a.act_id,a.type,a.name as act_name,a.start_time,a.end_time';
            //所有正在参加活动的商品（不含周期购）
            $spu = (new LifeToolsSportsSecondsKillTicketDetail())->getGoodsInAct($spu_where, $spu_field);
            if (!empty($spu)) {
                $start_time = strtotime($data['start_time'] . " 00:00:00");
                $end_time = strtotime($data['end_time'] . " 23:59:59");
                foreach ($spu as $value) {
                    $flag = is_time_cross($start_time, $end_time, $value['start_time'], $value['end_time']);
                    if ($flag) {
                        $stime = date('Y-m-d', $value['start_time']);
                        $etime = date('Y-m-d', $value['end_time']);
                        if (in_array($value['ticket_id'], $limited_goods_id)) {
                            $msg = $goods_sku[$value['ticket_id']]['name'] . '已参与' . $stime . '~' . $etime . '的' . $value['act_name'] . '活动';
                            $res = ['status' => 1, 'msg' => $msg];
                            break;
                        }
                    }
                }
            }

        }

        return $res;

    }


    public function updateLimitedSku($goods_sku, $act_id = 0, $activity_id = 0, $old_data = array())
    {
        $activity_detail = $goods = [];
        foreach ($goods_sku as $val) {
            //单个商品多个sku
            $activity['activity_id'] = $activity_id;
            $activity['ticket_id'] = $val['ticket_id'];
            $activity_detail[$val['ticket_id']] = $activity;
            $good['act_id'] = $act_id;
            $good['ticket_id'] = $val['ticket_id'];
            $good['tool_id'] = $val['tools_id'];
            $good['act_stock_num'] = $val['act_stock_num'] ?: 0;
            $good['act_price'] = $val['act_price'];
            $good['discount_rate'] = $val['discount_rate'];
            $good['reduce_money'] = $val['reduce_money'];
            $good['day_stock_num'] = $val['day_stock_num'] ?? '0';
            $good['day_original_stock_num'] = $val['day_original_stock_num'] ?? '0';
            $good['sale_day'] = date('Ymd');

            if (!empty($old_data)) {
                $key = $val['ticket_id'];
                $good['is_first'] = isset($old_data[$key]['is_first']) ? $old_data[$key]['is_first'] : '';
                $good['sort'] = isset($old_data[$key]['sort']) ? $old_data[$key]['sort'] : '';
            }
            $goods[] = $good;
        }

        if (!empty($goods)) {
            (new LifeToolsSportsSecondsKillTicketSku())->insertAll($goods);
        }

        if (!empty($activity_detail)) {
            (new LifeToolsSportsSecondsKillTicketDetail())->insertAll($activity_detail);
        }
    }

    /**
     * 获取编辑页面信息
     * @param $id
     * @return array
     */
    public function getSecondsKillDetail($id)
    {

        if (empty($id)) {
            throw new \think\Exception('活动id为空');
        }

        $where = ['s.id' => $id, 'm.type' => 'limited'];
        $fields = 's.*,m.name,m.start_time,m.end_time,m.mer_id,m.status';
        $res = $this->LifeToolsSportsSecondsKillDetailModel->getInfo($where, $fields);
        if ($res['start_time'] < time() && $res['end_time'] < time()) {
            $res['status'] = 2;
        }

        if ($res['time_type'] == 1) {
            $res['start_time'] = date("Y-m-d H:i:s", $res['start_time']);
            $res['end_time'] = date("Y-m-d H:i:s", $res['end_time']);
            $res['cycle_start_time'] = '00:00';
            $res['cycle_end_time'] = '00:00';
        } else {
            $res['start_time'] = date("Y-m-d", $res['start_time']);
            $res['end_time'] = date("Y-m-d", $res['end_time']);
            $res['cycle_start_time'] = second_to_time($res['cycle_start_time']);
            $res['cycle_end_time'] = second_to_time($res['cycle_end_time']);
        }
        $res['notice_time'] = $res['notice_type'] == 1 ? 0 : $res['notice_time'];
        $limited_act_sku = (new LifeToolsSportsSecondsKillTicketSku())->where(['act_id' => $id])->order(['id'=>'asc'])->select();
        if (!empty($limited_act_sku)) {
            $limited_act_sku = $limited_act_sku->toArray();
        }
        $goods_id = [];
        $sku_id = [];
        $temp = [];
        foreach ($limited_act_sku as $value) {
            $goods_id[] = $value['tool_id'];
            $sku_id[$value['tool_id']][] = $value['ticket_id'];
            $temp[$value['tool_id'] . '-' . $value['ticket_id']] = $value;
        }

        $param = [];
        $param['tool_id'] = $goods_id;
        $param['ticket_id'] = $sku_id;
        $param['act_id'] = $id;
        $param['type'] = 'limited';
        $param['mer_id'] = $res['mer_id'];
        $param['page'] = 1;
        $param['pageSize'] = 100;

        $goods_info = (new LifeToolsTicketService())->getSportsSecondsKillGoodsSelect($param);
        if(!empty($goods_info['list'])){
            foreach ($goods_info['list'] as $key => $val) {
                $goods_info['list'][$key]['image']=empty($val['cover_image'])?"":replace_file_domain($val['cover_image']);
                if($val['stock_type'] == 1){
                    $goods_info['list'][$key]['stock_num'] =  $val['stock_num'] - $val['sale_count'];
                }

                if($val['is_sku'] == 1){
                    $goods_info['list'][$key]['max_price'] =get_number_format((new LifeToolsTicketSku())->getMaxPice(['ticket_id'=>$val['ticket_id'],'is_del'=>0],'sale_price'));
                    $goods_info['list'][$key]['min_price'] =get_number_format((new LifeToolsTicketSku())->getMinPice(['ticket_id'=>$val['ticket_id'],'is_del'=>0],'sale_price'));
                    $goods_info['list'][$key]['stock_num'] =(new LifeToolsTicketSku())->getSum(['ticket_id'=>$val['ticket_id'],'is_del'=>0],'stock_num');
                }
            }
        }
        $res['goods_info'] = $goods_info['list'];
        return $res;
    }

    /**
     * 商家后台--状态修改
     * @param $data
     * @param $id
     * @return 
     */
    public function ChangeSportsSecondsKill($data, $id)
    {
        $activity_info = (new LifeToolsSportsSecondsKill())->getActInfo($where = ['act_id' => $id, 'type' => 'limited'], $field = 'id');
        $activity_id = $activity_info[0]['id'];
        //如果该活动被装修，失效时删除对应的装修关联商品
        (new LifeToolsSportsSecondsKillTicketDetail())->delActiveDatailAll($where = ['activity_id' => $activity_id]);
        $where = ['act_id' => $id, 'type' => 'limited'];
        $res = (new LifeToolsSportsSecondsKill())->updateThis($where,$data);
        return $res;

    }


    /**更新活动数据
     * @param $data
     * @param $id
     */
    public function updateThis($where, $data)
    {
        if(empty($where) || empty($data)){
            return false;
        }
        $res = (new LifeToolsSportsSecondsKill())->updateThis($where,$data);
        return $res;
    }

    /**
     * @return mixed
     * 查找分类列表
     */
    public function getCategoryList()
    {
        return (new MallLimitedSku())->getCategoryList();
    }
}