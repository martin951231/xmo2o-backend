<?php
/**
 * 金融产品api接口
 * Author: hengtingmei
 * Date Time: 2022/01/06
 */

namespace app\banking\controller\api;

use app\banking\model\service\BankingApplyService;
use app\banking\model\service\BankingService;

class BankingController extends ApiBaseController
{
   
    /**
     * 获得列表
     */
    public function list()
    {
        $param['page'] = $this->request->param("page", "1", "intval");
        // 每页显示数量
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['type'] = $this->request->param("type", "", "trim");
        $param['loans_type'] = $this->request->param("loans_type", "1", "intval");

        $param['page'] =  $param['page'] ?: 1;
        $param['pageSize'] =  $param['pageSize'] ?: 10;
        // 获得列表
        $list = (new BankingService())->getList($param);
        return api_output(0, $list);
    }  

    /**
     * 获得编辑所需数据
     */
    public function detail()
    {
        $param['banking_id'] = $this->request->param("banking_id", "", "intval");
        $param['from'] = 'user';
        $detail = (new BankingService())->getDetail($param);
        return api_output(0, $detail);
    }
}
