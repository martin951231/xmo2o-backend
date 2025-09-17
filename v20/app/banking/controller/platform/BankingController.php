<?php
/**
 * 金融产品控制器
 * Author: hengtingmei
 * Date Time: 2022/01/06 13:50
 */

namespace app\banking\controller\platform;

use app\banking\model\service\BankingService;
use app\banking\model\service\BankingLogService;
use app\common\model\service\ConfigDataService;

class BankingController extends AuthBaseController
{
    /**
     * 获得列表
     */
    public function getList()
    {
        $param['page'] = $this->request->param("page", "1", "intval");
        // 每页显示数量
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['type'] = $this->request->param("type", "", "trim");
        $param['keywords'] = $this->request->param("keywords", "", "trim");
        $param['search_type'] = $this->request->param("search_type", "", "trim");
        $param['loans_type'] = $this->request->param("loans_type", "1", "intval");

        // 获得列表
        $list = (new BankingService())->getList($param);
        return api_output(0, $list);
    }   

    /**
     * 获得编辑所需数据
     */
    public function getDetail()
    {
        $param['banking_id'] = $this->request->param("banking_id", "", "intval");

        $detail = (new BankingService())->getDetail($param);
        return api_output(0, $detail);
    }
    
    /**
     * 保存搜索发现列表
     */
    public function saveBanking()
    {
        $param['banking_id'] = $this->request->param("banking_id", "0", "intval");
        $param['type'] = $this->request->param("type", "", "trim");
        $param['title'] = $this->request->param("title", "", "trim");
        $param['label'] = $this->request->param("label", "", "trim");
        $param['introduce'] = $this->request->param("introduce", "", "trim");
        $param['phone'] = $this->request->param("phone", "", "trim");
        $param['release_people'] = $this->request->param("release_people", "", "trim");
        $param['edit_people'] = $this->request->param("edit_people", "", "trim");
        $param['cover_image'] = $this->request->param("cover_image", "", "trim");
        $param['images'] = $this->request->param("images", "", "trim");
        $param['for_customer'] = $this->request->param("for_customer", "", "trim");
        $param['loans_time_limit'] = $this->request->param("loans_time_limit", "", "trim");
        $param['loans_highest_amount'] = $this->request->param("loans_highest_amount", "", "trim");
        $param['loans_interest_rate'] = $this->request->param("loans_interest_rate", "", "trim");
        $param['credit_card_equities'] = $this->request->param("credit_card_equities", "", "trim");
        $param['deposit_start_money'] = $this->request->param("deposit_start_money", "", "trim");
        $param['deposit_interest_rate'] = $this->request->param("deposit_interest_rate", "", "trim");
        $param['deposit_term'] = $this->request->param("deposit_term", "", "trim");
        $param['deposit_term_type'] = $this->request->param("deposit_term_type", "", "trim");
        $param['loans_type'] = $this->request->param("loans_type", "1", "intval");
        $param['loans_method'] = $this->request->param("loans_method", "", "trim");
        $param['loans_repayment_method'] = $this->request->param("loans_repayment_method", "", "trim");
        $res = (new BankingService())->saveBanking($param, $this->systemUser);
        return api_output(0, $res);
    }
    
    /**
     * 删除
     */
    public function delBanking()
    {
        $param['id'] = $this->request->param("id");
        
        $res = (new BankingService())->delBanking($param);
        
        return api_output(0, $res);
    }

    /**
     * 获取配置
     */
    public function getConfigDataList() {
        try {
            $data = (new ConfigDataService())->getConfigData();
            $data['bank_download_qrcode'] = replace_file_domain($data['bank_download_qrcode']);
            $data['bank_wechat_qrcode']   = replace_file_domain($data['bank_wechat_qrcode']);
            $data['banking_user_agreement'] = replace_file_domain_content($data['banking_user_agreement']);
            return api_output(0, $data);
        } catch(\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取配置
     */
    public function editSeting() {
        $param = $this->request->param();
        try {
            $arr = [
                'credit_card_apply_times'  => $param['credit_card_apply_times'] ?? 0,
                'deposit_need_input_money' => $param['deposit_need_input_money'] ?? 0,
                'ecard_need_industry'      => $param['ecard_need_industry'] ?? 0,
                'bank_download_qrcode'     => $param['bank_download_qrcode'] ?? '',
                'bank_wechat_qrcode'       => $param['bank_wechat_qrcode'] ?? '',
                'banking_user_agreement'     => $param['banking_user_agreement'] ?? '',
                'banking_user_agreement_show' => $param['banking_user_agreement_show'] ?? 0,
            ];
            $data = (new ConfigDataService())->amend($arr);
            return api_output(0, $data);
        } catch(\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取资讯列表
     */
    public function getInformationList() {
        $param['name']     = $this->request->param("name", "", "trim");
        $param['page']     = $this->request->param("page", 1, "intval");
        $param['pageSize'] = $this->request->param("pageSize", 10, "intval");
        try {
            $data = (new BankingService())->getInformationList($param);
            return api_output(0, $data);
        } catch(\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除资讯
     */
    public function delInformation() {
        $id = $this->request->param("id", 0, "intval");
        if (empty($id)) {
            return api_output_error(1003, '参数缺失');
        }
        try {
            $data = (new BankingService())->delInformation($id);
            return api_output(0, $data);
        } catch(\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取资讯详情
     */
    public function getInformationData() {
        $id = $this->request->param("id", 0, "intval");
        if (empty($id)) {
            return api_output_error(1003, '参数缺失');
        }
        try {
            $data = (new BankingService())->getInformationData($id);
            return api_output(0, $data);
        } catch(\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加或编辑资讯
     */
    public function editOrAddInformation() {
        $param = $this->request->param();
        try {
            $data = (new BankingService())->editOrAddInformation($param);
            return api_output(0, $data);
        } catch(\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获得修改日志列表
     */
    public function getLogList()
    {
        $param['banking_id'] = $this->request->param("banking_id", "0", "intval");
        $param['page'] = $this->request->param("page", "1", "intval");
        // 每页显示数量
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['keywords'] = $this->request->param("keywords", "", "trim");
        $param['start_time'] = $this->request->param("start_time", "", "trim");
        $param['end_time'] = $this->request->param("end_time", "", "trim");

        // 获得列表
        $list = (new BankingLogService())->getList($param);
        return api_output(0, $list);
    }   

}
