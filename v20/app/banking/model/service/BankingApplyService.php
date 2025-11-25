<?php
/**
 * 金融产品修改记录service
 * Author: hengtingmei
 * Date Time: 2022/01/06
 */

namespace app\banking\model\service;

use app\banking\model\db\BankingApply;
use app\common\model\service\export\ExportService;
use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;

class BankingApplyService {
    public $bankingApplyModel = null;
    public $loansStatusArr = null;
    public $depositTermTypeArr = null;
    public $statusArr = null;
    public $typeArr = null;
    public function __construct()
    {
        $this->bankingApplyModel = new BankingApply();
        $this->depositTermTypeArr = [
            'year' => '年',
            'month' => '月',
        ];
        $this->loansStatusArr = [
            0 => '待受理',
            1 => '已受理',
            2 => '已放款',
            3 => '已拒绝',
            4 => '已撤销',
        ];
        $this->statusArr = [
            0 => '待受理',
            1 => '已受理',
            2 => '申请成功',
            3 => '已拒绝',
            4 => '已撤销',
        ];
        $this->typeArr = [
            'personal_loans' => '个人贷款',
            'company_loans' => '企业贷款',
            'credit_card' => '信用卡',
            'ecard' => 'E支付',
            'deposit' => '存款',
            'public_deposit' => '对公账户预约',
        ];
    }

    /**
     * 获得产品列表
     * @param $where
     * @return array
     */
    public function getList($where = [])
    {
        $pageSize = isset($where['pageSize']) ? $where['pageSize'] : 0;//每页显示数量
        $page = request()->param('page', '1', 'intval');//页码

        // 构造查询条件
        $condition = [];
        $conditionCount = [];
        
        // 排序
        $order = [
            'a.apply_id' => 'DESC',
        ];

        $conditionCount[] = $condition[] = ['a.is_del', '=', 0];
        
        if(isset($where['sort_type']) && $where['sort_type'] && isset($where['sort_name']) && $where['sort_name']){
            $order = [
                'a.'.$where['sort_name'] => $where['sort_type'],
                'a.apply_id' => 'DESC',
            ];
        }

        // 服务类型
        if(isset($where['type']) && $where['type']){
            $conditionCount[] = $condition[] = ['a.type', '=', $where['type']];
        }          
        
        // 用户id
        if(isset($where['uid']) && $where['uid']){
            $conditionCount[] = $condition[] = ['a.uid', '=', $where['uid']];
        }     
        
        // 状态
        if(isset($where['status']) && $where['status'] != '-1'){
            $condition[] = ['a.status', '=', $where['status']];
        }

        // 关键词搜索
        if(isset($where['keywords']) && $where['keywords'] ){
            if(isset($where['search_type']) && $where['search_type']){
                switch($where['search_type']){
                    case 'title': // 产品名称
                        $conditionCount[] = $condition[] = ['a.title', 'like', '%'.$where['keywords'].'%'];
                        break;
                    case 'name': // 姓名
                        $conditionCount[] =  $condition[] = ['a.name', 'like', '%'.$where['keywords'].'%'];
                        break;
                    case 'phone': // 手机号
                        $conditionCount[] = $condition[] = ['a.phone', 'like', '%'.$where['keywords'].'%'];
                        break;
                }
            }else{
                $conditionCount[] = $condition[] = ['a.title|a.name|a.phone', 'like', '%'.$where['keywords'].'%'];
            }
        }

        // 申请开始时间
        if(isset($where['start_time']) && $where['start_time']){
            $conditionCount[] = $condition[] = ['a.add_time', '>=', strtotime($where['start_time'])];
        }
        // 申请结束时间
        if(isset($where['end_time']) && $where['end_time']){
            $conditionCount[] = $condition[] = ['a.add_time', '<=', strtotime($where['end_time'])+86399];
        }

        // 小区名称
        if(isset($where['village_id']) && $where['village_id']){
            $conditionCount[] = $condition[] = ['h.village_id', '=', $where['village_id']];
        }
        // 列表
        $list = $this->getSomeAndPage($condition, 'a.*,h.village_name,b.label,b.cover_image,b.deposit_term_type,b.deposit_term,b.deposit_interest_rate,b.deposit_start_money', $order, $page, $pageSize);
        if($list && $list['data']){            
            foreach($list['data'] as &$_banking){
                // 时间
                $_banking['add_time_txt'] =  date('Y-m-d H:i:s', $_banking['add_time']);

                // 状态
                if(in_array($_banking['type'],['personal_loans','company_loans'])){
                    $_banking['status_arr'] =  $this->loansStatusArr;
                    $_banking['status_txt'] =  $this->loansStatusArr[$_banking['status']] ?? '';
                }else{
                    $_banking['status_arr'] =  $this->statusArr;
                    $_banking['status_txt'] =  $this->statusArr[$_banking['status']] ?? '';
                }
                // 类型
                $_banking['type_txt'] =  $this->typeArr[$_banking['type']] ?? '';

                $_banking['label_arr'] =  $_banking['label'] ? explode(',', $_banking['label']) : [];
                $_banking['cover_image'] = replace_file_domain($_banking['cover_image']);
                if($_banking['type'] == 'deposit'){
                    $_banking['deposit_term_type_txt'] =  $this->depositTermTypeArr[$_banking['deposit_term_type']] ?? '';
                    $_banking['deposit_start_money'] =  get_format_number($_banking['deposit_start_money']);
                    $_banking['deposit_interest_rate'] =  get_format_number($_banking['deposit_interest_rate']);
                }
                $_banking['money'] = get_format_number($_banking['money']);
            }
        }

        if($page == 1){
            // 统计
            $tabArr = [
                [
                    'value' => -1,
                    'name' => '全部',
                    'count' => $this->getCount($conditionCount),
                ]
            ];

            foreach($this->statusArr as $key => $status){
                $conditionCountNew = $conditionCount;
                $conditionCountNew[] = ['a.status', '=', $key];
                $tabArr[] = [
                    'value' => $key,
                    'name' => $status,
                    'count' => $this->getCount($conditionCountNew),
                ];
            }
            $list['tab_arr'] = $tabArr;
        }
        return $list;
    }
    
    /**
     * 添加编辑
     * @param $param
     * @return array
     */
    public function getDetail($param = [], $userInfo = [])
    {        
        $applyId = $param['apply_id'] ?? '';// 申请id
        if(empty($applyId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        
        // 用户
        if(empty($userInfo)){
            throw new \think\Exception(L_('未登录'), 1002);
        }

        // 产品信息
        $where = [
            'uid' => $userInfo['uid'],
            'apply_id' => $applyId,
            'is_del' => 0
        ];
        $detail = $this->getOne($where);
        if(empty($detail)){
            throw new \think\Exception(L_('数据不存在'), 1001);
        }  
        
        $detail['add_time_txt'] =  date('Y-m-d H:i:s', $detail['add_time']);
        $detail['money'] = get_format_number($detail['money']);

        if($detail['banking_id']){            
            // 产品信息
            $where = [
                'banking_id' => $detail['banking_id'],
                'is_del' => 0
            ];
            $banking = (new BankingService())->getOne($where);

            
            $detail['bank_phone'] =  $banking['phone'] ?? ''; 
            $detail['deposit_term'] =  $banking['deposit_term'] ?: '0'; 
            $detail['deposit_interest_rate'] =  $banking['deposit_interest_rate'] ? get_format_number($banking['deposit_interest_rate']) : '0'; 
            $detail['deposit_term_type_txt'] = $this->depositTermTypeArr[$banking['deposit_term_type']] ?? '';

            if($banking && $banking['images']){
                $detail['images'] = explode(',', $banking['images']);
                $detail['images'] = array_map('replace_file_domain',$detail['images']);
            }
        }
        return $detail;
    }

    
    /**
     * 删除
     * @param $param
     * @return array
     */
    public function delApply($param = [], $userInfo = [])
    {        
        $applyId = $param['apply_id'] ?? '';// 申请id
        if(empty($applyId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        
        // 用户
        if(empty($userInfo)){
            throw new \think\Exception(L_('未登录'), 1002);
        }

        // 产品信息
        $where = [
            'uid' => $userInfo['uid'],
            'apply_id' => $applyId,
            'is_del' => 0
        ];
        $detail = $this->getOne($where);
        if(empty($detail)){
            throw new \think\Exception(L_('数据不存在'), 1001);
        }

        if(!in_array($detail['status'],[2,3,4])){
            throw new \think\Exception(L_('该状态下不能删除'), 1003);
        }    
        $data = [
            'is_del' => 1
        ];

        $res = $this->updateThis($where, $data);
        if($res === false){
            throw new \think\Exception(L_('删除失败，请稍后重试'), 1003);
        }
        
        return ['msg' => '删除成功'];
    }

    /**
     * 用户提交申请
     * @param $param
     * @return array
     */
    public function saveApply($param = [], $userInfo = [])
    {        
        $applyId = $param['apply_id'] ?? '';// 申请id
        $isPublicDeposit = $param['is_public_deposit'] ?? '';// 是否是对公账号预约：是传1，其他情况传0或者不传
        $data['banking_id'] = $param['banking_id'] ?? 0;// 产品ID
        $data['village_id'] = $param['village_id'] ?? '';// 小区id
        $data['name'] = $param['name'] ?? '';// 申请人姓名
        $data['phone'] = $param['phone'] ?? '';// 联系电话
        $data['address'] = $param['address'] ?? '';// 申请人地址    
        $data['money'] = $param['money'] ?? '';// 申请额度
        $data['company_name'] = $param['company_name'] ?? '';// 条件必传 对公账号预约：企业名称
        $data['id_number'] = $param['id_number'] ?? '';// 条件必传 对公账号预约：身份证号
        $data['industry'] = $param['industry'] ?? '';// 条件必传 申请E支付：行业
        $data['loans_method'] = $param['loans_method'] ?? '';// 贷款方式
        $data['loans_repayment_method'] = $param['loans_repayment_method'] ?? '';// 贷款还款方式

        // 申请用户
        if(empty($userInfo)){
            throw new \think\Exception(L_('未登录'), 1002);
        }

        if(empty($data['village_id'])){
            throw new \think\Exception(L_('缺少社区id参数'), 1001);
        }

        if($applyId){// 重新提交
            $where = [
                'apply_id' => $applyId,
                'uid' => $userInfo['uid'],
                'is_del' => 0
            ];
            $detail = $this->getOne($where);
            if(empty($detail)){
                throw new \think\Exception(L_('申请不存在'), 1003);
            }

            if($detail['status'] != 3){
                throw new \think\Exception(L_('该状态下不能重新提交申请'), 1003);
            }
            $data['banking_id'] = $detail['banking_id'];
        }else{
            if(empty($data['banking_id']) && !$isPublicDeposit){
                throw new \think\Exception(L_('缺少金融产品id参数'), 1001);
            }
        }      
        
        if(empty($data['name'])){
            throw new \think\Exception(L_('请输入姓名'), 1003);
        }

        if(empty($data['phone'])){
            throw new \think\Exception(L_('请输入电话'), 1003);
        }       
        
        $data['uid'] = $userInfo['uid'];

        if($data['banking_id']){
            // 产品信息
            $where = [
                'banking_id' => $data['banking_id'],
                'is_del' => 0
            ];
            $bankingDetail = (new BankingService())->getOne($where);
            if(empty($bankingDetail)){
                throw new \think\Exception(L_('数据不存在'), 1003);
            }
            $data['title'] = $bankingDetail['title']; // 产品名称
        }

        // 预约类型
        $data['type'] = $bankingDetail['type'] ?? '';
        if($data['type'] == 'loans'){
            $data['type'] = $bankingDetail['loans_type'] == 1 ? 'personal_loans' : 'company_loans';
        }elseif($isPublicDeposit){// 对公账号预约
            $data['type'] = 'public_deposit';
            $data['title'] = '对公账户预约'; // 产品名称
        }

        // 检查信用卡申请次数
        if($data['type'] == 'credit_card'){
            if(config_data('credit_card_apply_times') <= 0){
                throw new \think\Exception(L_('当前信用卡不可申请'), 1003);
            }elseif(config_data('credit_card_apply_times') >= 0){
                $where = [
                    ['uid', '=', $userInfo['uid']],
                    ['banking_id', '=', $data['banking_id']],
                    ['type', '=', 'credit_card'],
                    ['is_del', '=', '0'],
                ];
                if($applyId){
                    $where[] = ['apply_id', '<>', $applyId];
                }
                $count = $this->getCount($where);
                if($count >= config_data('credit_card_apply_times')){
                    throw new \think\Exception(L_('信用卡申请次数已达上限'), 1003);
                }
            }
        }

        $data['status'] = 0;// 待审核
        if($applyId){// 编辑
            $where = [
                'apply_id' => $applyId,
                'uid' => $userInfo['uid'],
                'is_del' => 0
            ];
            $res = $this->updateThis($where, $data);
        }else{
            $res = $applyId = $this->add($data);
        }

        if($res === false){
            throw new \think\Exception(L_('申请失败'), 1003);
        }       

        $returnArr['msg'] = L_('申请成功');
        $returnArr['apply_id'] = $applyId;
        return $returnArr;
    }

    /**
     * 用户撤销申请
     * @param $param
     * @return array
     */
    public function repealApply($param = [], $userInfo = [])
    {        
        $applyId = $param['apply_id'] ?? '';// 申请id
        
        // 申请用户
        if(empty($userInfo)){
            throw new \think\Exception(L_('未登录'), 1002);
        }

        // 产品信息
        $where = [
            'apply_id' => $applyId,
            'uid' => $userInfo['uid'],
            'is_del' => 0
        ];
        $detail = $this->getOne($where); 
        if(empty($detail)){
            throw new \think\Exception(L_('数据不存在'), 1003);
        }
        
        if($detail['status'] != 0){
            throw new \think\Exception(L_('该状态下不能撤销申请'), 1003);
        }

        $data['status'] = 4;
        $res = $this->updateThis($where, $data);

        if($res === false){
            throw new \think\Exception(L_('撤销申请失败，请稍后再试'), 1003);
        }       

        $returnArr['msg'] = L_('撤销申请成功');
        return $returnArr;
    }

     /**
     * 修改状态
     * @param array $param
     * @param array $systemUser 管理员信息
     * @return array
     */
    public function changeStatus($param, $systemUser){
        $applyId = $param['apply_id'] ?? 0;
        $status = $param['status'] ?? 0;
        if(!isset($param['status']) || empty($applyId)){
            throw new \think\Exception('缺少参数', 1001);
        }

        // 获取申请详情
        $where = [
            'apply_id' => $applyId
        ];
        $applyDetail = $this->getOne($where);
        if(empty($applyDetail)){
            throw new \think\Exception('数据不存在', 1003);
        }

        if($applyDetail['status'] == 4){
            throw new \think\Exception('用户已撤销，不能再操作', 1003);
        }

        // 修改状态
        $data = [
            'status' => $status,
            'audit_time' => time(),
            'admin_id' => $systemUser['id'] ?? 0
        ];
        $res = $this->updateThis($where,$data);
        if($res === false){
            throw new \think\Exception('审核失败', 1003);
        }

        // 写入日志
        $logData = [
            'apply_id' => $applyId,
            'pre_status' => $applyDetail['status'],
            'status' => $status,
            'admin_id' => $systemUser['id'] ?? 0,
            'add_ip' => request()->ip(),
        ];
        (new BankingApplyLogService())->add($logData);

        $where = [
            'banking_id' => $applyDetail['banking_id']
        ];
        // 统计次数
        if($status == 2 && $applyDetail['status'] != 2){// 审核通过
            (new BankingService())->setIncByField($where, 'sale_count', 1);
        }elseif($status != 2 && $applyDetail['status'] == 2){
            (new BankingService())->setDecByField($where, 'sale_count', 1);
        }
        // 发送模板消息
        $userInfo = (new UserService())->getUser($applyDetail['uid']);
        if($userInfo['openid']){
            $status = $this->statusArr[$status];
            (new TemplateNewsService())->sendTempMsg('TM00017',
            array(
                // 'href' => $this->getDetailUrl($applyDetail['type']).'?apply_id='.$applyDetail['apply_id'].'&banking_id='.$applyDetail['banking_id'],
                'wecha_id' => $userInfo['openid'],
                'first' => L_('审核状态提醒'),
                'OrderSn' => $applyDetail['title'],
                'OrderStatus' => '您的申请'.$status,
                'remark' => '审核状态变更点击查看订单详情！'), 
                0
            );   
        }

        return ['msg' => '审核成功'];
    }

    public function getDetailUrl($type){
        switch($type){
            case 'company_loans':
            case 'personal_loans':
                $url = get_base_url().'pages/loanModule/pages/toExamine';
                break;
            case 'credit_card':
                $url = get_base_url().'pages/loanModule/pages/creditCardDetail';
                break;
            case 'ecard':
                $url = get_base_url().'pages/loanModule/pages/toExamine';
                break;
            case 'public_deposit':
                $url = get_base_url().'pages/loanModule/pages/accountDetail';
                break;
            case 'deposit':
                $url = get_base_url().'pages/loanModule/pages/depositDetail';
                break;
        }
        return $url;
    }
    
    /**
     *获取贷款首页信息
     */
    public function loansIndex($uid) {
        $returnArr['now_time'] = date('Y-m-d H:i:s');
        // 查询贷款数量
        $where = [
            ['a.uid', '=', $uid],
            ['a.is_del', '=', 0],
            ['a.type', 'in', 'company_loans,personal_loans']
        ];
        $returnArr['loans_count'] = $this->getCount($where);
        return $returnArr;
    }
    
	/**
     * 添加导出计划任务
     * @param $title string 标题
     * @param $param array 数据
     * $param = [
     *         'type',//导出业务唯一标识
     * ]
     * @return array
     */
    public function addExport($param){
        $title = '申请记录';
		$param['service_path'] = '\app\banking\model\service\BankingApplyService';
		$param['service_name'] = 'applyExport';
		$param['rand_number'] = time();
        $result = (new ExportService())->addExport($title, $param);
        return $result;
    }

    /**
     * 导出申请
     * @param $param array 数据
     * $param = [
     *         'type',//导出业务唯一标识
     * ]
     * @return array
     */
    public function applyExport($param){
        $param['pageSize'] = 0;
        $orderList = $this->getList($param);
        $orderList = $orderList['data'];
        $csvHead = array(
            // L_('申请ID'),
            L_('产品名称'),
            L_('产品类型'),
            L_('申请人名称'),
            L_('申请人手机号'),
            L_('企业名称'),
            L_('地址'),
            L_('申请人小区'),
            L_('存款金额'),
            L_('状态'),
            L_('申请时间')
        );

        $csvData = [];
        if (!empty($orderList)) {
            foreach ($orderList as $orderKey => $value) {
                $csvData[$orderKey] = [
                    // $value['apply_id'],
                    $value['title'],
                    $value['type_txt'],
                    $value['name'],
                    $value['phone'],
                    $value['company_name'],
                    $value['address'],
                    $value['village_name'],
                    $value['money'],
                    $value['status_txt'],
                    $value['add_time_txt'],
                ];
            }
        }
        
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.csv';
        (new ExportService())->putCsv($filename, $csvData, $csvHead);
    }

    /**
    *获取多条条数据
    * @param array $where 
    * @return array
    */
    public function getSomeAndPage($where = [], $field = 'a.*,h.village_name',$order=true,$page=0,$limit=0){
        $result = $this->bankingApplyModel->getSomeAndPage($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        $result = $result->toArray();
        if($limit == 0){
            $resultArr['data'] = $result;
            $result = $resultArr;
        }
        return $result;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $data['add_time'] = time();
        $id = $this->bankingApplyModel->add($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param array $where  条件
     * @param array $data  数据
     * @return int|bool
     */
    public function updateThis($where, $data){
        if(empty($where) || empty($data)){
            return false;
        }
        $data['update_time'] = time();
        $res = $this->bankingApplyModel->updateThis($where, $data);
        if(!$res) {
            return false;
        }

        return $res;
    }

    /**
     * 获取总数
     * @param array $where  条件
     * @return array
     */
    public function getCount($where){
        $count = $this->bankingApplyModel->getCount($where);
        if(!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * 获取一条数据
     * @param array $where  条件
     * @return object
     */
    public function getOne($where, $field=true){
        $res = $this->bankingApplyModel->getOne($where, $field);
        return $res;
    }
}