<?php
/**
 * 门票service
 * @date 2021-12-16
 */

namespace app\life_tools\model\service;

use app\common\model\service\AuditService;
use app\life_tools\model\db\LifeScenicActivityDetail;
use app\life_tools\model\db\LifeScenicLimitedSku;
use app\life_tools\model\db\LifeTools;
use app\life_tools\model\db\LifeToolsCategory;
use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsOrderDetail;
use app\life_tools\model\db\LifeToolsSportsActivity;
use app\life_tools\model\db\LifeToolsSportsActivityBindTicket;
use app\life_tools\model\db\LifeToolsSportsSecondsKillDetail;
use app\life_tools\model\db\LifeToolsSportsSecondsKillTicketDetail;
use app\life_tools\model\db\LifeToolsSportsSecondsKillTicketSku;
use app\life_tools\model\db\LifeToolsTicket;
use app\life_tools\model\db\LifeToolsTicketCustomForm;
use app\life_tools\model\db\LifeToolsTicketSku;
use app\life_tools\model\db\LifeToolsTicketSpec;
use app\life_tools\model\db\LifeToolsTicketSpecVal;
use app\life_tools\model\service\group\LifeToolsGroupTicketService;
use app\mall\model\service\activity\MallActivityDetailService;
use think\Model;
use app\mall\model\service\MallGoodsService;
use think\facade\Db;

class LifeToolsTicketService
{
    public $lifeToolsTicketModel = null;
    public $lifeToolsModel = null;
    public function __construct()
    {
        $this->lifeToolsTicketModel = new LifeToolsTicket();
        $this->lifeToolsTicketCustomFormModel = new LifeToolsTicketCustomForm();
        $this->lifeToolsOrderModel = new LifeToolsOrder();
        $this->lifeToolsModel = new LifeTools();
    }

    /**
     * @param:
     * @return :  array
     * @Desc:   获取分类列表
     */
    public function getSortList($mer_id, $param)
    {
        if (!empty($mer_id)) {
            $mallSort = new LifeTools();
            //排序
            $order = [
                'r.sort' => 'DESC',
            ];
            $where=[['l.mer_id','=',$mer_id],['l.is_del','=',0],['l.status','=',1],['r.is_del','=',0]];
            if($param['source']=='sport'){
                array_push($where,['l.type','=','stadium']);
            }elseif($param['source']=='stadium') {
                array_push($where,['l.type','=','stadium']);
            }elseif($param['source']=='course') {
                array_push($where,['l.type','=','course']);
            }else {
                array_push($where,['l.type','=','scenic']);
            }
            $all =(new LifeToolsCategory())->getListToolCategory($where,'r.cat_id,r.cat_id as id,r.cat_name as name',$order);
            // 总数
            //获取三级分类数据
            $farr=$all['list'];
            if (!empty($farr)) {
                $count_1 = 0;
                foreach ($farr as $key => $val1) {
                    $where_count=[['cat_id','=',$val1['cat_id']],['is_del','=',0],['status','=',1]];
                    $where_sort=[['r.cat_id','=',$val1['cat_id']], ['r.mer_id','=',$mer_id], ['r.is_del','=',0], ['r.status','=',1], ['m.status','=',1]];
                    if($param['source']=='sport'){
                        array_push($where_count,['type','=','stadium']);
                        array_push($where_sort,['r.type','=','stadium']);
                    }
                    $count_1=$mallSort->getCount($where_count);

                    $farr[$key]['children'] =$mallSort->getListTool($where_sort,
                        'r.tools_id as id,r.title as name',0,0, 'r.sort desc');
                    if ($count_1 == 0) {
                        $farr[$key]['has_goods'] = 0;
                    } else {
                        $farr[$key]['has_goods'] = 1;
                    }

                    if(!empty($farr[$key]['children'])){
                        foreach ($farr[$key]['children'] as $k=>$v){
                            $tickect=(new LifeToolsTicket())->getSome(['tools_id'=>$v['id'],'status'=>1,'is_del'=>0]);
                            if(empty($tickect)){
                                unset($farr[$key]);
                            }
                        }
                    }
                    $count_1 = 0;
                }
                $list1['list'] = array_values($farr);
                return $list1;
            } else {
                return ['list'=>[]];
            }
        } else {
            return ['list'=>[]];
        }
    }


    /**
     * @param $param
     * @return mixed|void
     * @throws \think\Exception
     * 获取商品列表（新建活动时选取商品）
     */
    public function getTickectSelect($param)
    {
        if (empty($param['mer_id'])) {
            throw new \think\Exception('缺少mer_id参数');
        }
        //所有商品（原始）
        $field = 'r.*,g.title as name,g.cover_image';
        $where = [['g.mer_id', '=', $param['mer_id']],
            ['g.status', '=', 1], ['g.is_del', '=', 0], ['r.status', '=', 1], ['r.is_del', '=', 0],['r.is_sku', '=', 0]];
        if (!empty($param['tools_id'])) {
            array_push($where, ['g.tools_id', '=', $param['tools_id']]);
        }

        if (!empty($param['keyword'])) {
            array_push($where, ['g.title', 'like', '%' . $param['keyword'] . '%']);
        }

        if ($param['source'] == 'group') {
            array_push($where, ['g.type', '=', 'scenic']);
        }
        if($param['source']=='limitedSport'){
            //多规格门票不可以选中
            array_push($where, ['r.is_sku', '=', 0]);
        }
        $order = ['r.sort' => 'DESC', 'r.ticket_id' => 'DESC'];
        $allGoods = (new LifeToolsTicket())->getListByTool($where,$field,$param['page'],$param['pageSize'],$order);
        if(!empty($allGoods['list'])){
            foreach ($allGoods['list'] as $k=>$v){
                $allGoods['list'][$k]['group_price']="";
                $allGoods['list'][$k]['max_num']=1;
                $allGoods['list'][$k]['sku_info']=[];
                $allGoods['list'][$k]['image']=empty($v['cover_image'])?"":replace_file_domain($v['cover_image']);
                if($param['source']=='limited'){
                    $excit=(new LifeScenicActivityDetail())->getGoodsInAct([['d.ticket_id','=',$v['ticket_id']],['a.end_time','>',time()],['a.is_del','=',0]],'a.*');
                }elseif($param['source']=='limitedSport'){
                    $excit=(new LifeToolsSportsSecondsKillTicketDetail())->getGoodsInAct([['d.ticket_id','=',$v['ticket_id']],['a.end_time','>',time()],['a.is_del','=',0]],'a.*');
                }elseif( $param['source']== 'group'){// 团体票
                    $excit = (new LifeToolsGroupTicketService())->getOne(['is_del'=>0,'ticket_id'=>$v['ticket_id']]);
                }else{
                    $excit=(new LifeToolsSportsActivityBindTicket())->getOne(['ticket_id'=>$v['ticket_id']]);
                }
                if(empty($excit)){
                    $allGoods['list'][$k]['can_be_choose'] = 1;
                }else{
                    $allGoods['list'][$k]['can_be_choose'] =0;
                }

                if($param['targeTage']){
                    foreach ($param['targeTage'] as $ks=>$vs){
                        $sku['pin_num']=$vs.'人';
                        if(in_array(1,$param['group_type'])){
                            $sku['group_price']=get_number_format($v['price']);
                            $sku['aa_price']='----';
                            $sku['aa_group_price']='----';
                        }else{
                            $sku['group_price']='----';
                            $sku['aa_price']='----';
                            $sku['aa_group_price']='----';
                        }

                        if(in_array(2,$param['group_type'])) {
                            $sku['aa_price']=get_number_format((floor($v['price'] / $vs * 100) / 100));
                            $sku['aa_group_price']=get_number_format($v['price'] - (floor($v['price'] / $vs * 100) / 100) * ($vs - 1));
                        }
                        $allGoods['list'][$k]['sku_info'][]=$sku;
                    }
                }

                if($v['stock_type'] == 1){
                    $allGoods['list'][$k]['stock_num'] =  $v['stock_num'] - $v['sale_count'];
                }

                if(in_array($v['is_sku'],[1,2])){
                    $allGoods['list'][$k]['max_price'] =get_number_format((new LifeToolsTicketSku())->getMaxPice(['ticket_id'=>$v['ticket_id'],'is_del'=>0],'sale_price'));
                    $allGoods['list'][$k]['min_price'] =get_number_format((new LifeToolsTicketSku())->getMinPice(['ticket_id'=>$v['ticket_id'],'is_del'=>0],'sale_price'));
                    $allGoods['list'][$k]['stock_num'] =(new LifeToolsTicketSku())->getSum(['ticket_id'=>$v['ticket_id'],'is_del'=>0],'stock_num');
                }
                $allGoods['list'][$k]['act_stock_num'] = $allGoods['list'][$k]['stock_num'];
                //如果传入开始时间，查询每日库存票总库存
                $param['start_time'] = $param['start_time'] ?? '';
                if($param['start_time'] && $v['stock_type'] == 2){
                    //查询有效可售卖天数（包括开始当天）
                    $canSaleDate = (new LifeToolsTicketSaleDayService())->getSome(['ticket_id' => $v['ticket_id']], true, 'day asc');
                    $canSaleDateAry = [];
                    foreach ($canSaleDate as $kk=>$vv){
                        if (strtotime($vv['day']) + 86399 <= $param['start_time']) {
                            unset($canSaleDate[$kk]);
                        } else {
                            $today = date('Y-m-d',$param['start_time']);
                            if ($vv['day'] == $param['start_time'] && ($v['can_book_today'] == 0 || $param['start_time'] >= strtotime($today . ' ' . $tv['book_today_time']))) {
                                unset($canSaleDate[$kk]);
                            }else
                                if ($canSaleDate[$kk]['is_sale'] == 0) {
                                    unset($canSaleDate[$kk]);
                                }else{
                                    $canSaleDateAry[] = $vv['day'];
                                }
                        }
                    }
                    $canSaleDate = $canSaleDate ? array_values($canSaleDate) : [];
                    if(count($canSaleDate) == 0){//无可售天数，总库存为0
                        $allGoods['list'][$k]['act_stock_num'] = 0;
                    }
                    $sumWhere = [
                        ['ticket_id', '=', $v['ticket_id']],
                        ['ticket_time', 'IN', $canSaleDateAry],
                        ['is_give', '=', 0]
                    ];
                    $orderIds = $this->lifeToolsOrderModel->where($sumWhere)->column('order_id');
                    $condition = [];
                    $condition[] = ['order_id', 'in', $orderIds];
                    $condition[] = ['status', '<>', 3];
                    $saleNum = (new LifeToolsOrderDetail())->where($condition)->count();//已售出总库存
                    $allStock = $v['stock_num'] * count($canSaleDate);//设置的总库存
                    $allGoods['list'][$k]['act_stock_num'] = $allStock - $saleNum > 0 ? $allStock - $saleNum : 0;
                }
            }
        }
        $list['list'] = $allGoods['list'];
        $list['count'] =$allGoods['total'];
        return $list;
    }


    /**
     * @param $param
     * @return mixed|void
     * @throws \think\Exception
     * 获取活动商品列表（新建活动时选取商品）
     */
    public function getActSelect($param)
    {
        if (empty($param['act_id'])) {
            throw new \think\Exception('缺少活动参数');
        }
        //所有商品（原始）
        $field = 't.*,l.cover_image,s.act_stock_num,s.act_price,s.discount_rate,s.reduce_money,l.title as name,t.title as tickect_name';
        $where = [['s.act_id', '=', $param['act_id']]];
        $order = ['s.sort' => 'DESC', 's.id' => 'DESC'];
        $allGoods = (new LifeScenicLimitedSku())->getActTickectList($where,$field,$order);
        $list['list'] = $allGoods['list'];
        $list['count'] =$allGoods['total'];
        return $list;
    }
    /**
     * @param $param
     * @return mixed|void
     * @throws \think\Exception
     * 获取体育秒杀活动商品列表（新建活动时选取商品）
     */
    public function getSportsSecondsKillGoodsSelect($param)
    {
        if (empty($param['act_id'])) {
            throw new \think\Exception('缺少活动参数');
        }
        //所有商品（原始）
        $field = 't.*,l.cover_image,s.act_stock_num,s.day_stock_num,s.day_original_stock_num,s.sale_day,s.act_price,s.discount_rate,s.reduce_money,l.title as name,t.title as tickect_name';
        $where = [['s.act_id', '=', $param['act_id']]];
        $order = ['s.sort' => 'DESC', 's.id' => 'DESC'];
        $allGoods = (new LifeToolsSportsSecondsKillTicketSku())->getActTickectList($where,$field,$order);
        $list['list'] = $allGoods['list'];
        $list['count'] =$allGoods['total'];
        return $list;
    }
    /**
     * 获得门票列表
     * @param $where
     * @return array
     */
    public function getList($where = [])
    {
        $pageSize = isset($where['pageSize']) ? $where['pageSize'] : 0;//每页显示数量
        $page = request()->param('page', '1', 'intval');//页码

        // 构造查询条件
        $condition = [];

        // 排序
        $order = [
            'a.sort' => 'DESC',
            'a.ticket_id' => 'DESC',
        ];

        // 服务id
        if(isset($where['tools_id']) && $where['tools_id']){
            $condition[] = ['a.tools_id', '=', $where['tools_id']];
        }

        // mer_id
        if(isset($where['mer_id']) && $where['mer_id']){
            $condition[] = ['a.mer_id', '=', $where['mer_id']];
        }

        // 搜索门票状态
        if(isset($where['status']) && $where['status']){
            $condition[] = ['a.status', '=', $where['status']];
        }


//        $condition[] = ['a.is_del', '=', 0];

        // 门票列表
//        $list = $this->getSomeAndPage($condition, 'a.*,b.secondary_commission,b.third_commission', $order, $page, $pageSize);

        // 搜索审核状态
        if(isset($where['audit_status']) && !is_null($where['audit_status'])){
            $condition[] = ['audit_status', '=', $where['audit_status']];
        }

        $condition[] = ['a.is_del', '=', 0];

        // 门票列表
        $list = $this->getSomeAndPage($condition, 'a.*,b.secondary_commission,b.third_commission', $order, $page, $pageSize);
        $auditMap = ['待审核', '审核成功', '审核失败'];
        $tool=(new LifeTools())->getDetail($where['tools_id'],'type');
        foreach($list['data'] as &$_ticket){
            if($_ticket['stock_type'] == 1){
                $_ticket['stock_num'] =  $_ticket['stock_num'] - $_ticket['sale_count'];
            }
            $_ticket['audit_status_text'] = $auditMap[$_ticket['audit_status']] ?? '';
            if(in_array($_ticket['is_sku'],[1,2])){
                $_ticket['max_price'] =get_number_format((new LifeToolsTicketSku())->getMaxPice(['ticket_id'=>$_ticket['ticket_id'],'is_del'=>0],'sale_price'));
                $_ticket['min_price'] =get_number_format((new LifeToolsTicketSku())->getMinPice(['ticket_id'=>$_ticket['ticket_id'],'is_del'=>0],'sale_price'));
                if($tool['type']=='stadium'){
                    $_ticket['stock_num'] =(new LifeToolsTicketSku())->getSum(['ticket_id'=>$_ticket['ticket_id'],'is_del'=>0],'stock_num');
                }else{
                    $_ticket['stock_num'] =(new LifeToolsTicketSku())->getSum(['ticket_id'=>$_ticket['ticket_id'],'is_del'=>0],'stock_num')-$_ticket['sale_count']>0?(new LifeToolsTicketSku())->getSum(['ticket_id'=>$_ticket['ticket_id'],'is_del'=>0],'stock_num')-$_ticket['sale_count']:0;
                }
            }
            $_ticket['audit_status_text'] = (new MallGoodsService())->auditStatusMap($_ticket['audit_status']);
            $_ticket['add_audit_time'] = $_ticket['add_audit_time'] ? date('Y-m-d H:i:s',$_ticket['add_audit_time']) : '';
            $_ticket['create_time_text'] = $_ticket['create_time'] ? date('Y-m-d H:i:s',$_ticket['create_time']) : '';
        }

        return $list;
    }

    /**
     * 获得门票详情
     * @param $param
     * @return array
     */
    public function getDetail($param = [])
    {

        $ticketId = $param['ticket_id'] ?? 0;// 门票ID
        $merId = $param['mer_id'] ?? 0;// 商家ID
        if(empty($merId) || empty($ticketId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        // 查询数据
        $where = [
            'mer_id' => $merId,
            'ticket_id' => $ticketId,
            'is_del' => 0
        ];
        $detail = $this->getOne($where);
        if(empty($detail)){
            return [];
        }
        $detail['course_end_time'] = date('Y-m-d H:i:s', $detail['course_end_time']);
        $detail['staff_ids'] = $detail['staff_ids'] ? array_map('intval',explode(',', trim($detail['staff_ids'],',')))  : [];// 绑定店员

        if($detail['stock_type'] == 1){// 永久库存
            $detail['stock_num'] =  $detail['stock_num'] - $detail['sale_count'];
        }

        //获取sku信息
        $sku_list  = [];
        $spec_list = [];
        if (in_array($detail['is_sku'],[1,2])) {
            $tools=(new LifeTools())->getDetail(['tools_id'=>$detail['tools_id']],'type');
            $sku_info = (new LifeToolsTicketSku())->getSome(['ticket_id' => $ticketId, 'is_del' => 0],true,'sku_id asc')->toArray();
            if (!empty($sku_info)) {
                foreach ($sku_info as $key => $val) {
                    $sku     = explode('|', $val['sku_info']);
                    $sku_str = explode(',', $val['sku_str']);
                    if (!empty($sku)) {
                        $sku_info[$key]['price_calendar'] = [];
                        $sku_info[$key]['spec_val_id'] = '';
                        foreach ($sku as $k => $v) {
                            $ids = explode(':', $v);
                            if (!empty($ids) && !empty($sku_str)) {
                                if (isset($ids[1]) && isset($ids[0])) {
                                    if (empty($spec_list[$ids[0]])) {
                                        $spec_list[] = [
                                            'id'   => $ids[0],
                                            'name' => (new LifeToolsTicketSpec())->where(['spec_id' => $ids[0]])->value('name'),
                                            'list' => (new LifeToolsTicketSpecVal())->where(['spec_id' => $ids[0], 'is_del' => 0])->field('id,name')->select() ?? []
                                        ];
                                    }
                                    $name = $sku_str[$k];
                                    $sku_info[$key]['specid:' . $ids[0]] = $name;
                                    $sku_info[$key]['spec_val_id'] .= empty($sku_info[$key]['spec_val_id']) ? $ids[1] : '_' . $ids[1];
                                }
                            }
                        }
                        $sku_info[$key]['price_calendar'] = (new LifeToolsTicketSaleDayService())->getSome(['ticket_id' => $ticketId,'sku_id'=>$val['sku_id']], true, ['day'=>'ASC']);
                         if($tools['type']=='course'){
                               $sumWhere1 = [
                                 ['ticket_id', '=', $ticketId],
                                 ['ticket_time', '=', date("Y-m-d",time())],
                                 ['sku_id', '=', $val['sku_id']],
                                 ['order_status', 'not in', [50, 60]],
                                 ['is_give', '=', 0]
                             ];
                             $sumWhere0 = [
                                 ['ticket_id', '=', $ticketId],
                                 ['sku_id', '=', $val['sku_id']],
                                 ['order_status', 'not in', [50, 60]],
                                 ['is_give', '=', 0]
                             ];
                             $sku_info[$key]['stock_num']=$sku_info[$key]['stock_num'] -
                                 (($tools['type'] == 'course') ? (new LifeToolsOrder())->where($sumWhere0)->sum('num') : (new LifeToolsOrder())->where($sumWhere1)->sum('num'));
                         }
                        if($sku_info[$key]['stock_num']<0){//场馆
                            $sku_info[$key]['stock_num']=0;
                        }
                    }
                    unset($sku_info[$key]['sku_info'],
                        $sku_info[$key]['sku_str'],
                        $sku_info[$key]['store_id'],
                        $sku_info[$key]['sale_price'],
                        $sku_info[$key]['create_time'],
                        $sku_info[$key]['is_del'],
                        $sku_info[$key]['origin_stock']);
                    if (!empty($spec_list)) {
                        $sku_list[] = $sku_info[$key];
                    }
                }
            }
        }else{
            // 查询日历价格
            $detail['price_calendar'] = (new LifeToolsTicketSaleDayService())->getSome(['ticket_id' => $ticketId], true, ['day'=>'ASC']);
        }
        $detail['has_bind_sports'] = false;
        if($detail['is_sku'] === 0){//单规格
            $detail['has_bind_sports'] = $this->hasBindSports($detail['ticket_id'], $detail['mer_id']);
        }
        //确保未来三个月都有数据
        $startData = time();
        $endDate = mktime(0, 0, 0, date('m') + 3, date('d'), date('Y'));

        if(isset($detail['price_calendar'])){
            for($i = $startData; $i <= $endDate; $i += 86400 ){
                $d = date('Y-m-d', $i);
                $has = false;
                foreach ($detail['price_calendar'] as $key => $val) {
                    if($val['day'] == $d){
                        $has = true;
                    }
                }
                if(!$has){
                    $detail['price_calendar'][] = [
                        'day'   =>  $d,
                        "ticket_id" =>  $ticketId,
                        "sku_id" =>  0,
                        "price" =>  0,
                        "is_sale" =>  0
                    ];
                }
            }
        }
        
 
        $detail['has_bind_sports'] = false;
        if($detail['is_sku'] === 0){//单规格
            $detail['has_bind_sports'] = $this->hasBindSports($detail['ticket_id'], $detail['mer_id']);
        }
        $detail['spec_list'] = $spec_list;
        $detail['sku_list']  = $sku_list;

        //查询自定义表单
        $condition = [];
        $condition[] = ['ticket_id', '=', $ticketId];
        $condition[] = ['is_del', '=', 0];
        $detail['custom_form']  = $this->lifeToolsTicketCustomFormModel
                                    ->where($condition)
                                    ->withAttr('is_status', function($value, $data) {
                                        return $data['status'];
                                    })->append(['is_status'])
                                    ->select();
        return $detail;
    }

    /**
     * 获得编辑添加所需参数
     * @param $param
     * @return array
     */
    public function getEditInfo($param = [])
    {

        $toolsId = $param['tools_id'] ?? 0;// 服务ID
        $merId = $param['mer_id'] ?? 0;// 商家ID

        if(empty($toolsId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        $where = [
            'mer_id' => $merId,
            'tools_id' => $toolsId,
            'is_del' => 0
        ];
        $detail = (new LifeToolsService())->getOne($where);
        if(empty($detail)){
            throw new \think\Exception(L_('数据不存在'), 1001);
        }

        $returnArr['type'] = $detail['type'];
        $returnArr['title'] = $detail['title'];
        $returnArr['type_name'] = (new LifeToolsService())->getTypeName($detail['type']);
        return $returnArr;
    }

    /**
     * 添加编辑门票
     * @param $param
     * @return array
     */
    public function addOrEdit($param = [])
    {
        $ticketId                 = $param['ticket_id'] ?? 0;// 门票ID
        $data['mer_id']           = $param['mer_id'] ?? 0;// 商家ID
        $data['tools_id']         = $param['tools_id'] ?? 0;// 服务ID
        $data['sort']             = $param['sort'] ?? 0;// 排序值
        $data['title']            = $param['title'] ?? '';// 标题
        $data['description']      = $param['description'] ?? '';// 描述
        $data['old_price']        = $param['old_price'] ?? '0';// 老价格
        $data['price']            = $param['price'] ?? '0';// 现价
        $data['start_time']       = $param['start_time'] ?? '';
        $data['end_time']         = $param['end_time'] ?? '';
        $data['stock_num']        = $param['stock_num'] ?? '0';
        $data['status']           = $param['status'] ?? '0';
        $data['is_refund']        = $param['is_refund'] ?? '0';
        $data['can_book_today']   = $param['can_book_today'] ?? '0'; //是否可预订当天的门票
        $data['book_today_time']  = $param['book_today_time'] ?? '';//预定当天门票截止时间
        $data['open_custom_form'] = $param['open_custom_form'] ?? '';//是否开启自定义表单填写

        $data['scenic_ticket_type'] = $param['scenic_ticket_type'] ?? '';//景区门票类型
        $data['date_ticket_start']  = $param['date_ticket_start'] ?? '';//期票开始时间
        $data['date_ticket_end']    = $param['date_ticket_end'] ?? '';//期票结束时间

        $tools = (new LifeTools())->where('tools_id', $param['tools_id'])->find();

        if ($tools && $tools->type == 'scenic' && $data['scenic_ticket_type'] == 0 && (empty($data['date_ticket_start']) || empty($data['date_ticket_end']))) {
            throw new \think\Exception(L_('请选择期票开始结束时间'), 1001);
        }
        $priceCalendar           = $param['price_calendar'] ?? [];// 价格日历
        $label                   = $param['label'] ?? [];// 标签
        $data['course_end_time'] = isset($param['course_end_time']) && $param['course_end_time'] ? strtotime($param['course_end_time']) : '';

        $data['staff_ids'] = $param['staff_ids'] ? ',' . implode(',', $param['staff_ids']) . ',' : '';// 绑定店员

        $data['is_sku'] = $param['is_sku'];
        //库存计算
        if (!empty($param['list'])) {//多规格
            $param['stock_num'] = 0;
            foreach ($param['list'] as $i) {
                $param['stock_num'] += $i['stock_num'];
                if ($i['stock_num'] == -1) {
                    $param['stock_num'] = -1;
                    break;
                }
            }
            $price = array_column($param['list'], 'price');
            $param['max_price'] = max($price);
            $param['min_price'] = min($price);
            //该商品是sku时price字段存入min_price的值
            $param['price']      = $param['min_price'];
        } else {
            //单规格最高价和最低价都存入price
            $param['max_price']  = $param['price'];
            $param['min_price']  = $param['price'];
            $param['is_sku'] = 0;
        }
        $spec_list = $param['spec_list'];
        $list      = $param['list'];
        unset($param['list']);
        unset($param['spec_list']);
         if(!empty($spec_list)){
             foreach ($spec_list as $sk => $val) {
                 if (empty($val['list'])) {
                     throw new \think\Exception(L_('不能上传空规格'), 1001);
                 }
             }
         }
        if(is_array($label) && count($label)){
            foreach($label as $key => $val)
            {
                $label[$key] = str_replace(' ', '', $val);
            }
            $data['label'] = implode(' ', $label);
        }else{
            $data['label'] = '';
        }
        if(empty($data['mer_id']) || empty($data['tools_id'])){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        // 查询对应的服务
        $where = [
            'mer_id' => $data['mer_id'],
            'tools_id' => $data['tools_id'],
            'is_del' => 0
        ];
        $toolsDetail = (new LifeToolsService())->getOne($where);
        if(empty($toolsDetail)){
            throw new \think\Exception(L_('服务不存在'), 1001);
        }
        if($ticketId){
            $param['ticket_id']=$ticketId;
            $where = [
                'mer_id' => $data['mer_id'],
                'ticket_id' => $ticketId,
                'is_del' => 0
            ];
            $detail = $this->getOne($where);
            if(empty($detail)){
                throw new \think\Exception(L_('数据不存在'), 1001);
            }
        }
        $data['audit_msg'] = '';
        //开启景区、体育、商城、外卖审核
        if(customization('open_scenic_sports_mall_shop_audit') == 1){

            //下架操作不需要同意
            $outOfStock = false;
            if($ticketId 
                && $data['mer_id'] == $detail['mer_id']
                && $data['tools_id'] == $detail['tools_id']
                && $data['sort'] == $detail['sort']
                && $data['title'] == $detail['title']
                && $data['description'] == $detail['description']
                && $data['old_price'] == $detail['old_price']
                && $data['price'] == $detail['price']
                && $data['start_time'] == $detail['start_time']
                && $data['end_time'] == $detail['end_time']
                && $data['stock_num'] == $detail['stock_num']
                && $data['is_refund'] == $detail['is_refund']
                && $data['can_book_today'] == $detail['can_book_today']
                && $data['book_today_time'] == $detail['book_today_time']
                && $data['open_custom_form'] == $detail['open_custom_form']
                && $data['scenic_ticket_type'] == $detail['scenic_ticket_type']
                && $data['date_ticket_start'] == $detail['date_ticket_start']
                && $data['date_ticket_end'] == $detail['date_ticket_end']
                && $data['status'] == 0
                && $detail['status'] == 1
            ){
                $outOfStock = true;
            }

            if(!$outOfStock){
                //判断写入的审核状态
                if(($toolsDetail['type'] == 'stadium' && cfg('life_tools_sports_audit_type') == '0') || //体育场馆
                    ($toolsDetail['type'] == 'course' && cfg('life_tools_sports_course_audit_type') == '0') || //体育课程
                    ($toolsDetail['type'] == 'scenic' && cfg('life_tools_scenic_ticket_audit') == '0')//景区
                ){
                    $data['audit_status'] = 1;
                    $data['audit_msg'] = '自动审核通过';
                    $data['status'] = $param['status'];
                }else{
                    $data['audit_status'] = 0;
                    $data['status'] = 0;
                }
            }
        }
        $data['add_audit_time'] = time();
        $msg = L_('新增');
        if($ticketId){// 编辑
            $ticket = $this->lifeToolsTicketModel->getDetail($ticketId);
            LifeToolsTicketService::checkSku($ticket);
            $param['ticket_id']=$ticketId;
            $msg = L_('编辑');
            $where = [
                'mer_id' => $data['mer_id'],
                'ticket_id' => $ticketId,
                'is_del' => 0
            ];
            $detail = $this->getOne($where);
            if(empty($detail)){
                throw new \think\Exception(L_('数据不存在'), 1001);
            }

            if($detail['stock_type'] == 1){// 永久库存
                $data['stock_num'] =  $data['stock_num'] + $detail['sale_count'];
            }
            $res = $this->updateThis($where, $data);
        }else{
            if($toolsDetail['type'] == 'stadium' || $toolsDetail['type'] == 'scenic'){
                $data['stock_type'] = '2';// 每日库存
            }else{
                $data['stock_type'] = '1';// 全部库存
            }
            $param['ticket_id']=$res = $ticketId = $this->add($data);
        }

        if($res === false){
            throw new \think\Exception($msg.L_('失败'), 1003);
        }
        // 保存日历价格
        (new LifeToolsTicketSaleDayService())->del(['ticket_id' => $ticketId]);

        //添加sku
        $this->dealSkuAndSpec($spec_list, $list, $param);
        if(empty($list)) {
            if ($priceCalendar) {
                $saveData = [];
                foreach ($priceCalendar as $value) {
                    $saveData[] = [
                        'ticket_id' => $ticketId,
                        'day' => $value['day'] ?? '',
                        'price' => $value['price'] ?? $data['price'],
                        'is_sale' => $value['is_sale'] ?? 1,
                    ];
                }
                if ($saveData) {
                    (new LifeToolsTicketSaleDayService())->addAll($saveData);
                }
            } else {// 保存近三个月的数据
                $startDay = time();
                $lastDay = strtotime('+3 month');

                $num = ceil(($lastDay - $startDay) / 86400);
                $saveData = [];
                for ($i = 0; $i < $num; $i++) {
                    $day = date('Y-m-d', strtotime('+' . $i . ' day'));
                    $saveData[] = [
                        'ticket_id' => $ticketId,
                        'day' => $day,
                        'price' => $data['price'],
                        'is_sale' => 1,
                    ];
                }
                if ($saveData) {
                    (new LifeToolsTicketSaleDayService())->addAll($saveData);
                }

            }
        }

        //自定义表单
        if($ticketId){

            //清除原有的
            $condition = [];
            $condition[] = ['ticket_id', '=', $ticketId];
            $this->lifeToolsTicketCustomFormModel->where($condition)->delete();

            if(is_array($param['custom_form']) && count($param['custom_form']) > 0){
                $saveData = [];
                $time = time();
                foreach ($param['custom_form'] as $key => $value) {
                    if(empty($value['title'])){
                        continue;
                    }
                    $tmp = [];
                    $tmp['ticket_id'] = $ticketId;
                    $tmp['title'] = $value['title']?? '';
                    $tmp['type'] = $value['type'] ?? 'text';
                    $tmp['sort'] = $value['sort'] ?? 0;
                    $tmp['content'] = $value['content'] ?? '';
                    $tmp['is_must'] = $value['is_must'] ?? 0;
                    $tmp['status'] = $value['is_status'] ?? 0;
                    $tmp['is_del'] = 0;
                    $tmp['add_time'] = $time;
                    $saveData[] = $tmp;
                }
                $this->lifeToolsTicketCustomFormModel->saveAll($saveData);
            }
        }

        $returnArr['msg'] = $msg.L_('成功');
        return $returnArr;
    }

    /**
     * @throws Exception
     * 处理sku 和 spec
     */
    public function dealSkuAndSpec($spec_list, $list, $param)
    {
        $specIds = (new LifeToolsTicketSpec())->where(['ticket_id' => $param['ticket_id']])->column('spec_id');
        if ($specIds) {
            (new LifeToolsTicketSpec())->updateThis([['spec_id', 'in', $specIds]], ['is_del' => 1]);//删除规格
            (new LifeToolsTicketSpecVal())->updateThis([['spec_id', 'in', $specIds]], ['is_del' => 1]);//删除规格值
        }
        (new LifeToolsTicketSku())->updateThis(['ticket_id' => $param['ticket_id']], ['is_del' => 1]);//删除sku
        if (in_array($param['is_sku'] ,[1,2])) {//多规格
            $sku_arr   = [];
            $spec_info = [];
            if (!empty($spec_list)) {
                foreach ($spec_list as $sk => $val) {
                    if ($val['id'] == 0) {
                        $val['id'] = (new LifeToolsTicketSpec())->add([
                            'name'        => $val['name'],
                            'ticket_id'    => $param['ticket_id'],
                            'create_time' => time()
                        ]);
                    } else {
                        (new LifeToolsTicketSpec())->updateThis(['spec_id' => $val['id']], ['name' => $val['name'], 'is_del' => 0]);
                    }
                    foreach ($val['list'] as $kk => $vv) {
                        $specValId = $vv['id'];
                        if ($vv['id'] == 0) {
                            $specValId = $kk;
                            $vv['id']  = (new LifeToolsTicketSpecVal())->add([
                                'name'        => $vv['name'],
                                'spec_id'     => $val['id'],
                                'create_time' => time()
                            ]);
                        } else {
                            (new LifeToolsTicketSpecVal())->updateThis(['id' => $vv['id']], ['name' => $vv['name'], 'is_del' => 0]);
                        }
                        $spec_info[$sk][] = $val['id'] . ':' . $vv['id'] . ';' . $vv['name'] . ';' . $specValId;
                    }
                }
                $sku_info = $this->combination($spec_info);
                foreach ($sku_info as $sv) {
                    $sku1 = '';
                    $sku2 = '';
                    $sku3 = '';
                    foreach ($sv as $svv) {
                        $skuvv = explode(';', $svv);
                        $sku1 .= $sku1 === '' ? $skuvv[2] : '_' . $skuvv[2];
                        $sku2 .= $sku2 === '' ? $skuvv[0] : '|' . $skuvv[0];
                        $sku3 .= $sku3 === '' ? $skuvv[1] : ',' . $skuvv[1];
                    }
                    $sku_arr[$sku1]['sku_info'] = $sku2;
                    $sku_arr[$sku1]['sku_str']  = $sku3;
                }
            }
            if (!empty($spec_list) && !empty($list)) {
                foreach ($list as $v) {
                    $skuData = !empty($sku_arr[$v['index']]['sku_info']) ? (new LifeToolsTicketSku())->getOne([
                        'sku_info'     => $sku_arr[$v['index']]['sku_info'],
                        'ticket_id' => $param['ticket_id'],
                        'is_del'       => 1
                    ]) : [];
                    if ($skuData) {
                        $data = [
                            'price'      => $v['price'],
                            'sale_price' => $v['price'],
                            'stock_num'  => $v['stock_num'],
                            'sku_str'    => $sku_arr[$v['index']]['sku_str'],
                            'is_del'     => 0,
                        ];
                        (new LifeToolsTicketSku())->updateThis(['sku_id' => $skuData['sku_id']], $data);
                        $new_skuId=$skuData['sku_id'];
                    } else {
                        $data = [
                            'price'        => $v['price'],
                            'sale_price'   => $v['price'],
                            'stock_num'    => $v['stock_num'],
                            'origin_stock' => $v['stock_num'],
                            'is_del'       => 0,
                            'sku_str'      => $sku_arr[$v['index']]['sku_str'],
                        ];
                        $data['ticket_id'] = $param['ticket_id'];
                        $data['sku_info']     = $sku_arr[$v['index']]['sku_info'];
                        $data['create_time']  = time();
                        $new_skuId=(new LifeToolsTicketSku())->add($data);
                    }
                    if (isset($v['price_calendar']) && !empty($v['price_calendar'])) {
                        $saveData = [];
                        foreach ($v['price_calendar'] as $value2) {
                            $saveData[] = [
                                'ticket_id' => $param['ticket_id'],
                                'day' => $value2['day'] ?? '',
                                'sku_id' => $new_skuId ?? 0,
                                'price' => $value2['price'] ?? $value2['price'],
                                'is_sale' => $value2['is_sale'] ?? 1,
                            ];
                        }
                        if ($saveData) {
                            (new LifeToolsTicketSaleDayService())->addAll($saveData);
                        }
                    }
                    else {// 保存近三个月的数据
                        $startDay = time();
                        $lastDay = strtotime('+3 month');
                        $num = ceil(($lastDay - $startDay) / 86400);
                        $saveData = [];
                        for ($i = 0; $i < $num; $i++) {
                            $day = date('Y-m-d', strtotime('+' . $i . ' day'));
                            $saveData[] = [
                                'ticket_id' => $param['ticket_id'],
                                'sku_id' => $new_skuId ?? 0,
                                'day' => $day,
                                'price' => $data['price'],
                                'is_sale' => 1,
                            ];
                        }
                        if ($saveData) {
                            (new LifeToolsTicketSaleDayService())->addAll($saveData);
                        }
                    }
                }
            }
        }
        return true;
    }


    /**
     * 获取多维数组所有组合
     */
    public function combination($options = []) {
        $rows = [];
        foreach ($options as $option => $items) {
            if (count($rows) > 0) {
                $clone = $rows;// 2、将第一列作为模板
                $rows  = [];// 3、置空当前列表，因为只有第一列的数据，组合是不完整的
                foreach ($items as $item) {// 4、遍历当前列，追加到模板中，使模板中的组合变得完整
                    $tmp = $clone;
                    foreach ($tmp as $index => $value) {
                        $value[$option] = $item;
                        $tmp[$index]    = $value;
                    }
                    $rows = array_merge($rows, $tmp);// 5、将完整的组合拼回原列表中
                }
            } else {// 1、先计算出第一列
                foreach ($items as $item) {
                    $rows[][$option] = $item;
                }
            }
        }
        return $rows;
    }
    /**
     * 添加编辑门票
     * @param $param
     * @return array
     */
    public function del($param = [])
    {

        $ticketId = $param['ticket_id'] ?? 0;// 门票ID
        $merId = $param['mer_id'] ?? 0;// 商家ID
        if(empty($merId) || empty($ticketId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        // 查询原来数据
        $where = [
            'mer_id' => $merId,
            'ticket_id' => $ticketId,
            'is_del' => 0
        ];
        $detail = $this->getOne($where);
        if(empty($detail)){
            throw new \think\Exception(L_('数据不存在或已删除'), 1001);
        }

        // 查询未核销门票 存在未核销的不能删除
        $whereOrder = [
            'ticket_id' => $ticketId,
            'order_status' => 20,
        ];
        $order = (new LifeToolsOrderService)->getOne($whereOrder);
        if($order){
            throw new \think\Exception(L_('该门票存在未核销订单不能删除'), 1003);
        }

        // 假删除
        $data = [
            'is_del' => 1
        ];
        $res = $this->updateThis($where, $data);
        if($res === false){
            throw new \think\Exception(L_('删除失败'), 1003);
        }

        $returnArr['msg'] = L_('删除成功');
        return $returnArr;
    }

    /**
     *插入一条数据
     * @param array $data
     * @return int|bool
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $data['create_time'] = time();
        $data['last_time'] = time();

        $id = $this->lifeToolsTicketModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *获取一条条数据
     * @param array $where
     * @return array
     */
    public function getOne($where){
        $result = $this->lifeToolsTicketModel->where($where)->append(['label_arr','audit_status_text'])->find();
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取多条条数据
     * @param array $where
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->lifeToolsTicketModel->getSome($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
    *获取多条条数据
    * @param array $where
    * @return array
    */
   public function getSomeAndPage($where = [], $field = true,$order=true,$page=0,$limit=0){
//       $result = $this->lifeToolsTicketModel->getSomeAndPage($where,$field, $order, $page,$limit);
       $result = $this->lifeToolsTicketModel->getListAndDistribution($where,$field, $order, $page,$limit);
       if(empty($result)) return [];
       return $result->toArray();
   }

    /**
     *获取数据总数
     * @param array $where
     * @return array
     */
    public function getCount($where = []){
        $result = $this->lifeToolsTicketModel->getCount($where);
        if(empty($result)) return 0;
        return $result;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $data['last_time'] = time();

        $result = $this->lifeToolsTicketModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * 审核列表
     */
    public function lifeToolsAudit($params)
    {
        $field = 'r.*,r.price as money,t.audit_status AS tools_audit_status,t.type,t.phone,m.name AS merchant_name,t.title as tools_title';
        $condition = [];

        if(empty($params['tools_type'])){
            throw new \think\Exception('tools_type不能为空！');
        }

        if($params['tools_type'] == 'sports'){
            $condition[] = ['t.type' ,'in', ['stadium', 'course']];
            $nauditWhere = ['t.type' ,'in', ['stadium', 'course']];
        }else{
            $condition[] = ['t.type' ,'=', $params['tools_type']];
            $nauditWhere = ['t.type' ,'=', $params['tools_type']];
        }

        if(!empty($params['keywords'])){
            $condition[] = ['r.title|t.introduce|t.phone|t.address|r.label' ,'like', '%'.$params['keywords'].'%'];
        }
        // 状态
        if(isset($params['audit_status']) && !is_null($params['audit_status'])){
            $condition[] = ['r.audit_status' ,'=', $params['audit_status']];
        }
        $typeMap = [
            'scenic'    =>      '景区',
            'stadium'   =>      '场馆',
            'course'   =>      '课程'
        ];

        $auditStatusMap = [
            0   =>  '待审核',
            1   =>  '审核成功',
            2   =>  '审核失败'
        ];

        $data = $this->lifeToolsTicketModel->getAuditList($condition, $field, $params['page_size'])->toArray();

        foreach ($data['data'] as $key => $value) {
            $data['data'][$key]['type_text'] = $typeMap[$value['type']] ?? '';
            $data['data'][$key]['tools_audit_status_text'] = $auditStatusMap[$value['tools_audit_status']] ?? '';
            $data['data'][$key]['add_audit_time'] = $data['data'][$key]['add_audit_time'] ? date('Y.m.d H:i',$data['data'][$key]['add_audit_time']) : '';
            $data['data'][$key]['audit_time'] = $data['data'][$key]['audit_time'] ? date('Y.m.d H:i',$data['data'][$key]['audit_time']) : '';
        }
        return $data;
    }

     /**
     * 门票审核
     */
    public function auditTicket($params)
    {
        if(!in_array($params['audit_status'], [1, 2])){
            throw new \think\Exception('审核状态不正确！');
        }
        if($params['audit_status'] == 2 && !$params['audit_msg']){
            throw new \think\Exception('请填写审核理由！');
        }
        if(!is_array($params['ticket_ids']) || !count($params['ticket_ids'])){
            throw new \think\Exception('ticket_ids不能为空！');
        }
        Db::startTrans();
        try {
            $this->lifeToolsTicketModel->where('ticket_id', 'in', $params['ticket_ids'])->select()->each(function($item) use($params){
                $item->audit_status = $params['audit_status'];
                $item->audit_msg = $params['audit_msg'];
                $item->status = $params['audit_status'] == 1 ? 1 : 0;
                $item->last_time = time();
                $item->audit_time = time();
                $item->save();
            });
            //写入审核日志记录
            $params['admin_id'] = $params['admin_id'] ?? 0;
            $params['audit_object_ids'] = $params['ticket_ids'];
            //判断当前审核类型
            $params['type'] = 'ticket';
            $auditService = new AuditService();
            $auditService->addLog($params);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }

        return true;
    }
    public static function checkSku($ticket){
        if (0 == $ticket['is_sku']) {
            $hasBindSecondsKill = self::hasBindSecondsKill($ticket['ticket_id'], $ticket['mer_id']);

            if($hasBindSecondsKill && in_array(input('is_sku'),[LifeToolsTicket::SKU_MULTI_SPEC, LifeToolsTicket::SKU_STADIUM]) ){
                throw_exception('此门票正在参与“体育秒杀”，需要关闭体育秒杀才能开启多规格。');
            }

            $hasBindSportsActivity = self::hasBindSportsActivity($ticket['ticket_id'], $ticket['mer_id']);

            if($hasBindSportsActivity && in_array(input('is_sku'),[LifeToolsTicket::SKU_MULTI_SPEC, LifeToolsTicket::SKU_STADIUM]) ){
                throw_exception('此门票正在参与“体育约战”，需要关闭体育约战才能开启多规格。');
            }
        }
        return true;
    }

    public static function hasBindSecondsKill(int $ticketId, int $merId) : bool
    {
        $ticket = LifeToolsSportsSecondsKillDetail::alias('s')
            ->join('life_tools_sports_seconds_kill a', 's.id = a.act_id')
            ->join('life_tools_sports_seconds_kill_ticket_detail d', 'd.activity_id = a.id')
            ->field('d.id')
            ->where([
                ['a.is_del', '=', 0],
                ['a.type', '=', 'limited'],
                ['a.mer_id', '=', $merId],
                ['d.ticket_id', '=', $ticketId]
            ])
            ->find();

        return empty($ticket) ? false : true;
    }

    public static function hasBindSportsActivity(int $ticketId, int $merId) : bool
    {
        $ticket = LifeToolsSportsActivity::alias('s')
            ->join('life_tools_sports_activity_bind_ticket lt','lt.activity_id = s.activity_id')
            ->join('life_tools_ticket ticket','ticket.ticket_id = lt.ticket_id')
            ->field('s.activity_id')
            ->where([
                ['s.is_del', '=', 0],
                ['s.mer_id', '=', $merId],
                ['ticket.ticket_id', '=', $ticketId]
            ])
            ->find();

        return empty($ticket) ? false : true;
    }

    /**
     * @param int $ticketId
     * @param int $merId
     * @return bool
     */
    public function hasBindSports(int $ticketId, int $merId) : bool
    {
        if(self::hasBindSecondsKill($ticketId,  $merId) || self::hasBindSportsActivity($ticketId,  $merId)){
            return true;
        }
        return false;
    }
    /*
     * 获取门票列表
     * @author nidan
     * @date 2022/4/1
     * @param $param
     */
    public function getTicketList($param)
    {
        if(!isset($param['mer_id'])){
            throw new \think\Exception('参数缺失！');
        }
        $page = $param['page']??1;
        $pageSize = $param['page_size']??10;
        $where = [
            ['r.is_del','=',0],
            ['r.status','=',1],
            ['g.is_del','=',0],
            ['g.status','=',1],
            ['g.mer_id','=',$param['mer_id']],
            ['g.type','=','scenic']
        ];
        if($param['select_date'] == date('Y-m-d')){
            //筛除不能购买当天票的门票
            $where[] = ['t.can_book_today','=',1];
            //筛除超过预定当天门票截止时间的门票
            $where[] = ['t.book_today_time','>=',date('H:i:s')];
        }
        //根据门票名称搜索
        if(!empty($param['keyWords'])){
            array_push($where,['r.title','like','%'.$param['keyWords'].'%']);
        }
        //根据景区查询
        if(!empty($param['tools_id'])){
            array_push($where,['r.tools_id','=',$param['tools_id']]);
        }
        $order = 'r.sort desc,r.ticket_id desc';
        $field = ['r.ticket_id','r.tools_id','g.title as tools_title','r.title as ticket_title','r.stock_num','r.sale_count','r.stock_type','s.price','r.price as ticket_price','s.pigcms_id'];
        $result = $this->lifeToolsTicketModel->getListAndPrice($where, $field,$page,$pageSize,$order,$param['select_date']);
        if($result === false) {
            return [];
        }
        foreach ($result['list'] as &$v){
            $sumWhere = [
                ['ticket_id', '=', $v['ticket_id']],
                ['ticket_time', '=', $param['select_date']],
                ['order_status', 'not in', [50, 60]],
                ['is_give', '=', 0]
            ];
            //剩余库存
            // $v['stock_num'] = $v['stock_num'] - (($v['stock_type'] == 1) ? $v['sale_count'] : $this->lifeToolsOrderModel->where($sumWhere)->sum('num'));
            $v['price'] = $v['pigcms_id']?$v['price']:$v['ticket_price'];
            unset($v['sale_count'],$v['stock_type'],$v['ticket_price']);
        }
        return $result;
    }

    /**
     * 查询未审核数量
     */
    public function getNotAuditNum($param)
    {
        $data = [];
        if($param['tools_type'] == 'sports'){
            $nauditWhere = ['t.type' ,'in', ['stadium', 'course']];
            $auditWhere = ['type' ,'in', ['stadium', 'course']];
        }else{
            $nauditWhere = ['t.type' ,'=', $param['tools_type']];
            $auditWhere = ['type' ,'=', $param['tools_type']];
        }
        //获取未审核数量
        $data['not_audit_num_ticket'] = $this->lifeToolsTicketModel->getAuditList([['r.is_del','=',0],['r.audit_status','=',0],['t.is_del','=',0],$nauditWhere],false,0);
        if($data['not_audit_num_ticket'] > 99){
            $data['not_audit_num_ticket'] = '99+';
        }
        $data['not_audit_num_tools'] = $this->lifeToolsModel
            ->where([['is_del','=',0],['audit_status','=',0],$auditWhere])
            ->count();
        if($data['not_audit_num_tools'] > 99){
            $data['not_audit_num_tools'] = '99+';
        }
        return $data;
    }
}