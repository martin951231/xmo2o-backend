<?php


namespace app\mall\model\service\activity;

use app\mall\model\db\MallGoods;
use app\mall\model\service\MallGoodsService;

use app\mall\model\db\MallFullGiveAct;
use app\mall\model\db\MallOrder;
use think\facade\Db;

class MallFullGiveActService
{
    protected $MallFullGiveAct = null;

    public function __construct()
    {
        $this->MallFullGiveAct = (new MallFullGiveAct());
        $this->MallGoods = (new MallGoods());
    }

    /**
     * @param $level
     * @param $goodsList
     * @return array
     */
    public function getEveryLevelGoods($level,$goodsList){
      $arrs=array();
      if(!empty($level) && !empty($goodsList)){
          foreach ($level as $k=>$v){
              foreach ($goodsList as $k1=>$v1){
                  if($v['id']==$v1['id']){
                      if($v1['full_type']==1){
                          $arrs[$k]['title']="满".get_format_number($v1['level_money']).'得赠品';
                      }else{
                          $arrs[$k]['title']="满".get_format_number($v1['level_money']).'件得赠品';
                      }
                      $goodslist['left_nums']=$v1['act_stock_num'];
                      $arr=$this->MallGoods->getGoodsNameAndSku($v1['goods_id'],$v1['sku_id']);
                      $goodslist['goods_name']=$arr['name'];
                      $goodslist['spec_str']=$arr['sku_str'];
                      $goodslist['gift_num']=$v1['gift_num'];//每次赠送的数量
                      $goodslist['goods_img']=$arr['image'] ? replace_file_domain($arr['image']) : '';
                      $arrs[$k]['list'][]=$goodslist;
                  }
              }
          }
      }
      return $arrs;
    }
    /**
     * @param $actid
     * @return mixed
     * 根据活动id获取赠品列表
     */
    public function getGiveList($actid)
    {
        return $this->MallFullGiveAct->getGiveList($actid);
    }

    /**
     * @param $goods_id
     * @param $store_id
     * @return bool|mixed
     * @author mrdeng
     * 加入购物，赠送物品列表
     */
    public function getGiveListShopCart($goods_id, $store_id)
    {
        //获取活动id
        $arr = (new MallFullGiveSkuService())->getActId($goods_id, $store_id);
        if (empty($arr)) {
            //活动实效
            return false;
        } else {
            $givie_list = $this->getGiveList($arr['act_id']);

            return $givie_list;
        }
    }


    /**满赠活动列表
     * @param $store_id
     * @param $param
     */
    public function getGiveActList($store_id, $param)
    {
        if (empty($store_id)) {
            throw new \think\Exception('店铺id为空');
        }

        $page = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = !empty($param['pageSize']) ? $param['pageSize'] : 10;//每页限制
        $name = !empty($param['name']) ? trim($param['name'], '') : '';
        $status = $param['status'];//活动状态 0未开始 1进行中 2已失效
        $startTime = !empty($param['start_time']) ? strtotime($param['start_time'] . " 00:00:00") : '';//开始时间
        $endTime = !empty($param['end_time']) ? strtotime($param['end_time'] . " 23:59:59") : '';//结束时间


        if (!empty($startTime) && !empty($endTime) && $startTime > $endTime) {
            throw new \think\Exception(L_('结束时间应大于开始时间'), 1003);
        }

        // 搜索条件
        $where = [];

        $where[] = ['a.store_id', '=', $store_id];
        $where[] = ['a.type', '=', 'give'];
        $where[] = ['a.is_del', '=', 0];
        if ($name) {
            $where[] = ['a.name', 'like', '%' . $name . '%'];
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
            $whereOr[] = ['a.is_del', '=', 0];
            $whereOr[] = ['a.type', '=', 'give'];
            $whereOr[] = ['a.store_id', '=', $store_id];
        }else{
            if (in_array($status, [0, 1, 2])) {//0未开始 1进行中 2已失效 3全部活动
                $where[] = ['a.status', '=', $status];
            }
        }
        //活动状态   //0未开始 1进行中 2已失效 3全部活动
        /*$stime = strtotime(date("Y-m-d 00:00:00",time()));
        $etime = strtotime(date("Y-m-d 23:59:59",time()));
        if ($status == 0) {
            $where[] = ['a.start_time', '>', $stime];
            $where[] = ['a.status', '<>', 2];
        }elseif($status == 1) {
            $where[] = ['a.start_time', '<=', $stime];
            $where[] = ['a.end_time', '>=', $etime];
            $where[] = ['a.status', '<>', 2];
        }elseif($status == 2) {
            $where[] = ['a.end_time', '<', $etime];
        }

        if (!empty($startTime) && !empty($endTime)) {
            $where[] = ['a.start_time', '>=', $startTime];
            $where[] = ['a.end_time', '<=', $endTime];
        } elseif ($startTime) {
            $where[] = ['a.start_time', '>=', $startTime];
        } elseif ($endTime) {
            $where[] = ['a.end_time', '<=', $endTime];
        }*/

        $prefix = config('database.connections.mysql.prefix');
        /*$count = $this->MallFullGiveAct->alias('g')
            ->join([$prefix . 'mall_activity' => 'a'], 'g.id = a.act_id', 'LEFT')
            ->where($where)
            ->count();
        if ($count < 1) {
            $lists = [];
        } else {*/
            $fields = 'g.id,g.full_type,a.`name`,a.store_id,a.start_time,a.end_time,a.`status`,a.desc,a.act_id';
            $lists = $this->MallFullGiveAct->alias('g')
                ->join([$prefix . 'mall_activity' => 'a'], 'g.id = a.act_id', 'LEFT')
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
            $lists=$lists->order('a.sort ASC,a.id DESC')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select()
                ->toArray();
            if(!empty($lists)){
                foreach ($lists as &$value) {
                    if ($value['start_time'] < time() && $value['end_time'] < time()) {
                        $value['status'] = 2;
                    }
                    if ($value['start_time'] < time() && $value['end_time'] > time() && $value['status'] != 2) {
                        $value['status'] = 1;
                    }
                    $value['start_time'] = date('Y-m-d', $value['start_time']);
                    $value['end_time'] = date('Y-m-d', $value['end_time']);
                    $value['level_detail'] = $this->getGiveGiftSkuList($value['id'], $value['full_type']);

                    $condition_where = [];
                    $condition_where[] = ['o.store_id', '=', $store_id];
                    $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET('give',o.activity_type)")];
                    $condition_where[] = ['', 'exp', Db::raw("FIND_IN_SET({$value['act_id']},o.activity_id)")];
                    $condition_where[] = ['o.status', '>=', 10];
                    $condition_where[] = ['o.status', '<=', 49];
                    $total = (new MallOrder())->getList($fields = 'o.uid,o.money_real', $condition_where);
                    $real_income = array_sum(array_column($total, 'money_real'));
                    $value['pay_order_num'] = count($total);//支付单数
                    $value['real_income'] = number_format($real_income, 2);//实收金额
                    $total = array_column($total, NULL, 'uid');
                    $value['pay_order_people'] = count($total);//支付人数
                }
            }
        /*}*/
        $res['list'] = $lists;
        $res['pageSize'] = $pageSize;
        $res['count'] = $count;
        $res['page'] = $page;
        return $res;
    }

    /**层级赠送商品信息
     * @param $id
     * @param $full_type 1:满N元 2:满N件 需转成整型
     */
    public function getGiveGiftSkuList($id, $full_type = 1)
    {
        // 搜索条件
        $where = [];
        $where[] = ['g.id', '=', $id];
        $fields = 'a.*';
        $prefix = config('database.connections.mysql.prefix');
        $lists = $this->MallFullGiveAct->alias('g')
            ->join([$prefix . 'mall_full_give_level' => 'a'], 'g.id = a.act_id', 'LEFT')
            ->field($fields)
            ->where($where)
            ->order('a.level_sort ASC')
            ->select()
            ->toArray();
        foreach ($lists as &$value) {
            if ($full_type == 2) $value['level_money'] = intval($value['level_money']);
            unset($value['act_id']);
            $fields = 'g.goods_id,g.sku_id,g.act_stock_num,g.gift_num,g.sale_num,mg.name,sku.sku_str,sku.price,sku.stock_num';
            $where = ['g.act_id' => $id, 'g.level_num' => $value['id'], 'a.type' => 'give'];
            $goods = (new MallFullGiveGiftSkuService())->getFullGiveGiftSku($fields, $where);
            $value['goods'] = $goods;
        }
        return $lists;
    }

    /**
     * 商家后台添加 满赠活动
     * User: 钱大双
     * Date: 2020-10-16 15:25:09
     * @param $data
     * @return bool
     * @throws \think\Exception
     */
    public function addGiveAct($data)
    {
        if (count($data['gift_detail']) > 5) {
            throw new \think\Exception(L_('满赠层级最多5层'), 1003);
        }

        if ($data['start_time'] > $data['end_time']) {
            throw new \think\Exception(L_('活动开始时间不能大于结束时间'), 1003);
        }

        //商城满赠活动信息
        $give = [];
        $give['full_type'] = $data['full_type'];
        $give['nums'] = $data['nums'];
        $give['join_max_num'] = $data['join_max_num'];
        $give['is_discount_share'] = $data['is_discount_share'];
        $give['discount_card'] = $data['discount_card'];
        $give['discount_coupon'] = $data['discount_coupon'];

        //商品活动信息
        $activity = [];
        $start_time = strtotime($data['start_time'] . " 00:00:00");
        $end_time = strtotime($data['end_time'] . " 23:59:59");
        $activity['name'] = $data['name'];
        $activity['act_type'] = $data['act_type'];
        $activity['start_time'] = $start_time;
        $activity['end_time'] = $end_time;
        $activity['desc'] = $data['desc'];
        $activity['sort'] = $data['sort'];
        if ($start_time > time()) {
            $activity['status'] = 0;
        } elseif ($start_time < time() && $end_time > time()) {
            $activity['status'] = 1;
        } elseif ($start_time < time() && $end_time < time()) {
            $activity['status'] = 2;
        }

        if ($data['id'] > 0) {//编辑
            Db::startTrans();
            try {
                $act_id = $data['id'];
                //获取活动信息
                $activity_info = (new MallActivityService())->getActInfo($where = ['act_id' => $act_id, 'type' => $data['type']], $field = 'id,start_time,end_time,act_type');
                $activity_id = $activity_info[0]['id'];
                $old_start_time = date('Y-m-d', $activity_info[0]['start_time']);
                $old_end_time = date('Y-m-d', $activity_info[0]['end_time']);

                $flag = 1;
                if ($data['act_type'] !== $activity_info[0]['act_type'] || $old_start_time !== $data['start_time'] || $old_end_time !== $data['end_time']) {
                    $flag = 2;
                } else {
                    $goods = (new MallActivityDetailService())->getGoodsInAct($where = ['activity_id' => $activity_id], $fields = 'goods_id');
                    $old_goods_id = array_column($goods, 'goods_id');//编辑前的活动商品ID
                    $new_goods_id = array_column($data['goods_sku'], 'goods_id');//编辑后的活动商品ID
                    $diff = array_diff($old_goods_id, $new_goods_id);
                    if ($diff) {
                        $flag = 2;
                    } else {
                        $gift_goods_sku = (new MallFullGiveGiftSkuService())->getFullGiveGiftSku($field = 'g.sku_id', $where = ['g.act_id' => $act_id, 'a.type' => 'give']);
                        $old_sku_id = array_column($gift_goods_sku, 'sku_id');//编辑前的赠送商品sku_id

                        $new_sku_id = [];//编辑后的赠送商品sku_id
                        if (!empty($data['gift_detail'])) {
                            foreach ($data['gift_detail'] as $gift) {
                                if (!empty($gift['goods'])) {
                                    foreach ($gift['goods'] as $g) {
                                        $new_sku_id[] = $g['sku_id'];
                                    }
                                }
                            }
                        }
                        $diff = array_diff($old_sku_id, $new_sku_id);
                        if ($diff) {
                            $flag = 2;
                        }
                    }
                }

                if ($flag == 1) {
                    $this->MallFullGiveAct->updateGive($give, $where = ['id' => $act_id]);
                    (new MallFullGiveLevelService())->delGiveLevel($where = ['act_id' => $act_id]);
                    (new MallFullGiveGiftSkuService())->delGiveGiftSku($where = ['act_id' => $act_id]);
                    (new MallActivityService())->updateActive($activity, $where = ['id' => $activity_id]);
                    (new MallActivityDetailService())->delActiveDatailAll($where = ['activity_id' => $activity_id]);
                    $gift_detail = $data['gift_detail'];
                    $goods_sku = $data['goods_sku'];
                    $act_type = $data['act_type'];
                    $this->update_give_active($gift_detail, $goods_sku, $act_id, $act_type, $activity_id,$data['id'],$data['id']);
                    Db::commit();
                    $res = ['status' => 0, 'msg' => '编辑成功'];
                } else {
                    $check = $this->_check_activity_goods($data);
                    if ($check['status'] == 0) {
                        $this->MallFullGiveAct->updateGive($give, $where = ['id' => $act_id]);
                        (new MallFullGiveLevelService())->delGiveLevel($where = ['act_id' => $act_id]);
                        (new MallFullGiveGiftSkuService())->delGiveGiftSku($where = ['act_id' => $act_id]);
                        (new MallActivityService())->updateActive($activity, $where = ['id' => $activity_id]);
                        (new MallActivityDetailService())->delActiveDatailAll($where = ['activity_id' => $activity_id]);
                        $gift_detail = $data['gift_detail'];
                        $goods_sku = $data['goods_sku'];
                        $act_type = $data['act_type'];
                        $this->update_give_active($gift_detail, $goods_sku, $act_id, $act_type, $activity_id,$data['id']);
                        Db::commit();
                        $res = ['status' => 0, 'msg' => '编辑成功'];
                    } else {
                        $res = ['status' => 1, 'msg' => $check['msg']];
                    }
                }
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
                $res = ['status' => 1, 'msg' => $e->getMessage()];
            }

        } else {//添加
            Db::startTrans();
            try {
                $check = $this->_check_activity_goods($data);
                if ($check['status'] == 0) {
                    $act_id = $this->MallFullGiveAct->addGive($give);
                    $activity['act_id'] = $act_id;
                    $activity['mer_id'] = $data['mer_id'];
                    $activity['store_id'] = $data['store_id'];
                    $activity['type'] = $data['type'];
                    $activity['create_time'] = time();
                    $activity_id = (new MallActivityService())->addActive($activity);
                    if ($act_id && $activity_id) {
                        $gift_detail = $data['gift_detail'];
                        $goods_sku = $data['goods_sku'];
                        $act_type = $data['act_type'];
                        $this->update_give_active($gift_detail, $goods_sku, $act_id, $act_type, $activity_id);
                        //提交事务
                        Db::commit();
                        $res = ['status' => 0, 'msg' => '添加成功'];
                    } else {
                        //回滚事务
                        Db::rollback();
                        $res = ['status' => 1, 'msg' => '添加失败'];
                    }
                } else {
                    $res = ['status' => 1, 'msg' => $check['msg']];
                }
            } catch (\Exception $e) {
                //回滚事务
                Db::rollback();
                $res = ['status' => 1, 'msg' => $e->getMessage()];
            }
        }

        return $res;
    }

    /**修改活动状态
     * @param $data
     * @param $id
     */
    public function changeState($data, $id)
    {
        $where = ['act_id' => $id, 'type' => 'give'];
        $res = (new MallActivityService())->updateActive($data, $where);
        return $res;
    }

    /**软删除活动
     * @param $data
     * @param $id
     */
    public function del($data, $id)
    {
        $where = ['act_id' => $id, 'type' => 'give'];
        $res = (new MallActivityService())->updateActive($data, $where);
        return $res;
    }

    /**活动基本信息
     * @param $data
     * @param $id
     */
    public function getInfoById($id)
    {
        if (empty($id)) {
            throw new \think\Exception('活动id为空');
        }

        $where = ['s.id' => $id, 'm.type' => 'give'];
        $fields = 's.*,m.id as activity_id,m.name,m.act_type,m.desc,m.start_time,m.end_time,m.mer_id,m.store_id,m.status';
        $res = (new MallFullGiveAct())->getInfo($where, $fields);
        if ($res['start_time'] < time() && $res['end_time'] < time()) {
            $res['status'] = 2;
        }
        $res['start_time'] = date("Y-m-d", $res['start_time']);
        $res['end_time'] = date("Y-m-d", $res['end_time']);
        $activity_id = $res['activity_id'];
        if ($res['act_type'] == 0) {//部分商品参与活动
            $goods = (new MallActivityDetailService())->getGoodsInAct($where = ['activity_id' => $activity_id], $fields = 'goods_id');
            $goods_id = array_column($goods, 'goods_id');
            $param = [];
            $param['goods_id'] = $goods_id;
            $param['type'] = 'give';
            $param['mer_id'] = $res['mer_id'];
            $param['store_id'] = $res['store_id'];
            $param['page'] = 1;
            $param['pageSize'] = 100;
            $goods_info = (new MallGoodsService())->getMallGoodsSelect($param);
            $res['goods_sku'] = $goods_info['list'];
        } else {
            $res['goods_sku'] = [];
        }
        $gift_detail = $this->getGiveGiftSkuList($id, $res['full_type']);
        $res['gift_detail'] = $gift_detail;
        unset($res['activity_id']);
        unset($res['mer_id']);
        unset($res['store_id']);
        return $res;
    }

    /**更新满赠活动相关信息
     * @param $gift_detail 每个等级下赠送商品的sku集合
     * @param $goods_sku  商品的sku信息
     * @param int $act_id
     * @param int $act_type
     * @param int $activity_id
     */
    private function update_give_active($gift_detail, $goods_sku, $act_id = 0, $act_type = 0, $activity_id = 0,$id=0)
    {
        $level_num_data = $goods = [];
        foreach ($gift_detail as $key => $value) {
            $add = [];
            $add['act_id'] = $act_id;
            $add['level_sort'] = $value['level_sort'];
            $add['level_money'] = $value['level_money'];
            $level_num = (new MallFullGiveLevelService())->addGiveLevel($add);
            if ($level_num) {
                $level_num_data[] = $level_num;
                $goods[] = $value['goods'];
            }
        }
        //参加满赠活动层级的赠品
        foreach ($level_num_data as $k => $val) {
            if (isset($goods[$k])) {
                foreach ($goods[$k] as $kk => $vv) {
                    $good = [];
                    $good['act_id'] = $act_id;
                    $good['level_num'] = $val;
                    $good['goods_id'] = $vv['goods_id'];
                    $good['sku_id'] = $vv['sku_id'];
                    if(!isset($vv['act_stock_num'])){
                        throw new \think\Exception(L_("赠品库存必填"), 1003);
                    }
                    if($id==0 && !isset($vv['stock_num1'])) {
                        throw new \think\Exception(L_("赠品库存必填"), 1003);
                    }
                    if($id==0){
                        $good['act_stock_num'] = $vv['stock_num1'] ?? '';
                    }else{
                        $good['act_stock_num'] = $vv['act_stock_num'] ?? '';
                    }
                    $good['gift_num'] = $vv['gift_num'];
                    (new MallFullGiveGiftSkuService())->addGiveGiftSku($good);
                }
            }
        }

        if ($act_type == 0) {
            //参加满赠活动的商品
            $activity_detail = [];
            foreach ($goods_sku as $skus) {
                // $sku = [];
                // $sku['act_id'] = $act_id;
                // $sku['goods_id'] = $skus['goods_id'];
                // $sku['act_stock_num'] = $skus['act_stock_num'];
                // $sku['act_lowest_price'] = $skus['act_lowest_price'];
                // $sku['act_highest_price'] = $skus['act_highest_price'];
                // (new MallFullGiveSkuService())->addGiveSku($sku);
                $activity_detail[] = ['activity_id' => $activity_id, 'goods_id' => $skus['goods_id']];
            }
            (new MallActivityDetailService())->addActiveDatailAll($activity_detail);
        }
    }

    //校验活动商品是否合法
    private function _check_activity_goods($data)
    {
        //满赠有效活动商品信息
        $curr_time = time();
        $spu_where = [
            ['a.end_time', '>', $curr_time],
            ['a.status', '<>', 2],
            ['a.is_del', '=', 0],
            ['a.act_id', '<>', $data['id']],
            ['a.mer_id','=',$data['mer_id']],
            ['a.store_id','=',$data['store_id']],
            ['a.type', '=', 'give'],
        ];
        $spu_field = 'd.goods_id,a.id,a.act_id,a.type,a.name as act_name,a.start_time,a.end_time,a.act_type';
        $res = ['status' => 0];
        $spu = (new MallActivityService())->getGoodsInAct($spu_where, $spu_field);
        $start_time = strtotime($data['start_time'] . " 00:00:00");
        $end_time = strtotime($data['end_time'] . " 23:59:59");
        if (!empty($spu)) {
            $spu = sortArr($spu, 'act_type', SORT_DESC);
            $activity_goods_id = array_column($data['goods_sku'], 'goods_id');
            foreach ($spu as $value) {
                $flag = is_time_cross($start_time, $end_time, $value['start_time'], $value['end_time']);
                if ($flag) {
                    $stime = date('Y-m-d', $value['start_time']);
                    $etime = date('Y-m-d', $value['end_time']);
                    if ($data['act_type'] == 1) {
                        $msg = $stime . '~' . $etime . '已有部分商品参与' . $value['act_name'] . '满赠活动';
                        $res = ['status' => 1, 'msg' => $msg];
                        break;
                    } else {
                        if ($value['act_type'] == 1) {
                            $msg = $stime . '~' . $etime . '已有部分商品参与' . $value['act_name'] . '满赠活动';
                            $res = ['status' => 1, 'msg' => $msg];
                            break;
                        } else {
                            if (in_array($value['goods_id'], $activity_goods_id)) {
                                $goods_detail = (new MallGoodsService())->getOne($value['goods_id']);
                                $msg = $goods_detail['name'] . '已参与' . $stime . '~' . $etime . '的' . $value['act_name'] . '满赠活动';
                                $res = ['status' => 1, 'msg' => $msg];
                                break;
                            }
                        }
                    }
                }
            }
        }

        //校验赠送商品是否合法
        if ($res['status'] == 0) {
            $sku_field = 'g.sku_id,a.name as act_name,a.start_time,a.end_time,mg.name,sku.sku_str';
            $gift_goods = (new MallFullGiveGiftSkuService())->getFullGiveGiftSku($sku_field, $spu_where);

            $activity_sku_id = [];
            if (!empty($data['gift_detail'])) {
                foreach ($data['gift_detail'] as $gift) {
                    if (!empty($gift['goods'])) {
                        foreach ($gift['goods'] as $g) {
                            $activity_sku_id[] = $g['sku_id'];
                        }
                    }
                }
            }
            foreach ($gift_goods as $val) {
                $flag = is_time_cross($start_time, $end_time, $val['start_time'], $val['end_time']);
                if ($flag) {
                    $stime = date('Y-m-d', $val['start_time']);
                    $etime = date('Y-m-d', $val['end_time']);
                    if (in_array($val['sku_id'], $activity_sku_id)) {
                        $msg = $val['sku_str'] . '规格的' . $val['name'] . '商品' . '已作为赠品参与' . $stime . '~' . $etime . '的' . $val['act_name'] . '满赠活动';
                        $res = ['status' => 1, 'msg' => $msg];
                        break;
                    }
                }
            }
        }
        return $res;
    }
}