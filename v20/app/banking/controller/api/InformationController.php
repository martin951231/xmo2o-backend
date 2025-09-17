<?php
/**
 * 金融资讯api接口
 * Author: hengtingmei
 * Date Time: 2022/01/10
 */

namespace app\banking\controller\api;

use app\banking\model\service\BankingService;

class InformationController extends ApiBaseController
{
    /**
     * 获得列表
     */
    public function list()
    {
        $param['page'] = $this->request->param("page", "1", "intval");
        // 每页显示数量
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['page'] =  $param['page'] ?: 1;
        $param['pageSize'] =  $param['pageSize'] ?: 10;
        $param['from'] = 'user';
        // 获得列表
        $list = (new BankingService())->getInformationList($param);
        return api_output(0, $list);
    }  

    /**
     * 资讯详情
     */
    public function detail()
    {
        $param['pigcms_id'] = $this->request->param("pigcms_id", "", "intval");
        $detail = (new BankingService())->getInformationData($param['pigcms_id'], 'user');
        return api_output(0, $detail);
    }
}
