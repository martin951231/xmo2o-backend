<?php
/**
 * 金融产品service
 * Author: hengtingmei
 * Date Time: 2022/01/06 13:40
 */

namespace app\banking\model\service;

use app\banking\model\db\Banking;
use app\banking\model\db\BankingInformation;
use think\facade\Db;

class BankingService {
    public $bankingModel = null;
    public $loansTypeArr = null;
    public $typeArr = null;
    public $depositTermTypeArr = null;
    public $BankingInformation = null;
    public function __construct()
    {
        $this->bankingModel = new Banking();
        $this->BankingInformation = new BankingInformation();

        $this->loansTypeArr = [
            1 => '个人贷',
            2 => '企业贷',
        ];
        $this->depositTermTypeArr = [
            'year' => '年',
            'month' => '个月',
        ];
        $this->typeArr = [
            'loans' => '贷款',
            'credit_card' => '信用卡',
            'ecard' => 'E支付',
            'deposit' => '存款',
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
        
        // 排序
        $order = [
            'banking_id' => 'DESC',
        ];

        // 服务类型
        if(isset($where['type']) && $where['type']){
            $condition[] = ['type', '=', $where['type']];
        }

        // 关键词搜索
        if(isset($where['keywords']) && $where['keywords'] ){
            if(isset($where['search_type']) && $where['search_type']){
                switch($where['search_type']){
                    case 'title': // 贷款名称
                        $condition[] = ['title', 'like', '%'.$where['keywords'].'%'];
                        break;
                    case 'release_people': // 发布人
                        $condition[] = ['release_people', 'like', '%'.$where['keywords'].'%'];
                        break;
                }
            }else{
                $condition[] = ['title', 'like', '%'.$where['keywords'].'%'];
            }
        }

        // 搜索状态
        if(isset($where['status']) && $where['status'] != '-1'){
            $condition[] = ['status', '=', $where['status']];
        }

        // 搜索贷款类型
        if(isset($where['loans_type']) && $where['loans_type']){
            $condition[] = ['loans_type', '=', $where['loans_type']];
        }

        $condition[] = ['is_del', '=', 0];
        // 列表
        $list = $this->getSomeAndPage($condition, true, $order, $page, $pageSize);
        
        if($list){
            foreach($list['data'] as &$_banking){
                $_banking['add_time_txt'] =  date('Y-m-d H:i:s', $_banking['add_time']);
                $_banking['loans_type_txt'] = $this->loansTypeArr[$_banking['loans_type']] ?? '';
                $_banking['deposit_term_type_txt'] = $this->depositTermTypeArr[$_banking['deposit_term_type']] ?? '';
                $_banking['cover_image'] = replace_file_domain($_banking['cover_image']);
                if($_banking['type'] == 'credit_card'){
                    $_banking['cover_image'] = thumb_img($_banking['cover_image'],500, 300);
                }else{
                    $_banking['cover_image'] = thumb_img($_banking['cover_image'],200, 200);
                }
                $_banking['label_arr'] =  explode(' ', $_banking['label']);
                $_banking['highest_amount'] = $_banking['loans_highest_amount'] = get_format_number($_banking['loans_highest_amount']);
                $_banking['deposit_start_money'] = get_format_number($_banking['deposit_start_money']);
            }
        }

        return $list;
    }

    /**
     * 获得详情
     * @param $param
     * @return array
     */
    public function getDetail($param = [])
    {
        
        $bankingId = $param['banking_id'] ?? 0;// 产品ID
        if(empty($bankingId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        // 查询数据
        $where = [
            'banking_id' => $bankingId,
            'is_del' => 0
        ];
        $detail = $this->getOne($where);
        if(empty($detail)){
            throw new \think\Exception(L_('数据不存在或已删除'), 1001);
        }

        $detail['cover_image'] = replace_file_domain($detail['cover_image']);
        if($detail['images']){
            $detail['images'] = explode(',', $detail['images']);
            $detail['images'] = array_map('replace_file_domain',$detail['images']);
        }
        $detail['add_time_txt'] =  date('Y-m-d H:i:s', $detail['add_time']);

        $detail['label_arr'] =  $detail['label'] ? explode(' ', $detail['label']) : [];

        switch($detail['type']){
            case 'loans':
                $detail['loans_type_txt'] = $this->loansTypeArr[$detail['loans_type']] ?? '';
                $detail['loans_method'] = $detail['loans_method'] ? explode(',', $detail['loans_method']) : [];
                $detail['loans_repayment_method'] = $detail['loans_repayment_method'] ? explode(',', $detail['loans_repayment_method']) : [];
                break;
            case 'ecard':
                $detail['ecard_need_industry'] = config_data('ecard_need_industry');
                break;
            case 'deposit':
                $detail['deposit_need_input_money'] = config_data('deposit_need_input_money');
                $detail['deposit_term_type_txt'] = $this->depositTermTypeArr[$detail['deposit_term_type']] ?? '';
                break;
            case 'credit_card':// 信用卡
                // 查询滑动数据
                // 右滑
                $where = [
                    ['status', '=', 1],
                    ['is_del', '=', 0],
                    ['type', '=', 'credit_card'],
                    ['banking_id', '>', $detail['banking_id']],
                ];
                $list = $this->getSomeAndPage($where, 'banking_id,type,cover_image', ['banking_id'=>'asc'], 1, 3);
                $detail['pre_list'] = $list ? $list['data'] : [];
                foreach($detail['pre_list'] as $key => $value){
                    $detail['pre_list'][$key]['cover_image'] = replace_file_domain($value['cover_image']);
                }

                // 左滑
                $where = [
                    ['status', '=', 1],
                    ['is_del', '=', 0],
                    ['type', '=', 'credit_card'],
                    ['banking_id', '<', $detail['banking_id']],
                ];
                $list = $this->getSomeAndPage($where, 'banking_id,type,cover_image', ['banking_id'=>'desc'], 1, 3);
                $detail['next_list'] = $list ? $list['data'] : [];
                foreach($detail['next_list'] as $key => $value){
                    $detail['next_list'][$key]['cover_image'] = replace_file_domain($value['cover_image']);
                }

                break;
        }
        $detail['loans_highest_amount'] = get_format_number($detail['loans_highest_amount']);
        $detail['deposit_start_money'] = get_format_number($detail['deposit_start_money']);

        if(isset($param['from']) && $param['from'] == 'user'){// 统计点击量
            $this->setIncByField($where, 'view_count', 1);
        }        
        return $detail;
    }

    /**
     * 添加编辑
     * @param $param
     * @return array
     */
    public function saveBanking($param = [], $systemUser = [])
    {        
        $bankingId = $param['banking_id'] ?? 0;// 产品ID
        $editPeople = $param['edit_people'] ?? '';// 修改人
        $data['type'] = $param['type'] ?? '';// 类型：loans-贷款，credit_card-信用卡，ecark-E卡，deposit-存款
        $data['title'] = $param['title'] ?? '';// 标题
        $data['label'] = $param['label'] ?? '';// 发布人
        $data['phone'] = $param['phone'] ?? '';// 联系电话
        $data['release_people'] = $param['release_people'] ?? '';// 发布人
        $data['cover_image'] = $param['cover_image'] ?? '';// 封面图
        $data['images'] = $param['images'] ?? '';// 详情图
        // $data['sort'] = $param['sort'] ?? 0;// 排序值
        $data['for_customer'] = $param['for_customer'] ?? '';// 适用客户
        $data['loans_type'] = $param['loans_type'] ?? '1';// 贷款类型：1-个人贷，2-企业贷
        $data['loans_time_limit'] = $param['loans_time_limit'] ?? '';// 贷款期限
        $data['loans_interest_rate'] = $param['loans_interest_rate'] ?? ''; // 贷款利率
        $data['loans_highest_amount'] = $param['loans_highest_amount'] ?: '0.00'; // 贷款最高额度
        $data['credit_card_equities'] = $param['credit_card_equities'] ?: ''; // 信用卡权益
        $data['deposit_start_money'] = $param['deposit_start_money'] ?: '0.00'; // 存款起始金额
        $data['deposit_interest_rate'] = $param['deposit_interest_rate'] ?: '0.00'; //存款年利率 
        $data['deposit_term'] = $param['deposit_term'] ?? '0';//存款存期
        $data['deposit_term_type'] = $param['deposit_term_type'] ?? '';//存款存期类型
        $data['introduce'] = $param['introduce'] ?? '';//简介
        $data['status'] = $param['status'] ?? '1';
        $data['loans_method'] = $param['loans_method'] ?? [];//贷款方式
        $data['loans_repayment_method'] = $param['loans_repayment_method'] ?? [];//还款方式
        // $data['loans_method'] = implode(',', $data['loans_method']);
        // $data['loans_repayment_method'] = implode(',', $data['loans_repayment_method']);

        if($data['type'] != 'deposit' && empty($data['title'])){
            throw new \think\Exception(L_('请输入产品名称'), 1001);
        }

        $domain = file_domain();
        $data['cover_image'] = str_replace($domain, '', $data['cover_image']);
        if(is_array($data['images'])){
            $data['images'] = implode(',', $data['images']);
            $data['images'] = str_replace($domain, '', $data['images']);
        }else{
            $data['images'] = str_replace($domain, '', $data['images']);
        }

        $msg = L_('新增');
        if($bankingId){// 编辑
            $msg = L_('编辑');
            $where = [
                'banking_id' => $bankingId,
                'is_del' => 0
            ];
            $detail = $this->getOne($where);
            if(empty($detail)){
                throw new \think\Exception(L_('数据不存在'), 1001);
            }
            
            $res = $this->updateThis($where, $data);
        }else{
            $res = $this->add($data);
        }

        if($res === false){
            throw new \think\Exception($msg.L_('失败'), 1003);
        }
        
        if($bankingId){// 保存修改日志
            $dataLogAll = [];
            $add = 1;
            foreach($data as $key => $value){
                $detail['loans_highest_amount'] = get_format_number($detail['loans_highest_amount']);
                $detail['deposit_start_money'] = get_format_number($detail['deposit_start_money']);
                $detail['deposit_interest_rate'] = get_format_number($detail['deposit_interest_rate']);
                
                if(($data['type'] == 'credit_card') && $key=='images'){
                    continue;
                }     
                
                if((($key == 'deposit_term' || $key == 'deposit_term_type')) && $add==0){
                    continue;
                }
                if($detail[$key] != $value){
                    $dataLog = [
                        'banking_id' => $bankingId,
                        'edit_people' => $editPeople,
                        'admin_id' => $systemUser['id'] ?? 0,
                        'add_ip' => request()->ip(),
                        'add_time' => time(),
                        'type' => $key,
                        'pre_content' => $detail[$key],
                        'content' => $value,
                    ];
                    switch($key){
                        case 'title':
                            $dataLog['title'] = $this->typeArr[$data['type']].'名称';
                            break;
                        case 'label':
                            if($data['type'] == 'ecard'){
                                $dataLog['title'] = '功能优势';
                            }else{
                                $dataLog['title'] = '标签';
                            }
                            break;
                        case 'phone':
                            $dataLog['title'] = '联系电话';
                            break;
                        case 'release_people':
                            $dataLog['title'] = '发布人';
                            break;
                        case 'cover_image':
                            $dataLog['title'] = '封面图';
                            break;
                        case 'loans_type':
                            $dataLog['title'] = '贷款类型';
                            $dataLog['pre_content'] = $this->loansTypeArr[$dataLog['pre_content']] ?? '';
                            $dataLog['content'] = $this->loansTypeArr[$dataLog['content']] ?? '';
                            break;
                        case 'images':
                            $dataLog['title'] = '详情图';
                            break;
                        case 'for_customer':
                            $dataLog['title'] = '适用客户';
                            break;
                        case 'loans_time_limit':
                            $dataLog['title'] = '贷款期限';
                            break;
                        case 'loans_highest_amount':
                            $dataLog['title'] = '贷款最高额度';
                            $dataLog['pre_content'] = $dataLog['pre_content'] . '万';
                            $dataLog['content'] = $dataLog['content'] . '万';
                            break;
                        case 'credit_card_equities':
                            $dataLog['title'] = '信用卡权益';
                            break;
                        case 'deposit_start_money':
                            $dataLog['title'] = '存款起始金额';
                            break;
                        case 'deposit_interest_rate':
                            $dataLog['title'] = '存款年利率';
                            $dataLog['pre_content'] = $dataLog['pre_content'] . '%';
                            $dataLog['content'] = $dataLog['content'] . '%';
                            break;
                        case 'deposit_term':
                        case 'deposit_term_type':
                            $dataLog['type'] = 'deposit_term';
                            $dataLog['title'] = '存款存期类型';
                            $dataLog['pre_content'] = $detail['deposit_term'] .$this->depositTermTypeArr[$detail['deposit_term_type']] ?? '';
                            $dataLog['content'] = $data['deposit_term'] .$this->depositTermTypeArr[$data['deposit_term_type']] ?? '';
                            $add=0;
                            
                            break;
                        case 'introduce':
                            if($data['type'] == 'ecard'){
                                $dataLog['title'] = '业务简介';
                            }elseif(($data['type'] == 'credit_card')){
                                $dataLog['title'] = '信用卡简介';
                            }else{
                                $dataLog['title'] = '简介';
                            }
                            break;
                        case 'loans_interest_rate':
                            $dataLog['title'] = '贷款利率';
                            break;
                        case 'loans_method':
                            $dataLog['title'] = '贷款方式';
                            break;
                        case 'loans_repayment_method':
                            $dataLog['title'] = '还款方式';
                            break;
                    }
                    $dataLogAll[] = $dataLog;

                }
            }
            if($dataLogAll){
                (new BankingLogService())->addAll($dataLogAll);
            }
            
        }

        $returnArr['msg'] = $msg.L_('成功');
        return $returnArr;
    }

    /**
     * 删除
     * @param $param
     * @return array
     */
    public function delBanking($param = [])
    {
        $bankingId = $param['id'] ?? 0;// 产品ID
        if(empty($bankingId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        // 查询原来数据
        $where = [
            ['is_del', '=', 0]
        ];
        if(is_array($bankingId)){
            $where[] = ['banking_id' ,'in', implode(',', $bankingId)];
        }else{
            $where[] = ['banking_id' ,'=', $bankingId];
        }
       
        $detail = $this->getOne($where);
        if(empty($detail)){
            throw new \think\Exception(L_('数据不存在或已删除'), 1001);
        }

        // 假删除
        $res = $this->del($where);     
        if($res === false){
            throw new \think\Exception(L_('删除失败'), 1003);
        }

        $returnArr['msg'] = L_('删除成功');
        return $returnArr;
    }

    /**
     *获取资讯列表
     */
    public function getInformationList($param = []) {
        $where = [
            ['is_del', '=', 0]
        ];
        if (!empty($param['name'])) {
            $where[] = ['title', 'like', '%' . $param['name'] . '%'];
        }
        $limit = [
            'page' => $param['page'] ?? 1,
            'list_rows' => $param['pageSize'] ?? 10
        ];

        if(isset($param['from']) && $param['from'] == 'user'){
            $condition[] = ['show_type', 'exp', Db::raw('=1 OR (start_time<='. time() .' AND end_time>=' . time() . ')')];
        }

        $list = $this->BankingInformation->getList($where, $limit);
        if ($list['data']) {
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['image'] = replace_file_domain($v['image']);
                $list['data'][$k]['show_time'] = $v['show_type'] == 1 ? '永久显示' : date('Y/m/d', $v['start_time']) . ' ~ ' . date('Y/m/d', $v['end_time']);
                $list['data'][$k]['add_time_txt'] = $list['data'][$k]['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
            }
        }
        return $list;
    }

    /**
     * 删除资讯
     */
    public function delInformation($id = 0) {
        $this->BankingInformation->updateThis(['pigcms_id' => $id], ['is_del' => 1]);
        return true;
    }

    /**
     * 获取资讯详情
     */
    public function getInformationData($id = 0, $from = '') {
        if(empty($id)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $data = $this->BankingInformation->getOne(['pigcms_id' => $id])->toArray();
        $data['show_type']  = strval($data['show_type']);
        $data['image']      = replace_file_domain($data['image']);
        $data['content']      = replace_file_domain_content($data['content']);
        $data['start_time'] = !empty($data['start_time']) ? date('Y-m-d H:i:s',$data['start_time']) : null;
        $data['end_time']   = !empty($data['end_time']) ? date('Y-m-d H:i:s',$data['end_time']) : null;
        $data['add_time_txt'] = date('Y-m-d H:i:s', $data['add_time']);

        if($from == 'user'){
            $this->BankingInformation->where(['pigcms_id' => $id])->inc('view_count',1)->update();
        }
        return $data;
    }

    /**
     * 添加或编辑资讯
     */
    public function editOrAddInformation($param = []) {
        $arr = [
            'title'      => $param['title'] ?? '',
            'image'      => $param['image'] ?? '',
            'content'    => $param['content'] ?? '',
            'show_type'  => $param['show_type'] ?? 1,
            'start_time' => !empty($param['start_time']) ? strtotime($param['start_time']) : 0,
            'end_time'   => !empty($param['end_time']) ? strtotime($param['end_time']) : 0,
            'add_time'   => time(),
        ];
        if (!empty($param['pigcms_id'])) { //编辑
            $this->BankingInformation->updateThis(['pigcms_id' => $param['pigcms_id']], $arr);
        } else {
            $this->BankingInformation->add($arr);
        }
        return true;
    }

    /**
     *获取一条条数据
     * @param array $where 
     * @return array
     */
    public function getOne($where){
        $result = $this->bankingModel->getOne($where);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
    *获取多条条数据
    * @param array $where 
    * @return array
    */
    public function getSomeAndPage($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->bankingModel->getSomeAndPage($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    } 

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $data['add_time'] = time();
        $data['update_time'] = time();
        $id = $this->bankingModel->add($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 增加字段值
     * @param array $where
     * @param string $field
     * @param int $num
     * @return bool|int
     */
    public function setIncByField($where,$field = 'view_count', $num = 1){
        if (!$where) {
            return false;
        }

        $result = $this->bankingModel->where($where)->inc($field,$num)->update();
        return $result;
    }

    /**
     * 更新数据
     * @param array $where
     * @param string $field
     * @param int $num
     * @return bool|int
     */
    public function setDecByField($where,$field = 'sale_count', $num = 1){
        if (!$where) {
            return false;
        }

        $result = $this->bankingModel->where($where)->dec($field,$num)->update();
        return $result;
    }

    /**
     * 更新数据
     * @param array $where
     * @param array $data
     * @return bool|int
     */
    public function updateThis($where,$data){
        if (!$where || !$data) {
            return false;
        }

        $data['update_time'] = time();
        $result = $this->bankingModel->updateThis($where, $data);
        return $result;
    }
    
    /**
     * 删除数据
     * @param array $where  条件
     * @return bool
     */
    public function del($where){
        if(!$where){
            return false;
        }

        $result = $this->bankingModel->updateThis($where, ['is_del'=>1]);
        if(!$result) {
            return false;
        }
        return true;
    }
}