<?php
/**
 * 金融产品申请控制器
 * Author: hengtingmei
 * Date Time: 2022/01/08 13:50
 */

namespace app\banking\controller\platform;

use app\banking\model\service\BankingApplyService;
use app\community\model\service\HouseVillageService;

class BankingApplyController extends AuthBaseController
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
        $param['start_time'] = $this->request->param("start_time", "", "trim");
        $param['end_time'] = $this->request->param("end_time", "", "trim");
        $param['village_id'] = $this->request->param("village_id", "", "intval");
        $param['sort_name'] = $this->request->param("sort_name", "", "trim");
        $param['sort_type'] = $this->request->param("sort_type", "", "trim");
        $param['status'] = $this->request->param("status", "", "intval");

        // 获得列表
        $list = (new BankingApplyService())->getList($param);
        return api_output(0, $list);
    }   

    /**
     * 审核
     */
    public function changeStatus()
    {
        $param['apply_id'] = $this->request->param("id", "", "intval");
        $param['status'] = $this->request->param("status", "", "intval");

        $detail = (new BankingApplyService())->changeStatus($param, $this->systemUser);
        return api_output(0, $detail);
    }


    /**
     * 获得修改日志列表
     */
    public function getVillageList()
    {
        $param['keywords'] = $this->request->param("keywords", "0", "trim");
        $param['page'] = $this->request->param("page", "1", "intval");
        
        // 查询条件
        $where = [];
        if($param['keywords']){
            $where = [
                'village_name', 'like', '%'.$param['keywords'].'%'
            ];
        }

        // 获得列表
        $list = (new HouseVillageService())->getList($where,'village_id,village_name',$param['page'], 10, '', 1);
        return api_output(0, $list);
    }  

    // 导出
	public function export(){
        $param['page'] = $this->request->param("page", "1", "intval");
        // 每页显示数量
        $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $param['type'] = $this->request->param("type", "", "trim");
        $param['keywords'] = $this->request->param("keywords", "", "trim");
        $param['search_type'] = $this->request->param("search_type", "", "trim");
        $param['start_time'] = $this->request->param("start_time", "", "trim");
        $param['end_time'] = $this->request->param("end_time", "", "trim");
        $param['village_name'] = $this->request->param("village_name", "", "intval");
        $param['sort_name'] = $this->request->param("sort_name", "", "trim");
        $param['sort_type'] = $this->request->param("sort_type", "", "trim");
        
        try {
            $result = (new BankingApplyService())->addExport($param); 
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }
}
