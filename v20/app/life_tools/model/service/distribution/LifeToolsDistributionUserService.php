<?php

/**
 * 三级分销分销员service
 */

namespace app\life_tools\model\service\distribution;

use app\common\model\service\MerchantService;
use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\life_tools\model\db\LifeTools;
use app\life_tools\model\db\LifeToolsDistributionOrder;
use app\life_tools\model\db\LifeToolsDistributionOrderStatement;
use app\life_tools\model\db\LifeToolsDistributionSetting;
use app\life_tools\model\db\LifeToolsDistributionUser;
use app\life_tools\model\db\LifeToolsDistributionUserBindMerchant;
use app\common\model\service\export\ExportService as BaseExportService;
use app\life_tools\model\db\LifeToolsDistributionLog;
use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsTicketDistribution;
use app\life_tools\model\service\LifeToolsService;

class LifeToolsDistributionUserService
{

    public $lifeToolsModel = null;
    public $lifeToolsDistributionSettingModel = null;
    public $lifeToolsDistributionUserModel = null;
    public $lifeToolsDistributionOrderModel = null;
    public $lifeToolsDistributionUserBindMerchantModel = null;
    public $lifeToolsTicketDistributionModel = null;
    public $lifeToolsDistributionOrderStatementModel = null;
    public $lifeToolsDistributionLogModel = null;

    public function __construct()
    {
        $this->lifeToolsModel = new LifeTools();
        $this->lifeToolsDistributionSettingModel = new LifeToolsDistributionSetting();
        $this->lifeToolsDistributionUserModel = new LifeToolsDistributionUser();
        $this->lifeToolsDistributionOrderModel = new LifeToolsDistributionOrder();
        $this->lifeToolsDistributionUserBindMerchantModel = new LifeToolsDistributionUserBindMerchant();
        $this->lifeToolsTicketDistributionModel = new LifeToolsTicketDistribution();
        $this->lifeToolsDistributionOrderStatementModel = new LifeToolsDistributionOrderStatement();
        $this->lifeToolsDistributionLogModel = new LifeToolsDistributionLog();
    }

    /**
     * 分销员申请表单信息
     */
    public function getCustomFormData($params)
    {
        if(!empty($params['pigcms_id'])){
             
            $condition = [];
            $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
            $condition[] = ['is_del', '=', 0];
            $userBindMer = $this->lifeToolsDistributionUserBindMerchantModel->where($condition)->find();
            if(!$userBindMer){
                throw new \think\Exception('认证记录不存在!');
            }
            $params['mer_id'] = $userBindMer->mer_id;
            $params['type'] = $userBindMer->type;
        } 

        if (empty($params['mer_id'])) {
            throw new \think\Exception('mer_id不能为空！');
        }

        $setting = $this->lifeToolsDistributionSettingModel->where('mer_id', $params['mer_id'])->find();
        if (!$setting || ($params['type'] == 1 && !$setting->business_custom_form) || ($params['type'] == 0 && !$setting->personal_custom_form)) {
            throw new \think\Exception('未找到配置!');
        }
        $custom_form = $params['type'] == 1 ? json_decode($setting->business_custom_form, true) : json_decode($setting->personal_custom_form, true);

        if(isset($userBindMer->custom_form)){
            foreach($custom_form as $key => $val){
                foreach ($userBindMer->custom_form as $k => $v) {
                    if($val['title'] == $v['title']){
                        $custom_form[$key]['value'] = $v['value'];
                        if(isset($v['show_value'])){
                            $custom_form[$key]['show_value'] = $v['show_value'];
                        }
                    }
                }
            }
        }

        //过滤禁用表单
        foreach ($custom_form as $key => $val) {
            if ($val['status'] == 0) {
                unset($custom_form[$key]);
                continue;
            }
            if ($val['type'] == 'select') {
                $content = explode(',', $val['content']);
                $conArr = [];
                foreach ($content as $k => $v) {
                    $conArr[$k]['label'] = $v;
                    $conArr[$k]['value'] = $k;
                }
                $custom_form[$key]['content'] = $conArr;
            }
        }
        $custom_form = array_values($custom_form);

        //排序
        $sortArr = array_column($custom_form, 'sort');
        array_multisort($sortArr , SORT_DESC, $custom_form);
        return $custom_form;
    }


    /**
     * 提交审核
     */
    public function submitAudit($params)
    {
        //修改
        if(!empty($params['pigcms_id'])){
            $condition = [];
            $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
            $condition[] = ['is_del', '=', 0];
            $distributionBindMer = $distributionAudit = $this->lifeToolsDistributionUserBindMerchantModel->where($condition)->find();
            if(!$distributionAudit){
                throw new \think\Exception('认证记录不存在！');
            }
            $distributionBindMer->update_time = time();
            $params['mer_id'] = $distributionBindMer->mer_id;
        }else{

            if (empty($params['mer_id'])) {
                throw new \think\Exception('mer_id不能为空！');
            }
    
            if (empty($params['custom_form'])) {
                throw new \think\Exception('custom_form不能为空！');
            }
    
            if(!in_array($params['type'], [0, 1])){
                throw new \think\Exception('type类型不存在！');
            }
            $condition = [];
            $condition[] = ['uid', '=', $params['uid']];
            $condition[] = ['is_del', '=', 0];
            $distribution = $this->lifeToolsDistributionUserModel->where($condition)->find();
            if(!$distribution){
                $distribution = $this->lifeToolsDistributionUserModel;
                $distribution->uid = $params['uid'];
                $distribution->status = 1;
                $distribution->add_time = time();
                $distribution->save();
    
            }
            $condition = [];
            $condition[] = ['uid', '=', $params['uid']];
            $condition[] = ['mer_id', '=', $params['mer_id']];
            $condition[] = ['user_id', '=', $distribution->user_id];
            $condition[] = ['is_del', '=', 0];
            $distributionAudit = $this->lifeToolsDistributionUserBindMerchantModel->where($condition)->find();

            $distributionBindMer = $this->lifeToolsDistributionUserBindMerchantModel;
            $distributionBindMer->create_time = time();
            $distributionBindMer->uid = $params['uid'];
            $distributionBindMer->user_id = $distribution->user_id;
            $distributionBindMer->mer_id = $params['mer_id'];
            $distributionBindMer->type = $params['type'];
        }
        
        if($distributionAudit && $distributionAudit->audit_status == 1){
            throw new \think\Exception('已通过审核，请勿重复提交！');
        }
        if($distributionAudit && $distributionAudit->audit_status == 0){
            throw new \think\Exception('申请审核中，请勿重复提交！');
        }

        $setting = $this->lifeToolsDistributionSettingModel->where('mer_id', $params['mer_id'])->find();
        if (!$setting) {
            throw new \think\Exception('未找到配置!');
        }

        $form = $params['custom_form'];

        foreach($form as $k => &$item){
            if(empty($item['value']) && $item['is_must'] == 1){
                throw new \think\Exception($item['title'] . '参数不能为空');
            }

            if($item['type'] == 'image'){
                if(!is_array($item['value'])){
                    throw new \think\Exception('value格式有误！');
                }
                if(count($item['value']) > $item['image_max_num']){
                    throw new \think\Exception($item['title'] . '最多上传' . $item['image_max_num'] . '张图片');
                }

                $item['value'] = array_filter($item['value']);
            }

            if($item['type'] == 'idcard'){
                if(!is_idcard($item['value'])){
                    throw new \think\Exception('身份证号码填写不正确!');
                }
            }

            if($item['type'] == 'select'){
                if(!empty($item['value']) && is_array($item['value']) && isset($item['value'][0]['value'])){
                    $item['value'] = $item['value'][0]['value'];
                }
            }

        } 
        //自动审核
        if($setting->distributor_audit == 1){
            $distributionBindMer->audit_status = 1;
            $distributionBindMer->audit_msg = '自动审核通过';

            //审核通过，成为分销员
            $condition = [];
            $condition[] = ['user_id', '=', $distributionBindMer->user_id];
            $condition[] = ['status', '=', 1];
            $condition[] = ['is_del', '=', 0];
            $condition[] = ['is_cert', '=', 0];
            $user = $this->lifeToolsDistributionUserModel->where($condition)->find();
            if($user){
                $time = time();
                $user->is_cert = 1;
                $user->update_time = $time;
                $user->save();

                //上级邀请人数
                $inviteDistributionUser = $this->lifeToolsDistributionUserModel->where('user_id', $user->pid)->find();
                if($inviteDistributionUser && $inviteDistributionUser->is_del == 0){
                    $condition = [];
                    $condition[] = ['pid', '=', $inviteDistributionUser->user_id];
                    $condition[] = ['is_cert', '=', 1];
                    $condition[] = ['is_del', '=', 0];
                    $inviteDistributionUser->invit_num = $this->lifeToolsDistributionUserModel->where($condition)->count();
                    $inviteDistributionUser->update_time = $time;
                    $inviteDistributionUser->save();
                }
                
            }


             //查询商户名称
            $merchantInfo = (new MerchantService())->getInfo($params['mer_id']);
            if(!$merchantInfo){
                throw new \think\Exception('查询商户信息失败！');
            }
            // 获得用户的openID
            $nowUser = (new UserService())->getUser($params['uid']);
            $openid = $nowUser['openid']??'';
            if ($openid) {
                // 通过微信公众号发送审核通知
                $msgDataWx = [
                    'href' => get_base_url().'pages/lifeTools/distribution/index/index',
                    'wecha_id' => $openid,
                    'first' => $merchantInfo['name'].'分销员自动审核通过',
                    'keyword1' => cfg('site_name'),
                    'keyword2' => '审核通过',
                    'keyword3' => date("Y-m-d H:i"),
                    'remark' => '',

                ];
                (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
 
            }

        }else{
            $distributionBindMer->audit_status = 0;
        }
        $distributionBindMer->custom_form = $form;
        return $distributionBindMer->save();
    }

    /**
     * 获取统计数据
     */
    public function getAtatisticsInfo($params)
    {
        if (!$params['mer_id']) {
            throw new \think\Exception('参数缺失！');
        }
        $where = [
            ['b.mer_id','=',$params['mer_id']],
            ['a.is_del','=',0],
            ['b.audit_status','=',1],
            ['a.status','=',1]
        ];
        $field = ['a.user_id'];
        $order = 'a.user_id desc';
        //分销员列表
        $data = $this->lifeToolsDistributionUserModel->getList($params['mer_id'],$where, $field, $order);
        $statisticsInfo['price_settled_in'] = 0;//结算中总金额
        $statisticsInfo['price_settled'] = 0;//已结算总金额
        $statisticsInfo['order_count'] = 0;//分销订单总数量
        $statisticsInfo['distributor_count'] = count($data);//分销员总数量

        //查询商家所有订单
        $orderList = $this->lifeToolsDistributionOrderModel->getListByMerchant(['a.mer_id'=>$params['mer_id']],['b.price','a.status','a.user_id','a.commission_level_1','a.commission_level_2']);
        $statisticsInfo['order_count'] = count($orderList);
        foreach ($orderList as $v){
            $price = $v['commission_level_1'] + $v['commission_level_2'];
            if($v['status'] == 1){//结算中
                $statisticsInfo['price_settled_in'] += $price;
            }elseif($v['status'] == 2){//已结算
                $statisticsInfo['price_settled'] += $price;
            }
        }
        //查询解结算单
        $statement = $this->lifeToolsDistributionOrderStatementModel->getSome(['mer_id'=>$params['mer_id']],['total_money','reject_money','statement_status']);
        foreach ($statement as $val){
            if($val['statement_status'] == 0 && $val['reject_money']){//结算中
                $statisticsInfo['price_settled_in'] -= $val['reject_money'];
            }elseif($val['statement_status'] == 1 && $val['reject_money']){//已结算
                $statisticsInfo['price_settled'] -= $val['reject_money'];
            }
        }
        return $statisticsInfo;
    }

    /**
     * 获取分销员列表
     */
    public function getDistributorList($params)
    {
        if (!$params['mer_id']) {
            throw new \think\Exception('参数缺失！');
        }
        $where = [
            ['b.mer_id','=',$params['mer_id']],
            ['a.is_del','=',0],
            ['b.type','=',$params['type']],
            ['a.status','=',1]
        ];
        if($params['nickname']){
            array_push($where,['c.nickname','like','%'.$params['nickname'].'%']);
        }
        if($params['phone']){
            array_push($where,['c.phone','=',$params['phone']]);
        }
        if($params['status'] !== 'all'){
            array_push($where,['b.audit_status','=',$params['status']]);
        }
        if($params['user_ids']){
            $userIdExportAry = explode(',',$params['user_ids']);
            if(count($userIdExportAry) > 1){
                array_push($where,['a.user_id','IN',$userIdExportAry]);
            }
            if(count($userIdExportAry) == 1){
                array_push($where,['a.user_id','=',$params['user_ids']]);
            }

        }
        $field = ['a.user_id','c.nickname','c.phone','b.commission','b.invit_money','a.invit_num','b.audit_status','b.custom_form','b.audit_msg'];
        $order = 'a.user_id desc';
        //分销员列表
        if($params['function_type']){
            $data = $this->lifeToolsDistributionUserModel->getList($params['mer_id'],$where, $field, $order);
        }else{
            $data = $this->lifeToolsDistributionUserModel->getList($params['mer_id'],$where, $field, $order, $params['page'], $params['page_size']);
        }

        $userIdAry = [];
        foreach ($data as &$value){
            //自定义表单
            $value['custom_form'] = $value['custom_form']?(is_array($value['custom_form']) ?: json_decode($value['custom_form'])):[];
            array_push($userIdAry,$value['user_id']);
            //结算中订单数量
            $value['count_settled_in_order'] = 0;
            //已结算订单数量
            $value['count_settled_order'] = 0;
            //未结算订单数量
            $value['count_settled_not_order'] = 0;
            //已结算订单金额
            $value['price_settled'] = 0;
            //结算中订单金额
            $value['price_settled_in'] = 0;
            //未结算订单金额
            $value['price_settled_not'] = 0;
            //驳回总金额
            $value['reject_money'] = 0;
        }
        if(!$userIdAry){
            return $data;
        }
        //查询商家所有订单
        $orderList = $this->lifeToolsDistributionOrderModel->getListByMerchant(['a.mer_id'=>$params['mer_id']],['b.price','a.status','a.user_id','a.commission_level_1','a.commission_level_2']);
        if($orderList){
            foreach ($data as $value){
                foreach ($orderList as $v){
                    $price = $v['commission_level_1'] + $v['commission_level_2'];
                    if($v['user_id'] == $value['user_id'] && $v['status'] == 1){
                        $value['count_settled_in_order'] += 1;//结算中订单数量
                        $value['price_settled_in'] += $price;//结算中订单金额
                    }
                    if($v['user_id'] == $value['user_id'] && $v['status'] == 2){
                        $value['count_settled_order'] += 1;//已结算订单数量
                        $value['price_settled'] += $price;//已结算订单金额
                    }
                    if($v['user_id'] == $value['user_id'] && $v['status'] == 0){
                        $value['count_settled_not_order'] += 1;//未结算订单数量
                        $value['price_settled_not'] += $price;//未结算订单金额
                    }
                }
            }
            //查询结算单
            $statement = $this->lifeToolsDistributionOrderStatementModel->getSome(['mer_id'=>$params['mer_id']],['user_id','total_money','reject_money','statement_status']);
            if($statement){
                foreach ($data as $value){
                    foreach ($statement as $val){
                       if($val['user_id'] == $value['user_id'] && $val['statement_status'] == 0){
                           $value['price_settled_in'] -= $val['reject_money'];
                       }
                       if($val['user_id'] == $value['user_id'] && $val['statement_status'] == 1){
                           $value['price_settled'] -= $val['reject_money'];
                           $value['reject_money'] += $val['reject_money'];
                       }
                    }
                }
            }
        }

        if($params['function_type']){
            $rand_number = time();
            return $this->exportDistributorList($rand_number,$data);
        }
        return $data;
    }
    /**
     * 分销员列表导出
     * @return array
     */
    public function exportDistributorList($randNumber,$data)
    {
        $csvHead = array(
            L_('昵称'),
            L_('手机号'),
            L_('佣金'),
            L_('邀请奖励'),
            L_('邀请人数'),
            L_('结算中订单'),
            L_('已结算订单'),
            L_('结算中金额'),
            L_('已结算金额'),
            L_('状态')
        );

        $csvData = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $statusMsg = $value['audit_status'] ? ($value['audit_status'] == 1 ? '已认证' : '认证失败') : '未审核';
                $csvData[$key] = [
                    $value['nickname'],
                    $value['phone'],
                    $value['commission'],
                    $value['invit_money'],
                    $value['invit_num'],
                    $value['count_settled_in_order'],
                    $value['count_settled_order'],
                    $value['price_settled_in'],
                    $value['price_settled'],
                    $statusMsg,
                ];
            }
        }
        $filename = date("Y-m-d", time()) . '-' . $randNumber . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }
    /**
     * 获取下级用户
     */
    public function getLowerLevel($params)
    {
        if (!$params['mer_id'] || !$params['user_id'] || !$params['level']) {
            throw new \think\Exception('参数缺失！');
        }

        $where[] = ['a.pid','=',$params['user_id']];
        $where[] = ['a.status','=',1];
        $where[] = ['b.audit_status','=',1];
        $where[] = ['b.is_del','=',0];
        if($params['select_type'] == 1 && $params['content']){
            //昵称搜索
            $where[] = $params['level'] == 1 ? ['e.nickname','like','%'.$params['content'].'%'] : ['f.nickname','like','%'.$params['content'].'%'];
        }
        if($params['select_type'] == 2 && $params['content']){
            //手机号搜索
            $where[] = $params['level'] == 1 ? ['e.phone','=',$params['content']] : ['f.phone','=',$params['content']];
        }
        if($params['select_type'] == 3 && $params['content']){
            //上级昵称搜索
            $where[] = $params['level'] == 1 ? ['g.nickname','like','%'.$params['content'].'%'] : ['h.nickname','like','%'.$params['content'].'%'];
        }
        if($params['select_type'] == 4 && $params['content']){
            //上级手机号搜索
            $where[] = $params['level'] == 1 ? ['g.phone','=',$params['content']] : ['h.phone','=',$params['content']];
        }
        if($params['level'] == 1){//查询一级用户
            $field = ['a.user_id','e.nickname','e.phone','a.commission_level_1','a.commission_level_2','a.invit_num','g.nickname as p_nickname','g.phone as p_phone'];
            $order = 'a.user_id desc';
        }
        if($params['level'] == 2){//查询二级用户
            $field = ['c.user_id','f.nickname','f.phone','c.commission_level_1','c.commission_level_2','c.invit_num','h.nickname as p_nickname','h.phone as p_phone'];
            $where[] = ['c.status','=',1];
            $where[] = ['d.audit_status','=',1];
            $where[] = ['d.is_del','=',0];
            $order = 'c.user_id desc';
        }
        if($params['function_type']){//导出数据
            $data = $this->lifeToolsDistributionUserModel->getLowerLevel($params,$where,$field,$order);;
            $rand_number = time();
            return $this->exportLowerLevelList($rand_number,$data);
        }
        $data = $this->lifeToolsDistributionUserModel->getLowerLevel($params,$where,$field,$order,$params['page'],$params['page_size']);
        return $data;
    }

    /**
     * 分销员审核
     */
    public function audit($params)
    {
        if (!$params['mer_id'] || !$params['user_id']) {
            throw new \think\Exception('参数缺失！');
        }
        if(!in_array($params['audit_status'],[1,2])){
            throw new \think\Exception('审核状态异常！');
        }
        //查询审核记录
        $record = $this->lifeToolsDistributionUserBindMerchantModel->getOne(['user_id'=>$params['user_id'],'mer_id'=>$params['mer_id'],'is_del'=>0],'audit_status,audit_msg,uid');
        if(!$record){
            throw new \think\Exception('未查询到审核记录！');
        }
        if(!$record['uid']){
            throw new \think\Exception('申请信息异常！');
        }
        if($record['audit_status'] != $params['audit_status'] || $record['audit_msg'] != $params['audit_msg']){
            //修改审核记录
            $update = $this->lifeToolsDistributionUserBindMerchantModel->where(['user_id'=>$params['user_id'],'mer_id'=>$params['mer_id'],'is_del'=>0])->update([
                'audit_status' => $params['audit_status'],
                'audit_msg' => $params['audit_msg']
            ]);
            if(!$update){
                throw new \think\Exception('修改审核记录失败！');
            }
            //审核通过，成为分销员
            // if($params['audit_status'] == 1){
                $condition = [];
                $condition[] = ['user_id', '=', $params['user_id']];
                $condition[] = ['status', '=', 1];
                $condition[] = ['is_del', '=', 0];
//                $condition[] = ['is_cert', '=', 0];
                $user = $this->lifeToolsDistributionUserModel->where($condition)->find();
                if($user){
                    $time = time();
                    $user->is_cert = $params['audit_status'] == 1 ? 1 : 0;
                    $user->update_time = $time;
                    $user->save();

                    //计算上级邀请人数
                    $inviteDistributionUser = $this->lifeToolsDistributionUserModel->where('user_id', $user->pid)->find();
                    if($inviteDistributionUser && $inviteDistributionUser->is_del == 0){
                        $inviteDistributionUser->invit_num = $this->lifeToolsDistributionUserModel->where('pid', $inviteDistributionUser->user_id)->where('is_cert', 1)->count();
                        $inviteDistributionUser->update_time = $time;
                        $inviteDistributionUser->save();
                    }
                    
                }
            // }
        }
        //查询商户名称
        $merchantInfo = (new MerchantService())->getInfo($params['mer_id']);
        if(!$merchantInfo){
            throw new \think\Exception('查询商户信息失败！');
        }
        // 获得用户的openID
        $nowUser = (new UserService())->getUser($record['uid']);
        $openid = $nowUser['openid']??'';
        if ($openid) {
            $statusMsg = $params['audit_status'] == 1 ? '通过' : '不通过';
            $remark = $params['audit_status'] == 2 ? $params['audit_msg'] : '';
            // 通过微信公众号发送审核通知
            $href = $params['audit_status'] == 1 ? get_base_url().'pages/lifeTools/distribution/index/index' : get_base_url().'pages/lifeTools/distribution/index/myCertification';
            $msgDataWx = [
                'href' => get_base_url().'pages/lifeTools/distribution/index/index',
                'wecha_id' => $openid,
                'first' => $merchantInfo['name'].'分销员审核'.$statusMsg,
                'keyword1' => cfg('site_name'),
                'keyword2' => '审核'.$statusMsg,
                'keyword3' => date("Y-m-d H:i"),
                'remark' => $remark,

            ];
            $res = (new TemplateNewsService())->sendTempMsg('OPENTM400166399', $msgDataWx);
            $msg = '操作成功,已发送审核通知！';
        }else{
            $msg = '操作成功！';
        }
        return ['msg'=>$msg];
    }

     /**
     * 分销中心
     */
    public function distributionCenter2($params)
    {
        $condition = [];
        $condition[] = ['uid', '=', $params['uid']];
        $condition[] = ['is_del', '=', 0];
        $disUser = $this->lifeToolsDistributionUserModel->where($condition)->find();
        if(!$disUser){
            throw new \think\Exception('分销员不存在！');
        }

        $output = [];
        //总收益
        $output['total_revenue'] = $disUser->total_commission;
        //结算中金额
        $settlement_money = $this->lifeToolsDistributionOrderStatementModel->field('(SUM(`total_money`)-SUM(`reject_money`)) AS num')->where('user_id', $disUser->user_id)->where('statement_status', 0)->find();
        $output['settlement_money'] = $settlement_money['num'] ?: 0;
        //邀请人数
        $output['invite_num'] = $this->lifeToolsDistributionUserModel->getSubData($disUser->user_id, 3) + $this->lifeToolsDistributionOrderModel->lowerLevelData($disUser->user_id, 2);
        //订单数量
        $output['order_num'] = $this->lifeToolsDistributionOrderModel->lowerLevelData($disUser->user_id, 3);
        //驳回
        $output['reject_money'] = $disUser->rejected_money;
        //总抽成 = 驳回 + 收益
        $output['total_money'] = $output['reject_money'] + $output['total_revenue'];

        //分销员
        if($params['is_cert'] == 1){

            $condition = [];
            $condition[] = ['pid', '=', $disUser->user_id];
            $condition[] = ['is_del', '=', 0];
            $condition[] = ['is_cert', '=', 1];
            $with = [];
            $with['user'] = function($query){
                $query->field(['uid', 'nickname', 'avatar'])->withAttr('avatar', function($value, $data){
                    $avatar = replace_file_domain($value);
                    return $avatar ?: cfg('site_url') . '/static/images/user_avatar.jpg';
                })->bind(['nickname', 'avatar']);
            };
            $disUserList = $this->lifeToolsDistributionUserModel
                        ->field(['uid', 'user_id', 'is_cert'])
                        ->with($with)
                        ->where($condition)
                        ->paginate(10)
                        ->each(function($item, $key){
                            //下单数量，推广订单数量
                            $item->order_num = $item->expand_order = $this->lifeToolsDistributionLogModel->getOrderDataByUserId($item->user_id);
                            //订单总金额
                            $item->order_price = $this->lifeToolsDistributionLogModel->getOrderDataByUserId($item->user_id, 2);
                            //总订单数
                            $item->total_order_num = $this->lifeToolsDistributionLogModel->getOrderDataByUserId($item->user_id, 3);
                            //下级推广数量
                            $item->sub_expand_order = $this->lifeToolsDistributionUserModel->getSubData($item->user_id);
                            //下级推广奖励
                            $item->Invite_rewards = $this->lifeToolsDistributionUserModel->getSubData($item->user_id, 2);
                            //下级推广佣金
                            $item->commission = $this->lifeToolsDistributionLogModel->where('user_id', $item->user_id)->where('status', 2)->sum('commission_level_1');
                            $item->is_cert = 1;
                        })->toArray();
        $output['expand_data'] = $disUserList['data'];
        }else{

            $result = $this->lifeToolsDistributionOrderModel->distributionSettlementList($disUser->user_id);
            $expand_data = $result['data'];
            unset($result['data']);

            foreach ($expand_data as $key => $val) {
                $expand_data[$key]['commission'] = $val['order_price']; //我的佣金
                $expand_data[$key]['expand_order'] = $val['order_num']; //推广订单
                $expand_data[$key]['avatar'] = replace_file_domain($val['avatar']) ?: cfg('site_url') . '/static/images/user_avatar.jpg';

                $expand_data[$key]['sub_expand_order'] = $this->lifeToolsDistributionOrderModel->lowerLevelData($disUser->user_id, $val['from_user_id'], 1);
                $expand_data[$key]['Invite_rewards'] = $this->lifeToolsDistributionOrderModel->lowerLevelData($disUser->user_id, $val['from_user_id']);
                $expand_data[$key]['is_cert'] = 0;
            }
            $output['expand_data'] = $expand_data;
        }

        

        return $output;
    } 

    /**
     * 分销中心
     */
    public function distributionCenter($uid)
    {
      
        $condition = [];
        $condition[] = ['uid', '=', $uid];
        $condition[] = ['is_del', '=', 0];
        $disUser = $this->lifeToolsDistributionUserModel->where($condition)->find();
        if(!$disUser){
            throw new \think\Exception('分销员不存在！');
        }


        $condition = [];
        $condition[] = ['pid', '=', $disUser->user_id];
        $condition[] = ['is_del', '=', 0];
        $with = [];
        $with['user'] = function($query){
            $query->field(['uid', 'nickname', 'avatar'])->withAttr('avatar', function($value, $data){
                $avatar = replace_file_domain($value);
                return $avatar ?: cfg('site_url') . '/static/images/user_avatar.jpg';
            })->bind(['nickname', 'avatar']);
        };
        $disUserList = $this->lifeToolsDistributionUserModel
                        ->field(['uid', 'user_id', 'is_cert'])
                        ->with($with)
                        ->where($condition)
                        ->paginate(10)
                        ->each(function($item, $key){
                            //下单数量，推广订单数量
                            $item->order_num = $item->expand_order = $this->lifeToolsDistributionLogModel->getOrderDataByUserId($item->user_id);
                            //订单总金额
                            $item->order_price = $this->lifeToolsDistributionLogModel->getOrderDataByUserId($item->user_id, 2);
                            //总订单数
                            $item->total_order_num = $this->lifeToolsDistributionLogModel->getOrderDataByUserId($item->user_id, 3);
                            //下级推广数量
                            $item->sub_expand_order = $this->lifeToolsDistributionUserModel->getSubData($item->user_id);
                            //下级推广奖励
                            $item->Invite_rewards = $this->lifeToolsDistributionUserModel->getSubData($item->user_id, 2);
                            //下级推广佣金
                            $item->commission = $this->lifeToolsDistributionLogModel->where('user_id', $item->user_id)->where('status', 2)->sum('commission_level_1');
                        })->toArray();
 
        //总收益 = 总抽成 - 驳回金额
        $disUserList['total_revenue'] = $disUser->total_commission;
        $settlement_money = $this->lifeToolsDistributionOrderModel->field('(SUM(`commission_level_1`)+SUM(`commission_level_2`)) AS num')->where('user_id', $disUser->user_id)->where('status', 1)->find();
        //结算中金额
        $disUserList['settlement_money'] = $settlement_money['num'] ?: 0;
        //邀请人数
        $disUserList['invite_num'] = $disUser->invit_num;
        //订单人数
        $disUserList['order_num'] = (int)$this->lifeToolsDistributionUserModel->getSubData($disUser->user_id);
        //驳回金额
        $disUserList['rejected_money'] = $disUser->rejected_money;
        //总抽成 = 驳回 + 收益
        $disUserList['commission_money'] = formatNumber($disUserList['total_revenue'] + $disUserList['rejected_money']);
        
        foreach ($disUserList['data'] as $key => $value) {
            if($value['is_cert'] == 0 && $value['total_order_num'] == 0){
                unset($disUserList['data'][$key]);
            }
        }
        $disUserList['expand_data'] = array_values($disUserList['data']);

        $condition = [];
        $condition[] = ['u.pid', '=', $disUser->user_id];
        $condition[] = ['u.is_del', '=', 0];
        $condition[] = ['u.is_cert', '=', 0];
        $disUserList['invite_num'] += $this->lifeToolsDistributionUserModel->alias('u')->join('life_tools_distribution_log o', 'u.user_id = o.user_id')->where($condition)->count();

        //查询用户所有确定的结算单
        $data = $this->lifeToolsDistributionOrderStatementModel->getSome(['statement_status'=>1,'user_id'=>$disUser->user_id],'total_money,reject_money');
        $disUserList['total_money'] = 0;//结算已确定总金额
        $disUserList['reject_money'] = 0;//结算已确定驳回金额
        foreach ($data as $v){
            $disUserList['total_money'] += $v['total_money'];
            $disUserList['reject_money'] += $v['reject_money'];
        }
        unset($disUserList['data']);
        return $disUserList;
    }

    /**
     * 分销员列表导出
     * @return array
     */
    public function exportLowerLevelList($randNumber,$data)
    {
        $csvHead = array(
            L_('昵称'),
            L_('手机号'),
            L_('佣金'),
            L_('邀请奖励'),
            L_('邀请人数')
        );

        $csvData = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $csvData[$key] = [
                    $value['nickname'],
                    $value['phone'],
                    $value['commission'],
                    $value['invit_money'],
                    $value['invit_num']
                ];
            }
        }
        $filename = date("Y-m-d", time()) . '-' . $randNumber . '.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);

        $downFileUrl = cfg('site_url').'/v20/runtime/' . $filename;
        return ['file_url' => $downFileUrl];
    }

    /**
     * 删除分销员
     */
    public function delDistributor($params)
    {
        if (!$params['mer_id'] || !$params['user_id']) {
            throw new \think\Exception('参数缺失！');
        }
        
        $user = $this->lifeToolsDistributionUserModel->where('user_id', $params['user_id'])->find();

        $del = $this->lifeToolsDistributionUserBindMerchantModel->updateThis(['user_id'=>$params['user_id'],'mer_id'=>$params['mer_id']],['is_del'=>1]);
        if (!$del){
            throw new \think\Exception('删除失败！');
        }

         //计算上级邀请人数
         $inviteDistributionUser = $this->lifeToolsDistributionUserModel->where('user_id', $user->pid)->find();
         if($inviteDistributionUser && $inviteDistributionUser->is_del == 0){
             $condition = [];
             $condition[] = ['pid', '=', $inviteDistributionUser->user_id];
             $condition[] = ['is_cert', '=', 1];
             $condition[] = ['is_del', '=', 0];
             $inviteDistributionUser->invit_num = $this->lifeToolsDistributionUserModel->where($condition)->count();
             $inviteDistributionUser->update_time = time();
             $inviteDistributionUser->save();
         }
         
        return ['msg'=>'操作成功！'];
    }

    /**
     * 分销中心-查看详情
     */
    public function distributionCenterDeail($params){
        $page = $params['page']??0;
        $pagesize = $params['page_size']??10;
        $where = [];
        $where[] = ['a.uid','=',$params['uid']];
        $where[] = ['b.audit_status','in',[1,2]];
        $user_merchants = $this->lifeToolsDistributionUserModel->getUserMerchant($where,'',true,$page,$pagesize);
        foreach ($user_merchants as $key=>$item){
            $user_merchants[$key]['logo'] = replace_file_domain($item['logo']);  //处理图片
            $user_merchants[$key]['audit_status_text'] = $item['audit_status'] ? ($item['audit_status'] == 1 ? '已认证' : '未通过') : '审核中';
            //获取景区
            $tools = (new LifeToolsService())->getLifeToolsList(['mer_id'=>$item['mer_id'],'page_size'=>50,'tools_type'=>'scenic']);
            $tools_arr = [];
            foreach ($tools as $val){
                $tools_arr[] = $val['title'];
            }
            $user_merchants[$key]['scenic'] = $tools_arr;
            $user_merchants[$key]['commission'] = $item['commission'] + $item['invit_money'] - $item['rejected_money'];
        }
        return $user_merchants;
    }

    /**
     * 分销中心-推广订单
     */
    public function distributionCenterOrder($params){
        $page = $params['page']??0;
        $pagesize = $params['page_size']??10;
        $status = $params['status']??0;
        if(!in_array($status,array(0,10,20,30,40,50))){
            throw new \think\Exception('参数错误！');
        }
        $status_name = array(10=>'待付款',20=>'待核销',30=>'已核销',40=>'已完成',50=>'已退款');
        $status_color = array(10=>'#FF4D32',20=>'#298AFA',30=>'#FFB932',40=>'#4FBF64',50=>'#999999');
        //查询当前用户是否为分销员
        $user_info = $this->lifeToolsDistributionUserModel->where(['uid'=>$params['uid']])->find();
        if(!$user_info){
            throw new \think\Exception('您不是分销员，无法查看！');
        }
        //校验核销状态是否切换成已完成状态
        //获取分销员所属商家
        $merchant_arr = (new LifeToolsDistributionUserBindMerchant())->where(['uid'=>$params['uid']])->select();
        $merchant_ids = [];
        foreach ($merchant_arr as $item){
            $merchant_ids[] = $item['mer_id'];
        }
        if($merchant_ids){
            //获取各商家的订单核销状态配置
            $mer_set = (new LifeToolsDistributionSetting())->field('update_status_time,mer_id')->where([['mer_id','in',$merchant_ids]])->select();
            $mer_set_arr = [];
            foreach ($mer_set as $val){
                $mer_set_arr[$val['mer_id']] = $val['update_status_time'];
            }
            //查询已核销的订单
            $order_hx = $this->lifeToolsDistributionUserModel->getUserOrder(['d.order_status'=>30],'d.*','b.create_time desc',0,$pagesize,'a.uid = '.$params['uid'].' or a.pid = '.$user_info['user_id']);
            foreach ($order_hx as $vv){
                if(isset($mer_set_arr[$vv['mer_id']])){
                    if(time()-$vv['verify_time']>=$mer_set_arr[$vv['mer_id']]*86400){
                        (new LifeToolsOrder())->updateThis(['order_id'=>$vv['order_id']],['order_status'=>40]);
                    }
                }
            }
        }
        $where = [];
        if(in_array($status,[10,20,30,40,50])){
            $where = ['d.order_status'=>$status];
        }else{
            $where[] = ['d.order_status','in',[10,20,30,40,50]];
        }
        $user_orders = $this->lifeToolsDistributionUserModel->getUserOrder($where,'','b.create_time desc',$page,$pagesize,'a.uid = '.$params['uid'].' or a.pid = '.$user_info['user_id']);
        //获取当前用户订单佣金
        $commission = (new LifeToolsDistributionOrder())->where(['user_id' => $user_info['user_id']])->select();
        $commission_arr = [];
        foreach ($commission as $item){
            $commission_arr[$item['order_id']] = 0;
            if($item['commission_level_1']>0){
                $commission_arr[$item['order_id']] = $item['commission_level_1'];
            }elseif($item['commission_level_2']>0){
                $commission_arr[$item['order_id']] = $item['commission_level_2'];
            }
        }
        foreach ($user_orders as $k=>$v){
            $user_orders[$k]['cover_image'] = replace_file_domain($v['cover_image']);
            $user_orders[$k]['commission'] = $commission_arr[$v['order_id']]??0;
            $user_orders[$k]['status_name'] = isset($status_name[$v['order_status']])?$status_name[$v['order_status']]:'';
            $user_orders[$k]['status_color'] = isset($status_color[$v['order_status']])?$status_color[$v['order_status']]:'';
            unset($user_orders[$k]['commission_level_1']);
            unset($user_orders[$k]['commission_level_2']);
        }
        return $user_orders;
    }

    /**
     * 分销中心-推广订单tab列表
     * @return array[]
     */
    public function distributionCenterOrderTab(){
        $data = [
            array(
                'name' => '全部',
                'value' => 0,
            ),
            array(
                'name' => '待付款',
                'value' => 10,
            ),
            array(
                'name' => '待核销',
                'value' => 20,
            ),
            array(
                'name' => '已核销',
                'value' => 30,
            ),
            array(
                'name' => '已完成',
                'value' => 40,
            ),
            array(
                'name' => '已退款',
                'value' => 50,
            ),
        ];
        return $data;
    }

    /**
     * 验证分销员
     * @param int $uid用户uid，pigcms_user表uid
     * @return object
     */
    public function checkUser($uid)
    {
        $condition = [];
        $condition[] = ['uid', '=', $uid];
        $condition[] = ['status', '=', 1];
        $condition[] = ['is_del', '=', 0];
        $user = $this->lifeToolsDistributionUserModel->where($condition)->find();
        if(!$user){
            throw new \think\Exception('分销员不存在！');
        }
        return $user;
    }


    /**
     * 分享景区列表
     */
    public function shareScenicList($params)
    {
        $user = $this->checkUser($params['uid']);
        $condition = [];
        $condition[] = ['uid', '=', $params['uid']];
        $condition[] = ['user_id', '=', $user->user_id];
        $condition[] = ['audit_status', '=', 1];
        $condition[] = ['is_del', '=', 0];
        //绑定的商家
        $mer_ids = $this->lifeToolsDistributionUserBindMerchantModel->where($condition)->column('mer_id');
        
        //配置过抽成的门票
        $ticketSet = $this->lifeToolsTicketDistributionModel->select();
        $ticketCommission = $ticket_ids = [];

        //保存门票对应抽成信息
        foreach ($ticketSet as $key => $value) {
            $ticketCommission[$value->ticket_id]['commission_level_1'] = $value->secondary_commission;
            $ticketCommission[$value->ticket_id]['commission_level_2'] = $value->third_commission;
            $ticket_ids[] = $value->ticket_id;
        }
        
        $condition = [];
        $condition[] = ['mer_id', 'in', $mer_ids];
        $condition[] = ['type', '=', 'scenic'];
        $condition[] = ['is_del', '=', 0];
        //搜索
        if(!empty($params['keywords'])){
            $condition[] = ['title', 'like', "%{$params['keywords']}%"];
        }
        //筛选分类
        if(!empty($params['cat_id'])){
            $condition[] = ['cat_id', '=', $params['cat_id']];
        }
        //关联模型
        $with = [];
        $with['tickets'] = function($query) use($ticket_ids){
            $query->field(['tools_id', 'ticket_id', 'title', 'label', 'old_price', 'price'])->where('ticket_id', 'in', $ticket_ids)->where('is_del', 0);
        };
        $toolsList = $this->lifeToolsModel
                    ->field(['tools_id', 'cat_id', 'title', 'time_txt', 'cover_image', 'address', 'phone', 'long', 'lat'])
                    ->with($with)
                    ->where($condition)
                    ->paginate($params['page_size'])
                    ->each(function($item, $key) use($ticketCommission){
                        $item->cover_image = replace_file_domain($item->cover_image);
                        $item->tickets->each(function($item, $key) use($ticketCommission){
                            $item->commission_level_1 = $ticketCommission[$item->ticket_id]['commission_level_1'];
                            $item->commission_level_2 = $ticketCommission[$item->ticket_id]['commission_level_2'];
                            if($item->label){
                                $item->label =  explode(' ', $item->label);
                            }else{
                                $item->label = [];
                            }
                        });
                    })->toArray();

        foreach ($toolsList['data'] as $key => $item) {
            if(count($item['tickets']) == 0){
                unset($toolsList['data'][$key]);
            }
        }
        $toolsList['data'] = array_values($toolsList['data']);
        return $toolsList;
    }


    /**
     * 我的认证
     */
    public function myAuthentication($params)
    {
        $pageSize = $params['page_size'] ?? 10;
        $where = [
            ['a.uid','=',$params['uid']],
            ['a.is_del','=',0]
        ];
        $field = 'a.pigcms_id,a.mer_id,b.name,b.logo,b.phone,a.audit_status,a.audit_msg,a.update_time as audit_time';
        $order = 'a.pigcms_id desc';
        $data = $this->lifeToolsDistributionUserBindMerchantModel->myAuthentication($where,$field,$order,$pageSize);
        if($data['data']){
            $merIdAry = [];
            foreach ($data['data'] as &$v){
                $v['audit_status_text'] = $v['audit_status'] ? ($v['audit_status'] == 1 ? '已认证' : '未通过') : '审核中';
                $v['audit_time'] = $v['audit_time'] ? date('Y-m-d H:i:s',$v['audit_time']) : '';
                //获取商家旗下的景区
                $merIdAry[] = $v['mer_id'];
            }

            $toolWhere = [
                'is_del' => 0,
                'status' => 1,
                'type' => 'scenic'
            ];
            $scenicList = $this->lifeToolsModel->getListByMerchant($toolWhere,$merIdAry,$toolField = 'mer_id,title',$order='sort desc');
            $scenicInfoAry = [];
            foreach ($scenicList as $scenic){
                $scenicInfoAry[$scenic['mer_id']][] = $scenic['title'];
            }
            foreach ($data['data'] as &$v){
                $v['logo'] = replace_file_domain($v['logo']);
                $v['scenic'] = $scenicInfoAry[$v['mer_id']]??[];
                unset($v['mer_id']);
            }
        }
        return $data;
    }
}
