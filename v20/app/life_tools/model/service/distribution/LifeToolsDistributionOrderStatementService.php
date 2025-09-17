<?php

/**
 * 分销结算单
 */

namespace app\life_tools\model\service\distribution;


use app\common\model\service\export\ExportService as BaseExportService;
use app\life_tools\model\db\LifeToolsDistributionLog;
use app\life_tools\model\db\LifeToolsDistributionOrder;
use app\life_tools\model\db\LifeToolsDistributionOrderStatement;
use app\life_tools\model\db\LifeToolsDistributionUser;
use app\life_tools\model\db\LifeToolsDistributionUserBindMerchant;
use think\facade\Db;

class LifeToolsDistributionOrderStatementService
{

    public $lifeToolsDistributionOrderStatementModel = null;
    public $lifeToolsDistributionOrderModel = null;
    public $lifeToolsDistributionUserModel = null;
    public $lifeToolsDistributionUserBindMerchantModel = null;
    public $lifeToolsDistributionLogModel = null;

    public function __construct()
    {
        $this->lifeToolsDistributionOrderStatementModel = new LifeToolsDistributionOrderStatement();
        $this->lifeToolsDistributionOrderModel = new LifeToolsDistributionOrder();
        $this->lifeToolsDistributionUserModel = new LifeToolsDistributionUser();
        $this->lifeToolsDistributionUserBindMerchantModel = new LifeToolsDistributionUserBindMerchant();
        $this->lifeToolsDistributionLogModel = new LifeToolsDistributionLog();
    }

    /**
     * 生成结算单
     */
    public function addStatement($params)
    {
        if (!$params['mer_id'] || !$params['user_id']) {
            throw new \think\Exception('参数缺失！');
        }
        if (!$params['name']) {
            throw new \think\Exception('结算单名称不能为空！');
        }
        if (!$params['company']) {
            throw new \think\Exception('发布单位不能为空！');
        }
        if (!$params['order_ids']) {
            throw new \think\Exception('至少选择一个订单！');
        }

        //计算结算单总金额
        $orderIdAry = explode(',',$params['order_ids']);
        $where[] = ['id','IN',$orderIdAry];
        $where[] = ['status','=',0];
        $where[] = ['user_id','=',$params['user_id']];

        $total = 0;//总金额
        //查询有效的订单
        $orderInfo = $this->lifeToolsDistributionOrderModel->getAllOrder($where);
        if(count($orderInfo) != count($orderIdAry)){
            throw new \think\Exception('有异常订单，无法生成结算单，请刷新页面重新操作！');
        }
        $commission_level_1 = 0;
        $commission_level_2 = 0;
        foreach ($orderInfo as $v){
            $price = $v['commission_level_1'] + $v['commission_level_2'];
            $commission_level_1 += $v['commission_level_1'];
            $commission_level_2 += $v['commission_level_2'];
            $total += $price;
        }
        if($params['reject_money'] > $total){
            throw new \think\Exception('驳回金额不能超过佣金总金额');
        }
        Db::StartTrans();
        try {
            //生成结算单
            $add = $this->lifeToolsDistributionOrderStatementModel->insert([
                'mer_id' => $params['mer_id'],
                'user_id' => $params['user_id'],
                'order_ids' => $params['order_ids'],
                'name' => $params['name'],
                'company' => $params['company'],
                'total_money' => $total,
                'reject_money' => $params['reject_money'],
                'commission_level_1'    => $commission_level_1,
                'commission_level_2'    => $commission_level_2,
                'create_time' => time(),
            ]);
            if(!$add){
                throw new \think\Exception('生成结算单失败！');
            }
            //修改订单状态
            $update = $this->lifeToolsDistributionOrderModel->updateThis([['id','IN',$orderIdAry]],['status'=>1]);
            if(!$update){
                throw new \think\Exception('修改订单状态失败！');
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return ['msg' => '生成结算单成功！'];
    }


    /**
     * 获取分销员的结算单
     */
    public function getOrderStatement($params)
    {
        $user = (new LifeToolsDistributionUserService)->checkUser($params['uid']);

        $condition = [];
        $condition[] = ['uid', '=', $params['uid']];
        $condition[] = ['user_id', '=', $user->user_id];
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['audit_status', '=', 1];
        $condition[] = ['is_del', '=', 0];
        $userMer = $this->lifeToolsDistributionUserBindMerchantModel->where($condition)->find();
        if(!$userMer){
            throw new \think\Exception('非此商家的分销员！');
        }

        $condition = [];
        $condition[] = ['mer_id', '=', $userMer->mer_id];
        $condition[] = ['user_id', '=', $user->user_id];
        if(!empty($params['keywords'])){
            $condition[] = ['name', 'like', "%{$params['keywords']}%"];
        }
        $statementList = $this->lifeToolsDistributionOrderStatementModel
                        ->where($condition)
                        ->order('create_time DESC')
                        ->paginate($params['page_size'])
                        ->append(['status_text', 'time_text'])
                        ->each(function($item, $key){
                            $item->statement_money = $item->total_money - $item->reject_money;
                        });
        return $statementList;
    }


    /**
     * 获取结算单详情
     */
    public function getOrderStatementDetail($params)
    {
        $user = (new LifeToolsDistributionUserService)->checkUser($params['uid']);

        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['statement_id']];
        $condition[] = ['user_id', '=', $user->user_id];
        $statement = $this->lifeToolsDistributionOrderStatementModel->with(['merchant'=>function($query){
            $query->field(['mer_id', 'phone']);
        }])->where($condition)->find();
        if(!$statement){
            throw new \think\Exception('结算单不存在！');
        }
        $order_ids = $statement->order_ids;
        if(!$order_ids){
            throw new \think\Exception('消费清单为空！');
        }
        $orderIdsArr = explode(',', $order_ids);
        $condition = [];
        $condition[] = ['id', 'in', $orderIdsArr];
        $with = [];
        $with['order'] = function($query){
            $query->field(['order_id', 'orderid', 'nickname', 'phone', 'price', 'add_time'])->bind(['orderid', 'nickname', 'phone', 'price', 'add_time']);
        };
        $order_list = $this->lifeToolsDistributionOrderModel
                    ->with($with)
                    ->where($condition)
                    ->paginate($params['page_size'])
                    ->append(['status_text'])
                    ->each(function($item, $key){
                        $item->tourist_info = '**' . mb_substr($item->nickname, -1) . '/' . substr_replace($item->phone, '****', 3, 4);
                        $item->order_time = date('Y.m.d H:i', $item->add_time);
                        $item->commission = $item->commission_level_1 + $item->commission_level_2;
                        $item->hidden(['nickname', 'phone']);
                    })->toArray();

        //结算单状态
        $order_list['statement_status'] = $statement->statement_status;
        $statementStatusMap = ['待确定', '已确定'];
        $order_list['statement_status_text'] = $statementStatusMap[$statement->statement_status] ?? '';
        $order_list['statement_id'] = $statement->pigcms_id;
        $order_list['phone'] = $statement->merchant->phone;
        $order_list['total_money'] = $statement->total_money;
        $order_list['reject_money'] = $statement->reject_money;
        $order_list['statement_money'] =$statement->total_money - $statement->reject_money;
        return $order_list;
    }

    /**
     * 确认结算单
     */
    public function confirmStatementOrder($params)
    {
        $user = (new LifeToolsDistributionUserService)->checkUser($params['uid']);
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['statement_id']];
        $condition[] = ['user_id', '=', $user->user_id];
        $statement = $this->lifeToolsDistributionOrderStatementModel->where($condition)->find();
        if(!$statement){
            throw new \think\Exception('结算单不存在！');
        }
        if($statement->statement_status == 1){
            throw new \think\Exception('结算单已结算，请勿重复操作！');
        }

        $order_ids = $statement->order_ids;
        if(!$order_ids){
            throw new \think\Exception('消费清单为空！');
        }
        $orderIdsArr = explode(',', $order_ids);
        $condition = [];
        $condition[] = ['id', 'in', $orderIdsArr];
        $distributionOrder = $this->lifeToolsDistributionOrderModel->where($condition)->select();
        Db::StartTrans();
        try {
            $orderId = [];
            foreach ($distributionOrder as $item) {
                if($item->status != 1){
                    throw new \think\Exception('结算单中含有状态不正确的订单！订单ID：'. $item->id);
                }
                if(!in_array($item->order_id , $orderId)){
                    $orderId[] = $item->order_id;
                }
                //修改结算清单状态
                $item->status = 2;
                $item->save();
            }

            $this->lifeToolsDistributionLogModel->where('order_id', 'in', $orderId)->where('status', 1)->update([
                'status'    =>  2
            ]);
            //修改结算单状态
            $statement->statement_status = 1;
            $statement->confirm_time = time();
            $statement->save();

            // $user->total_commission += ($statement->total_money - $statement->reject_money);
            $conditionStatement = [];
            $conditionStatement[] = ['user_id', '=', $user->user_id];
            $conditionStatement[] = ['statement_status', '=', 1];
            $totalStatement = $this->lifeToolsDistributionOrderStatementModel->field(['SUM(total_money) AS total_commission', 'SUM(reject_money) AS total_reject'])->where($conditionStatement)->find();
            $user->total_commission = $totalStatement['total_commission'] - $totalStatement['total_reject'];
            $user->rejected_money = $totalStatement['total_reject'];
            $user->save();

            $condition = [];
            $condition[] = ['uid', '=', $params['uid']];
            $condition[] = ['user_id', '=', $user->user_id];
            $condition[] = ['mer_id', '=', $statement->mer_id];
            $condition[] = ['audit_status', '=', 1];
            $condition[] = ['is_del', '=', 0];
            $userMer = $this->lifeToolsDistributionUserBindMerchantModel->where($condition)->find();
            if(!$userMer){
                throw new \think\Exception('非此商家的分销员！');
            } 

            $conditionStatement[] = ['mer_id', '=', $statement->mer_id];
            $totalStatementMer = $this->lifeToolsDistributionOrderStatementModel->field(['SUM(reject_money) AS total_reject'])->where($conditionStatement)->find();

            $userMer->rejected_money = $totalStatementMer['total_reject'];
            $userMer->save();

            Db::commit();
        } catch (\think\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }

        return true;
    }

    /**
     * 获取商家后台结算单列表
     * @author Nd
     * @date 2022/4/13
     */
    public function getStatement($params)
    {
        if(!$params['mer_id']){
            throw new \think\Exception('必要信息缺失！');
        }
        $params['page_size'] = $params['function_type'] ? 0 : ($params['page_size']??10);
        $where[] = ['a.mer_id','=',$params['mer_id']];
        if($params['search_type'] == 1 && $params['search_content']){
            $where[] = ['c.nickname','like','%'.$params['search_content'].'%'];
        }
        if($params['search_type'] == 2 && $params['search_content']){
            $where[] = ['c.phone','=',$params['search_content']];
        }
        if($params['status'] != 2){
            $where[] = ['a.statement_status','=',$params['status']];
        }
        $field = ['a.pigcms_id','a.name','c.nickname','c.phone','a.create_time','a.order_ids','a.statement_status','a.confirm_time','a.total_money','a.reject_money'];
        $order = 'a.pigcms_id desc';
        $data = $this->lifeToolsDistributionOrderStatementModel->getList($where,$field,$order,$params['page_size']);
        $list = $params['function_type'] ? $data : $data['data'];
        foreach ($list as &$v){
            $v['create_time'] = $v['create_time'] ? date('Y.m.d H:i',$v['create_time']) : '';
            $v['confirm_time'] = $v['confirm_time'] ? date('Y.m.d H:i',$v['confirm_time']) : '';
            $v['order_count'] = count(explode(',',$v['order_ids']));
            $v['order_count'] = count(explode(',',$v['order_ids']));
            $v['statement_status_msg'] = $v['statement_status'] ? '已确定' : '待确定';
            $v['money'] = $v['total_money'] - $v['reject_money'];
            unset($v['order_ids']);
        }
        if($params['function_type']){
            //导出
            $rand_number = time();
            return $this->exportStatement($rand_number,$list);
        }else{
            $returnData = $data;
            $returnData['data'] = $list;
        }
        return $returnData;
    }

    /**
     * 导出结算单列表
     * @author Nd
     * @date 2022/4/13
     */
    public function exportStatement($randNumber,$data)
    {
        $csvHead = array(
            L_('结算单名称'),
            L_('分销员/企业名称'),
            L_('手机号'),
            L_('发布时间'),
            L_('订单数量'),
            L_('状态'),
            L_('确定时间')
        );
        $csvData = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $csvData[$key] = [
                    $value['name'],
                    $value['nickname'],
                    $value['phone'],
                    $value['create_time'],
                    $value['order_count'],
                    $value['statement_status_msg'],
                    $value['confirm_time']
                ];
            }
        }
        $filename = date("Y-m-d", time()) . '-' . $randNumber . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }

    /**
     * 查询结算单详情
     * @author Nd
     * @date 2022/4/13
     */
    public function getDetail($params)
    {
        if(!$params['pigcms_id']){
            throw new \think\Exception('必要信息缺失！');
        }
        //查询结算单信息
        $info = $this->lifeToolsDistributionOrderStatementModel->getOne(['pigcms_id'=>$params['pigcms_id']],['order_ids']);
        if(!$info){
            throw new \think\Exception('未查询到结算单信息！');
        }
        $returnData = [];
        if($info['order_ids']){
            $orderIdAry = explode(',',$info['order_ids']);
            //查询订单列表信息
            $pageSize = $params['function_type'] ? 0 : ($params['page_size'] ?? 10);
            $where[] = ['a.id','IN',$orderIdAry];
            if($params['start_time'] && $params['end_time']){
                $where[] = ['b.pay_time','<=',strtotime($params['end_time'].' 23:59:59')];
                $where[] = ['b.pay_time','>=',strtotime($params['start_time'].' 00:00:00')];
            }
            if($params['search_type'] == 1 && $params['search_content']){
                $where[] = ['b.real_orderid','=',$params['search_content']];
            }
            if($params['search_type'] == 2 && $params['search_content']){
                $where[] = ['b.ticket_title','like','%'.$params['search_content'].'%'];
            }
            if($params['search_type'] == 3 && $params['search_content']){
                $where[] = ['b.nickname','like','%'.$params['search_content'].'%'];
            }
            if($params['search_type'] == 4 && $params['search_content']){
                $where[] = ['b.phone','=',$params['search_content']];
            }
            $field = ['a.order_id','b.real_orderid','b.ticket_title','c.title as tools_title','b.nickname','b.phone','b.num','b.price','a.commission_level_1','a.commission_level_2','a.status','b.pay_time','a.note'];
            $orderAry = $this->lifeToolsDistributionOrderModel->getList($where,$field,$pageSize);
            $orderAryInfo = $params['function_type'] ? $orderAry : $orderAry['data'];
            foreach ($orderAryInfo as &$v){
                $v['status_msg'] = $v['status'] ? ($v['status'] == 2 ? '已结算' : '结算中') : '待结算';
                $v['pay_time'] = $v['pay_time'] ? date('Y.m.d H:i',$v['pay_time']) : '';
            }
            if($params['function_type']){
                //导出
                $rand_number = time();
                return $this->exportStatementDetail($rand_number,$orderAryInfo);
            }else{
                $returnData = $orderAry;
                $returnData['data'] = $orderAryInfo;
            }
        }
        return $returnData;
    }

    /**
     * 导出结算单中的订单
     * @author Nd
     * @date 2022/4/13
     */
    public function exportStatementDetail($randNumber,$data)
    {
        $csvHead = array(
            L_('订单号'),
            L_('门票名称'),
            L_('景区名称'),
            L_('游客'),
            L_('手机号'),
            L_('数量'),
            L_('金额'),
            L_('佣金'),
            L_('邀请奖金'),
            L_('状态'),
            L_('下单日期'),
            L_('备注')
        );
        $csvData = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $csvData[$key] = [
                    "\t".$value['real_orderid']."\t",
                    $value['ticket_title'],
                    $value['tools_title'],
                    $value['nickname'],
                    $value['phone'],
                    $value['num'],
                    $value['price'],
                    $value['commission_level_1'],
                    $value['commission_level_2'],
                    $value['status_msg'],
                    $value['pay_time'],
                    $value['note']
                ];
            }
        }
        $filename = date("Y-m-d", time()) . '-' . $randNumber . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }
   
}
