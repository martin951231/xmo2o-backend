<?php
/**
 * 金融产品申请api接口
 * Author: hengtingmei
 * Date Time: 2022/01/06
 */

namespace app\banking\controller\api;

use app\banking\model\service\BankingApplyService;
use app\banking\model\service\BankingService;

class ApplyController extends ApiBaseController
{

    public function initialize()
    {
        parent::initialize();
       
        if(empty($this->_uid)){
            throw new \think\Exception('未登录', 1002);
        }
    }
   
    /**
     * 获得列表
     */
    public function list()
    {
        
        $param['page'] = $this->request->param("page", "1", "intval");
        // 每页显示数量
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['type'] = $this->request->param("type", "", "trim");
        $param['uid'] = $this->_uid;

        $param['page'] =  $param['page'] ?: 1;
        $param['pageSize'] =  $param['pageSize'] ?: 10;
        // 获得列表
        $list = (new BankingApplyService())->getList($param);
        return api_output(0, $list);
    }  

    /**
     * 获得编辑所需数据
     */
    public function detail()
    {
        $param['apply_id'] = $this->request->param("apply_id", "", "intval");

        if(empty($this->_uid)){
            throw new \think\Exception('未登录', 1002);
        }
        $detail = (new BankingApplyService())->getDetail($param, $this->userInfo);
        return api_output(0, $detail);
    }    
    
    /**
     * 保存搜索发现列表
     */
    public function saveApply()
    {
        $param['banking_id'] = $this->request->param("banking_id", "0", "intval");
        $param['village_id'] = $this->request->param("village_id", "0", "intval");
        $param['name'] = $this->request->param("name", "", "trim");
        $param['phone'] = $this->request->param("phone", "", "trim");
        $param['address'] = $this->request->param("address", "", "trim");
        $param['money'] = $this->request->param("money", "", "trim");
        $param['apply_id'] = $this->request->param("apply_id", "0", "intval");
        $param['is_public_deposit'] = $this->request->param("is_public_deposit", "0", "intval");
        $param['company_name'] = $this->request->param("company_name", "", "trim");
        $param['id_number'] = $this->request->param("id_number", "", "trim");
        $param['industry'] = $this->request->param("industry", "", "trim");
        $param['loans_method'] = $this->request->param("loans_method", "", "trim");
        $param['loans_repayment_method'] = $this->request->param("loans_repayment_method", "", "trim");
        $res = (new BankingApplyService())->saveApply($param, $this->userInfo);
        return api_output(0, $res);
    }

    /**
     * 保存搜索发现列表
     */
    public function delApply()
    {
        $param['apply_id'] = $this->request->param("apply_id", "", "intval");

        if(empty($this->_uid)){
            throw new \think\Exception('未登录', 1002);
        }
        $res = (new BankingApplyService())->delApply($param, $this->userInfo);
        return api_output(0, $res);
    }

    /**
     * 用户撤销申请
     */
    public function repealApply()
    {
        $param['apply_id'] = $this->request->param("apply_id", "0", "intval");
        $res = (new BankingApplyService())->repealApply($param, $this->userInfo);
        return api_output(0, $res);
    }
}
